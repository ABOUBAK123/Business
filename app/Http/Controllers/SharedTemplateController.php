<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\Document;
use App\Models\DocumentTemplate;
use App\Models\DocumentVersion;
use App\Services\Templates\TemplateGenerationCoreService;
use Dompdf\Dompdf;
use Dompdf\Options;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SharedTemplateController extends Controller
{
    private function allowedSharedTemplateIdsForUser(string $userId): array
    {
        $shareMapRaw = AppSetting::where('key', 'template_share_map')->value('value');
        $shareMap = [];

        if ($shareMapRaw) {
            try {
                $shareMap = json_decode($shareMapRaw, true) ?: [];
            } catch (\Exception $e) {
                $shareMap = [];
            }
        }

        return collect($shareMap)
            ->filter(fn ($users) => in_array($userId, (array) $users, true))
            ->keys()
            ->all();
    }

    /* ══════════════════════════════════════════════════════════
     *  INDEX — liste des templates partagés avec l'utilisateur
     * ══════════════════════════════════════════════════════════ */
    public function index(Request $request)
    {
        $user   = Auth::user();
        $search = $request->get('q', '');

        $allowedIds = $this->allowedSharedTemplateIdsForUser((string) $user->id);

        if (empty($allowedIds)) {
            return view('shared-templates.index', ['templates' => collect(), 'search' => $search]);
        }

        $query = DocumentTemplate::with(['variables', 'administration'])->whereIn('id', $allowedIds);

        if ($search) {
            $query->where('name', 'LIKE', "%{$search}%");
        }

        $templates = $query->latest()->get();

        // Pour les templates docx sans variables BDD, extraire les {{ }} depuis le XML du fichier
        $templates->each(function ($tpl) {
            $absPath = $tpl->storage_path ? $this->resolveAbsPath($tpl->storage_path) : null;
            if ($tpl->variables->isEmpty() && $absPath && file_exists($absPath)) {
                $ext = strtolower(pathinfo($tpl->storage_path ?: ($tpl->file_name ?? ''), PATHINFO_EXTENSION));
                if (in_array($ext, ['docx', 'xlsx', 'pptx'])) {
                    $tpl->docx_vars = $this->extractVarsFromOfficeFile($absPath);
                } else {
                    $tpl->docx_vars = [];
                }
            } else {
                $tpl->docx_vars = [];
            }
        });

        return view('shared-templates.index', compact('templates', 'search'));
    }

    /* ══════════════════════════════════════════════════════════
     *  GENERATE — génère un document à partir d'un template
     *
     *  Logique identique à l'app Node.js SharedTemplates.tsx :
     *  1. Extraire les [...] du champ `content` (slugifiés)
     *  2. Merger avec les variables BDD (template_variables)
     *  3. Remplacer [original] par la valeur saisie
     *  4. Pour les fichiers Office (docx/xlsx/pptx) : remplacer
     *     aussi dans le XML interne via ZipArchive
     * ══════════════════════════════════════════════════════════ */
    public function generate(Request $request, DocumentTemplate $template)
    {
        $allowedIds = $this->allowedSharedTemplateIdsForUser((string) Auth::id());
        abort_unless(in_array((string) $template->id, array_map('strval', $allowedIds), true), 403);

        $request->validate([
            'values'   => 'nullable|array',
            'values.*' => 'nullable|string|max:5000',
            'output_format' => 'nullable|in:source,pdf',
        ]);

        $coreService = app(TemplateGenerationCoreService::class);
        $template->loadMissing('variables');

        $values = $request->input('values', []);
        $outputFormat = $request->input('output_format', 'source');
        $generationWarning = null;

        // Livrable A: validation stricte des champs dynamiques requis.
        $coreService->assertRequiredValues($template, $values);

        /* -- Extraction des variables depuis le contenu --------- */
        \Log::info('GENERATE START template=' . $template->id . ' name=' . $template->name);
        \Log::info('GENERATE values_received=' . json_encode($values));
        \Log::info('GENERATE storage_path=' . ($template->storage_path ?: 'NULL'));

        $contentVarMap = $coreService->extractContentVariables($template->content ?? '');

        // Extraire aussi les {{ }} directement du fichier Office
        $ext = strtolower(pathinfo($template->storage_path ?: ($template->file_name ?? ''), PATHINFO_EXTENSION));
        $absTemplatePath = $template->storage_path ? $this->resolveAbsPath($template->storage_path) : null;
        $docxVars = [];
        if (in_array($ext, ['docx', 'xlsx', 'pptx'])
            && $absTemplatePath && file_exists($absTemplatePath))
        {
            $docxVars = $this->extractVarsFromOfficeFile($absTemplatePath);
        }

        /* -- Carte de remplacement : slug => label_original_dans_docx -- */
        $replacements = $coreService->buildReplacementMap($template, $contentVarMap, $docxVars);
        \Log::info('GENERATE replacements=' . json_encode($replacements));

        /* -- Remplacement dans le champ content (texte) --------- */
        $content = $template->content ?? '';
        foreach ($replacements as $slug => $original) {
            $val = $values[$slug] ?? '';
            // Supporte les deux syntaxes dans le contenu texte: {{var}} et [var]
            $content = preg_replace(
                '/\{\{\s*' . preg_quote($original, '/') . '\s*\}\}/u',
                $val,
                $content
            );
            $content = preg_replace(
                '/\[' . preg_quote($original, '/') . '\]/u',
                $val,
                $content
            );
            if ($slug !== $original) {
                $content = preg_replace(
                    '/\{\{\s*' . preg_quote($slug, '/') . '\s*\}\}/u',
                    $val,
                    $content
                );
                $content = preg_replace(
                    '/\[' . preg_quote($slug, '/') . '\]/u',
                    $val,
                    $content
                );
            }
        }

        /* ══════════════════════════════════════════════════════════
         *  NUMÉROTATION DU DOCUMENT
         *  Source du sub_entity_code : user_direction_assignments
         *  Format : CODE_ADMIN - CODE_ENTITE - 00001 - 2026
         * ══════════════════════════════════════════════════════════ */
        $numbering = $coreService->reserveDocumentNumber($template, Auth::id());
        $docNumber = $numbering['document_number'];
        $subEntityCode = $numbering['sub_entity_code'];
        $issuingAdminId = $numbering['issuing_administration_id'];

        /* ══════════════════════════════════════════════════════════
         *  QR CODE — token + URL de vérification
         * ══════════════════════════════════════════════════════════ */
        $qrToken   = Str::random(40);
        $verifyUrl = route('qr.download', ['token' => $qrToken]);

        // Position QR prioritaire: paramètre OnlyOffice/API Signature (signature_qr_position)
        // Fallback: anciens paramètres qr_image_* (en mm)
        $qrFromOnlyoffice = false;
        $qrX = 10.0;
        $qrY = 10.0;
        $qrW = 30.0;
        $qrH = 30.0;

        $signatureQrRaw = AppSetting::where('key', 'signature_qr_position')->value('value');
        if ($signatureQrRaw) {
            try {
                $signatureQr = json_decode($signatureQrRaw, true, 512, JSON_THROW_ON_ERROR);
                if (is_array($signatureQr)) {
                    $qrX = (float) ($signatureQr['imageX'] ?? $qrX);
                    $qrY = (float) ($signatureQr['imageY'] ?? $qrY);
                    $qrW = (float) ($signatureQr['imageWidth'] ?? $qrW);
                    $qrH = (float) ($signatureQr['imageHeight'] ?? $qrH);
                    $qrFromOnlyoffice = true;
                }
            } catch (\Throwable $e) {
                // fallback sur les paramètres historiques
            }
        }

        if (!$qrFromOnlyoffice) {
            $qrX = (float) (AppSetting::where('key', 'qr_image_x')->value('value') ?? 10);
            $qrY = (float) (AppSetting::where('key', 'qr_image_y')->value('value') ?? 10);
            $qrW = (float) (AppSetting::where('key', 'qr_image_width')->value('value') ?? 30);
            $qrH = (float) (AppSetting::where('key', 'qr_image_height')->value('value') ?? 30);
        }

        // Génération QR PNG dans un fichier temporaire (Dompdf requiert un chemin fichier)
        $qrTempPath = null;
        try {
            $qrResult   = Builder::create()
                ->writer(new PngWriter())
                ->data($verifyUrl)
                ->size(300)
                ->margin(6)
                ->build();
            $qrTempPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'qr_' . $qrToken . '.png';
            file_put_contents($qrTempPath, $qrResult->getString());
        } catch (\Throwable $e) {
            \Log::warning('QR generation failed for shared template document', [
                'template_id' => $template->id ?? null,
                'error' => $e->getMessage(),
            ]);
            $qrTempPath = null;
        }

        /* -- Copie + remplacement dans le fichier Office -------- */
        $baseName    = $template->file_name
            ? preg_replace('/\.[^.]+$/', '', $template->file_name)
            : Str::slug($template->name);
        $storagePath = null;
        $mimeType    = 'text/plain';
        $ext         = 'txt';

        $absSrcPath = $template->storage_path ? $this->resolveAbsPath($template->storage_path) : null;
        if ($absSrcPath && file_exists($absSrcPath)) {
            $ext      = pathinfo($template->storage_path ?: ($template->file_name ?? 'file.docx'), PATHINFO_EXTENSION) ?: 'docx';
            $destPath = 'documents/' . $baseName . '-' . now()->format('Ymd-His') . '.' . $ext;

            $mimeMap = [
                'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            ];
            $mimeType = $mimeMap[$ext] ?? 'application/octet-stream';

            // Copier dans storage/app/public/documents/ pour l'accès web via /storage/
            $absDestPath = Storage::disk('public')->path($destPath);
            if (!is_dir(dirname($absDestPath))) mkdir(dirname($absDestPath), 0755, true);
            copy($absSrcPath, $absDestPath);

            if (in_array($ext, ['docx', 'xlsx', 'pptx'])) {
                $absPath = $absDestPath;

                // Injecter document_number, qr_verify_url, et variables date/responsable automatiques
                $autoValues = $coreService->buildAutoValues(
                    $values,
                    $docNumber,
                    $verifyUrl,
                    Auth::user()->name ?? ''
                );

                \Log::info('GENERATE autoValues=' . json_encode($autoValues));
                \Log::info('GENERATE absSrcPath=' . ($absSrcPath ?: 'NULL') . ' exists=' . (file_exists($absSrcPath) ? 'YES' : 'NO'));
                \Log::info('GENERATE absDestPath=' . $absDestPath . ' exists_after_copy=' . (file_exists($absDestPath) ? 'YES' : 'NO'));

                // Les labels DB ont PRIORITÉ sur les slugs auto (ordre inversé: auto d'abord, DB ensuite)
                // Ex: 'date_du_jour' => 'date du jour' (DB label) écrase 'date_du_jour' => 'date_du_jour' (auto slug)
                $autoReplacements = $coreService->buildAutoReplacements($replacements);
                $this->replaceInOfficeFile($absPath, $autoReplacements, $autoValues);
                \Log::info('GENERATE replaceInOfficeFile done. docNumber=' . ($docNumber ?? 'NULL') . ' qrTemp=' . ($qrTempPath ?? 'NULL'));

                // Injecter le pied de page Word avec numéro + QR code (docx uniquement)
                if ($ext === 'docx' && $qrTempPath && file_exists($qrTempPath)) {
                    $qrWptForDocx = $qrFromOnlyoffice ? max(20, $qrW) : 56.7;
                    $qrHptForDocx = $qrFromOnlyoffice ? max(20, $qrH) : 56.7;
                    $this->injectDocxFooterWithQr($absPath, $docNumber ?? '', $verifyUrl, $qrTempPath, $qrWptForDocx, $qrHptForDocx);
                }

                // Option recommandée : template Office -> export PDF final (si convertisseur disponible)
                if ($outputFormat === 'pdf') {
                    $pdfPath = $this->convertOfficeToPdf($absDestPath);
                    if ($pdfPath) {
                        $pdfDestPath = 'documents/' . $baseName . '-' . now()->format('Ymd-His') . '.pdf';
                        Storage::disk('public')->put($pdfDestPath, file_get_contents($pdfPath));
                        @unlink($pdfPath);

                        $ext = 'pdf';
                        $mimeType = 'application/pdf';
                        $storagePath = '/storage/' . $pdfDestPath;
                    } else {
                        $generationWarning = 'Conversion PDF indisponible sur ce serveur. Document généré au format source.';
                    }
                }
            }

            // Nettoyage QR temp
            if ($qrTempPath && file_exists($qrTempPath)) {
                @unlink($qrTempPath);
                $qrTempPath = null;
            }

            if (!$storagePath) {
                $storagePath = '/storage/' . $destPath;
            }
        } else {
            // Génération PDF depuis le contenu texte
            $ext      = 'pdf';
            $mimeType = 'application/pdf';
            $destPath = 'documents/' . $baseName . '-' . now()->format('Ymd-His') . '.pdf';

            $htmlContent = nl2br(e($content));

            // HTML simple — sans position:fixed (non supporté par Dompdf)
            // Le numéro + footer sont injectés via canvas après le rendu
            $html = '<!DOCTYPE html><html><head><meta charset="UTF-8">
<style>
  body        { font-family: DejaVu Sans, sans-serif; font-size: 12pt; line-height: 1.6;
                color: #1a1a1a; margin: 40pt 40pt 60pt 40pt; }
  h1          { font-size: 16pt; color: #2453d6; border-bottom: 2pt solid #2453d6;
                padding-bottom: 6pt; margin-bottom: 16pt; }
  .meta       { color: #888; font-size: 9pt; margin-bottom: 24pt; }
  .docnum     { font-size: 9pt; font-weight: bold; color: #2453d6; margin-bottom: 4pt; }
  .content    { font-size: 11pt; }
</style></head><body>
<h1>' . e($template->name) . '</h1>
' . ($docNumber ? '<div class="docnum">N&#176; : ' . e($docNumber) . '</div>' : '') . '
<div class="meta">G&#233;n&#233;r&#233; le ' . now()->format('d/m/Y \à H:i') . '</div>
<div class="content">' . $htmlContent . '</div>
</body></html>';

            $options = new Options();
            $options->set('isRemoteEnabled', false);
            $options->set('isHtml5ParserEnabled', true);
            $options->set('defaultFont', 'dejavu sans');

            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($html, 'UTF-8');
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            // ── Injection du pied de page + QR via canvas Dompdf ───────────
            // A4 portrait : 595.28 x 841.89 points (1mm = 2.8346pt)
            $canvas  = $dompdf->getCanvas();
            $pw      = $canvas->get_width();   // ~595
            $ph      = $canvas->get_height();  // ~842
            $mm      = 2.8346;

            if ($qrFromOnlyoffice) {
                // Paramétrage OnlyOffice/API signature: coordonnées en points depuis le coin haut-gauche
                $qrWpt  = max(20, $qrW);
                $qrHpt  = max(20, $qrH);
                $qrXpt  = $qrX;
                $qrYpt  = $qrY;
            } else {
                // Paramétrage historique: marges en mm depuis droite/bas
                $qrWpt  = $qrW * $mm;
                $qrHpt  = $qrH * $mm;
                $qrXpt  = $pw - ($qrX * $mm) - $qrWpt;
                $qrYpt  = $ph - ($qrY * $mm) - $qrHpt;
            }

            // Clamp pour garantir que le QR reste visible dans la page
            $qrXpt = max(0, min($pw - $qrWpt, $qrXpt));
            $qrYpt = max(0, min($ph - $qrHpt, $qrYpt));

            $tmpQr   = $qrTempPath;
            $docNum  = $docNumber;
            $genDate = now()->format('d/m/Y H:i');

            $canvas->page_script(
                function (int $pageNumber, int $pageCount, $canvas, $fontMetrics)
                    use ($tmpQr, $qrXpt, $qrYpt, $qrWpt, $qrHpt, $ph, $pw, $docNum, $genDate)
                {
                    $fontNormal = $fontMetrics->getFont('DejaVu Sans', 'normal');
                    $gray  = [0.55, 0.55, 0.55];
                    $blue  = [0.14, 0.32, 0.84];

                    // Ligne de séparation du pied de page
                    $footerY = $ph - 40;
                    $canvas->line(28, $footerY, $pw - 28, $footerY, [0.8, 0.8, 0.8], 0.5);

                    // Numéro de document (gauche)
                    if ($docNum) {
                        $canvas->text(28, $footerY + 5, 'N\u00b0 : ' . $docNum, $fontNormal, 7.5, $blue);
                    }

                    // Texte vérification (gauche, 2e ligne)
                    $canvas->text(28, $footerY + 16, 'Authenticit\u00e9 v\u00e9rifiable par scan du QR code', $fontNormal, 7, $gray);

                    // Numéro de page (droite)
                    $pageTxt = 'Page ' . $pageNumber . ' / ' . $pageCount;
                    $tw = $fontMetrics->getTextWidth($pageTxt, $fontNormal, 7);
                    $canvas->text($pw - 28 - $tw, $footerY + 16, $pageTxt, $fontNormal, 7, $gray);

                    // QR code image
                    if ($tmpQr && file_exists($tmpQr)) {
                        $canvas->image($tmpQr, $qrXpt, $qrYpt, $qrWpt, $qrHpt);
                    }
                }
            );

            $pdfContent = $dompdf->output();

            // Nettoyage du fichier QR temporaire
            if ($qrTempPath && file_exists($qrTempPath)) {
                @unlink($qrTempPath);
                $qrTempPath = null;
            }

            Storage::disk('public')->put($destPath, $pdfContent);
            $storagePath = '/storage/' . $destPath;
        }

        /* -- Création en base ----------------------------------- */
        $fileName = $baseName . '-' . now()->format('Ymd-His') . '.' . $ext;
        $docId    = (string) Str::uuid();
        $title    = ($docNumber ? '[' . $docNumber . '] ' : '') . $template->name . ' — ' . now()->format('d/m/Y H:i');

        $document = Document::create([
            'id'                     => $docId,
            'title'                  => $title,
            'description'            => 'Généré depuis : ' . $template->name,
            'file_path'              => $storagePath,
            'file_size'              => $storagePath ? Storage::disk('public')->size(ltrim(str_replace('/storage/', '', $storagePath), '/')) : 0,
            'mime_type'              => $mimeType,
            'status'                 => 'active',
            'owner_id'               => Auth::id(),
            'created_by'             => Auth::id(),
            'document_number'        => $docNumber,
            'sub_entity_code'        => $subEntityCode,
            'qr_token'               => $qrToken,
            'issuing_administration_id' => $issuingAdminId,
        ]);

        DocumentVersion::create([
            'document_id' => $docId,
            'version'     => 1,
            'file_path'   => $storagePath,
            'creator_id'  => Auth::id(),
            'change_log'  => 'Génération depuis template : ' . $template->name,
        ]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success'           => true,
                'document_id'       => $docId,
                'title'             => $title,
                'document_number'   => $docNumber,
                'qr_token'          => $qrToken,
                'verify_url'        => $verifyUrl,
                'file_path'         => $storagePath,
                'generated_content' => $content,
                'message'           => 'Document généré et enregistré dans Mes Documents.',
                'warning'           => $generationWarning,
            ]);
        }

        return redirect()->route('documents.index')->with('success', 'Document généré avec succès !');
    }

    /* ══════════════════════════════════════════════════════════
     *  HELPERS PRIVÉS
     * ══════════════════════════════════════════════════════════ */

    /**
     * Résout le chemin absolu d'un fichier template quel que soit son emplacement.
     * - "images/templates/xxx.docx" → public_path("images/templates/xxx.docx")
     * - "documents/xxx.docx"        → storage/app/public/documents/xxx.docx
     * - "templates/xxx.docx"        → storage/app/public/templates/xxx.docx
     */
    private function resolveAbsPath(string $storagePath): string
    {
        if (str_starts_with($storagePath, 'images/')) {
            return public_path($storagePath);
        }
        return Storage::disk('public')->path($storagePath);
    }

    /**
     * Tente la conversion d'un fichier Office (docx/xlsx/pptx) en PDF via LibreOffice.
     * Retourne le chemin absolu du PDF généré, ou null si échec / binaire absent.
     */
    private function convertOfficeToPdf(string $absOfficePath): ?string
    {
        $tmpOutDir = storage_path('app/tmp/pdf-convert');
        if (!is_dir($tmpOutDir)) {
            @mkdir($tmpOutDir, 0755, true);
        }

        $commands = [
            'soffice --headless --nologo --convert-to pdf --outdir ' . escapeshellarg($tmpOutDir) . ' ' . escapeshellarg($absOfficePath),
            'libreoffice --headless --nologo --convert-to pdf --outdir ' . escapeshellarg($tmpOutDir) . ' ' . escapeshellarg($absOfficePath),
        ];

        foreach ($commands as $cmd) {
            @exec($cmd, $output, $code);
            if ($code !== 0) {
                continue;
            }

            $pdfFile = $tmpOutDir . DIRECTORY_SEPARATOR . pathinfo($absOfficePath, PATHINFO_FILENAME) . '.pdf';
            if (file_exists($pdfFile)) {
                return $pdfFile;
            }

            // Fallback si LibreOffice applique un nom différent
            $candidates = glob($tmpOutDir . DIRECTORY_SEPARATOR . '*.pdf') ?: [];
            if (!empty($candidates)) {
                usort($candidates, fn($a, $b) => filemtime($b) <=> filemtime($a));
                return $candidates[0];
            }
        }

        return null;
    }

    /**
     * Extrait les variables {{ }} depuis le XML interne d'un fichier Office (docx/xlsx/pptx).
     * Retourne un tableau [ ['key' => slug, 'label' => originalName], ... ]
     */
    private function extractVarsFromOfficeFile(string $absFilePath): array
    {
        if (!class_exists('ZipArchive') || !file_exists($absFilePath)) return [];

        $zip = new \ZipArchive();
        if ($zip->open($absFilePath) !== true) return [];

        $found = []; // slug => original
        $numFiles = $zip->numFiles;

        for ($i = 0; $i < $numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (!preg_match('/\.xml$/i', $name)) continue;
            if (preg_match('#\[Content_Types\]|_rels/#', $name)) continue;

            $xml = $zip->getFromIndex($i);
            if ($xml === false) continue;

            // Défragmenter les runs Word avant extraction (cas fréquent OnlyOffice)
            $isWordContent = preg_match('#word/(document|header|footer|endnote|footnote)#i', $name);
            $normalizedXml = $isWordContent ? $this->defragmentRuns($xml) : $xml;

            // Supprimer les balises XML + décoder les entités pour retrouver {{ }}
            $text = html_entity_decode(strip_tags($normalizedXml), ENT_QUOTES | ENT_HTML5, 'UTF-8');

            // Support des deux syntaxes : {{variable}} (ancien) et [variable] (nouveau)
            preg_match_all('/(?:\{\s*\{)\s*([^{}]+?)\s*(?:\}\s*\})/u', $text, $m1);
            preg_match_all('/\[([^\[\]]+?)\]/u', $text, $m2);
            foreach (array_merge($m1[1], $m2[1]) as $original) {
                $original = trim($original);
                if (!$original) continue;
                $slug = $this->slugify($original);
                if ($slug && !isset($found[$slug])) {
                    $found[$slug] = $original;
                }
            }
        }

        $zip->close();

        $result = [];
        foreach ($found as $slug => $original) {
            $result[] = [
                'key'         => $slug,
                'label'       => $original,
                'field_type'  => 'text',
                'required'    => false,
                'placeholder' => '',
                'default_value' => '',
                'options'     => [],
            ];
        }
        return $result;
    }

    /**
     * Slugifie un nom de variable — MÊME logique que le JS de l'app Node.js :
     *
     *   slugify("N'DJOMON Ohouo Landry Marius")
     *   => "n_djomon_ohouo_landry_marius"
     *
     * Étapes : translittération ASCII → minuscules → ' → _ → non-alnum → _ → trim _
     */
    private function slugify(string $text): string
    {
        // Translittération (supprime accents, ligatures…)
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        $text  = ($ascii !== false && $ascii !== '') ? $ascii : $text;


        $text = strtolower($text);
        $text = str_replace("'", '_', $text);          // apostrophe → _
        $text = preg_replace('/[^a-z0-9]+/', '_', $text); // tout le reste → _
        $text = trim($text, '_');

        return $text ?: 'var';
    }

    /**
     * Extrait toutes les variables [...] d'un contenu texte.
     * Retourne un tableau [ slug => originalName ] (dédupliqué, ordre de première apparition).
     *
     * Exemple : "Bonjour [N'DJOMON Landry], le [DATE]."
     *   => ['n_djomon_landry' => "N'DJOMON Landry", 'date' => 'DATE']
     */
    private function extractContentVars(string $content): array
    {
        if (!$content) return [];

        // Support des deux syntaxes : {{variable}} (ancien) et [variable] (nouveau)
        preg_match_all('/\{\{\s*([^{}]+?)\s*\}\}/', $content, $m1);
        preg_match_all('/\[([^\[\]]+?)\]/', $content, $m2);

        $vars = [];
        foreach (array_merge($m1[1], $m2[1]) as $match) {
            $original = trim($match);
            if ($original === '') continue;
            $slug     = $this->slugify($original);
            if (!isset($vars[$slug])) {
                $vars[$slug] = $original;
            }
        }
        return $vars;
    }

    /**
     * Injecte un pied de page dans un fichier .docx avec :
     * - Le numéro de document (texte gauche)
     * - Le QR code (image droite, 2cm x 2cm)
     * - L'URL de vérification (texte centré)
     *
     * Fonctionne en manipulant le ZIP du docx directement.
     * N'écrase pas un footer existant — ajoute un nouveau footer "default".
     */
    private function injectDocxFooterWithQr(string $absFilePath, string $docNumber, string $verifyUrl, string $qrPngPath, float $qrWidthPt = 56.7, float $qrHeightPt = 56.7): void
    {
        if (!class_exists('ZipArchive') || !file_exists($qrPngPath)) return;

        $zip = new \ZipArchive();
        if ($zip->open($absFilePath) !== true) return;

        // Lire les fichiers existants
        $docXml       = $zip->getFromName('word/document.xml');
        $contentTypes = $zip->getFromName('[Content_Types].xml');

        if ($docXml === false || $contentTypes === false) {
            $zip->close();
            return;
        }

        // Créer un _rels minimal si absent (DOCX simple sans relations)
        $docRelsXml = $zip->getFromName('word/_rels/document.xml.rels');
        if ($docRelsXml === false) {
            $docRelsXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
                . '</Relationships>';
        }

        // Ajouter un <w:sectPr> minimal si absent (requis pour la référence footer)
        if (strpos($docXml, '<w:sectPr') === false) {
            $docXml = str_replace('</w:body>', '<w:sectPr/></w:body>', $docXml);
        }

        $qrPngBytes  = file_get_contents($qrPngPath);
        $footerRelId = 'rIdFtrE-Admin1';
        $imgRelId    = 'rIdQrFtrImg1';
        $footerFile  = 'word/footer_eadmin.xml';
        $footerRels  = 'word/_rels/footer_eadmin.xml.rels';
        $mediaFile   = 'word/media/qr_eadmin.png';

        // 1. Ajouter l'image QR dans le ZIP
        $zip->addFromString($mediaFile, $qrPngBytes);

        // 2. Créer le fichier de relations du footer
        $footerRelsContent = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="' . $imgRelId . '"'
            . ' Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image"'
            . ' Target="media/qr_eadmin.png"/>'
            . '</Relationships>';
        $zip->addFromString($footerRels, $footerRelsContent);

        // 3. Construire le footer XML Word
        // Taille image en EMU (1pt = 12700 EMU)
        $cx  = (int) max(12700, round($qrWidthPt * 12700));
        $cy  = (int) max(12700, round($qrHeightPt * 12700));
        $num = htmlspecialchars($docNumber, ENT_XML1, 'UTF-8');
        $url = htmlspecialchars($verifyUrl,  ENT_XML1, 'UTF-8');

        $footerXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<w:ftr xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"'
            . ' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"'
            . ' xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing"'
            . ' xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main"'
            . ' xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture">'

            // Ligne séparatrice (bordure haut du paragraphe)
            . '<w:p>'
            . '<w:pPr>'
            . '<w:pBdr><w:top w:val="single" w:sz="4" w:space="1" w:color="CCCCCC"/></w:pBdr>'
            . '<w:tabs><w:tab w:val="center" w:pos="4680"/><w:tab w:val="right" w:pos="9360"/></w:tabs>'
            . '</w:pPr>'
            // Numéro (gauche)
            . '<w:r><w:rPr><w:sz w:val="16"/><w:color w:val="2453D6"/><w:b/></w:rPr>'
            . '<w:t xml:space="preserve">N\xc2\xb0\xc2\xa0: ' . $num . '</w:t></w:r>'
            // Tab → centre
            . '<w:r><w:tab/></w:r>'
            // Texte vérification (centre)
            . '<w:r><w:rPr><w:sz w:val="14"/><w:color w:val="888888"/></w:rPr>'
            . '<w:t>Authenticit\xc3\xa9 v\xc3\xa9rifiable par QR code</w:t></w:r>'
            // Tab → droite
            . '<w:r><w:tab/></w:r>'
            // QR code image (droite, inline)
            . '<w:r><w:rPr/>'
            . '<w:drawing>'
            . '<wp:inline distT="0" distB="0" distL="0" distR="0">'
            . '<wp:extent cx="' . $cx . '" cy="' . $cy . '"/>'
            . '<wp:effectExtent l="0" t="0" r="0" b="0"/>'
            . '<wp:docPr id="101" name="QR-eAdmin"/>'
            . '<wp:cNvGraphicFramePr>'
            . '<a:graphicFrameLocks noChangeAspect="1"/>'
            . '</wp:cNvGraphicFramePr>'
            . '<a:graphic>'
            . '<a:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/picture">'
            . '<pic:pic>'
            . '<pic:nvPicPr>'
            . '<pic:cNvPr id="0" name="QR-eAdmin"/>'
            . '<pic:cNvPicPr><a:picLocks noChangeAspect="1"/></pic:cNvPicPr>'
            . '</pic:nvPicPr>'
            . '<pic:blipFill>'
            . '<a:blip r:embed="' . $imgRelId . '"/>'
            . '<a:stretch><a:fillRect/></a:stretch>'
            . '</pic:blipFill>'
            . '<pic:spPr>'
            . '<a:xfrm><a:off x="0" y="0"/><a:ext cx="' . $cx . '" cy="' . $cy . '"/></a:xfrm>'
            . '<a:prstGeom prst="rect"><a:avLst/></a:prstGeom>'
            . '</pic:spPr>'
            . '</pic:pic>'
            . '</a:graphicData>'
            . '</a:graphic>'
            . '</wp:inline>'
            . '</w:drawing>'
            . '</w:r>'
            . '</w:p>'
            . '</w:ftr>';

        $zip->addFromString($footerFile, $footerXml);

        // 4. Ajouter la relation footer dans document.xml.rels
        $docRelsXml = str_replace(
            '</Relationships>',
            '<Relationship Id="' . $footerRelId . '"'
            . ' Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/footer"'
            . ' Target="footer_eadmin.xml"/>'
            . '</Relationships>',
            $docRelsXml
        );
        $zip->addFromString('word/_rels/document.xml.rels', $docRelsXml);

        // 5. Ajouter la référence footer dans sectPr du document.xml
        // IMPORTANT: injecter dans le DERNIER <w:sectPr> (le sectPr principal du corps du document,
        // pas les sectPr des sauts de section à l'intérieur du texte).
        $footerRef = '<w:footerReference w:type="default" r:id="' . $footerRelId . '"/>';
        if (strpos($docXml, 'w:footerReference') === false) {
            // Trouver la DERNIÈRE occurrence de </w:sectPr> ou <w:sectPr ... />
            $lastClose = strrpos($docXml, '</w:sectPr>');
            if ($lastClose !== false) {
                // Insérer footerRef juste avant le dernier </w:sectPr>
                $docXml = substr($docXml, 0, $lastClose) . $footerRef . substr($docXml, $lastClose);
            } else {
                // Pas de </w:sectPr> : chercher un self-closing <w:sectPr ... /> et l'expanser
                $expanded = preg_replace('/<w:sectPr([^>]*)\/>/s', '<w:sectPr$1>' . $footerRef . '</w:sectPr>', $docXml, 1, $cnt);
                if ($cnt > 0 && is_string($expanded)) {
                    $docXml = $expanded;
                } else {
                    // Dernier recours : insérer avant </w:body>
                    $docXml = str_replace('</w:body>', '<w:sectPr>' . $footerRef . '</w:sectPr></w:body>', $docXml);
                }
            }
        } else {
            // Remplacer le footer default existant pour garantir l'affichage du QR
            // → remplacer dans le dernier footerReference default
            $pattern = '/<w:footerReference\s+[^>]*w:type="default"[^>]*\/>/';
            preg_match_all($pattern, $docXml, $allMatches, PREG_OFFSET_CAPTURE);
            if (!empty($allMatches[0])) {
                $last = end($allMatches[0]);
                $docXml = substr($docXml, 0, $last[1]) . $footerRef . substr($docXml, $last[1] + strlen($last[0]));
            } else {
                // Injecter avant le dernier </w:sectPr>
                $lastClose = strrpos($docXml, '</w:sectPr>');
                if ($lastClose !== false) {
                    $docXml = substr($docXml, 0, $lastClose) . $footerRef . substr($docXml, $lastClose);
                }
            }
        }
        $zip->addFromString('word/document.xml', $docXml);

        // 6. Déclarer le footer dans [Content_Types].xml
        if (strpos($contentTypes, 'footer_eadmin.xml') === false) {
            $contentTypes = str_replace(
                '</Types>',
                '<Override PartName="/word/footer_eadmin.xml"'
                . ' ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.footer+xml"/>'
                . '</Types>',
                $contentTypes
            );
            $zip->addFromString('[Content_Types].xml', $contentTypes);
        }

        $zip->close();
    }

    /**
     * Remplace les [...] dans le XML interne d'un fichier Office (docx/xlsx/pptx).
     * Les fichiers Office sont des archives ZIP contenant des fichiers XML.
     *
     * IMPORTANT : Dans Word, une variable comme [NOM] peut être fragmentée en
     * plusieurs "runs" XML (<w:r>). Ce code gère le cas simple où le placeholder
     * est entier dans un seul run. Pour les cas complexes, OnlyOffice garantit
     * l'intégrité des runs lors de la saisie directe.
     */
    private function replaceInOfficeFile(string $absFilePath, array $replacements, array $values): void
    {
        if (!class_exists('ZipArchive')) return;

        \Log::info('replaceInOfficeFile START file=' . $absFilePath . ' exists=' . (file_exists($absFilePath) ? 'YES' : 'NO'));
        \Log::info('replaceInOfficeFile replacements_count=' . count($replacements) . ' values_count=' . count($values));

        $zip = new \ZipArchive();
        if ($zip->open($absFilePath, \ZipArchive::CREATE) !== true) {
            \Log::error('replaceInOfficeFile FAILED to open ZIP: ' . $absFilePath);
            return;
        }

        $numFiles = $zip->numFiles;
        $toUpdate = [];

        for ($i = 0; $i < $numFiles; $i++) {
            $stat = $zip->statIndex($i);
            $name = $stat['name'];

            if (!preg_match('/\.xml$/i', $name)) continue;
            if (preg_match('#\[Content_Types\]|_rels/#', $name)) continue;

            $xmlContent = $zip->getFromIndex($i);
            if ($xmlContent === false) continue;

            // ── ÉTAPE 1 : défragmenter les runs dans chaque paragraphe ──────
            // Appliquer sur tout fichier XML Word pouvant contenir {{ }} :
            // document.xml, header1.xml, footer1.xml, etc.
            // On évite les fichiers de styles/settings/relations qui n'ont pas de runs.
            $isWordContent = preg_match('#word/(document|header|footer|endnote|footnote)#i', $name);
            if ($isWordContent) {
                $newContent = $this->defragmentRuns($xmlContent);
            } else {
                $newContent = $xmlContent;
            }

            // ── ÉTAPE 2 : remplacer [variable] ET {{variable}} dans le XML défragmenté ──
            foreach ($replacements as $slug => $original) {
                $val = htmlspecialchars($values[$slug] ?? '', ENT_XML1, 'UTF-8');

                // Syntaxe nouvelle : [original]  — insensible à la casse (iu)
                $newContent = preg_replace(
                    '/\[' . preg_quote($original, '/') . '\]/iu',
                    $val,
                    $newContent
                );
                // Syntaxe ancienne : {{original}} — insensible à la casse (iu)
                $newContent = preg_replace(
                    '/\{\{\s*' . preg_quote($original, '/') . '\s*\}\}/iu',
                    $val,
                    $newContent
                );
                if ($slug !== $original) {
                    // Syntaxe nouvelle avec slug : [slug] — insensible à la casse (iu)
                    $newContent = preg_replace(
                        '/\[' . preg_quote($slug, '/') . '\]/iu',
                        $val,
                        $newContent
                    );
                    // Syntaxe ancienne avec slug : {{slug}} — insensible à la casse (iu)
                    $newContent = preg_replace(
                        '/\{\{\s*' . preg_quote($slug, '/') . '\s*\}\}/iu',
                        $val,
                        $newContent
                    );
                }
            }

            if ($newContent !== $xmlContent) {
                $toUpdate[$name] = $newContent;
            }
        }

        foreach ($toUpdate as $name => $newContent) {
            $zip->addFromString($name, $newContent);
        }

        \Log::info('replaceInOfficeFile files_updated=' . count($toUpdate) . ' keys=' . implode(',', array_keys($toUpdate)));

        $zip->close();
    }

    /**
     * Défragmente les runs Word dans chaque paragraphe <w:p>.
     *
     * Problème : Word peut stocker [VAR] sur plusieurs runs :
     *   <w:r><w:t>[</w:t></w:r><w:r><w:t>VAR</w:t></w:r><w:r><w:t>]</w:t></w:r>
     *
     * Solution : pour chaque paragraphe, si le texte concaténé contient [variable],
     * on regroupe tous les textes dans un seul <w:r> avec le rPr du premier run.
     * Les paragraphes sans [variable] ne sont pas touchés.
     * Le texte XML brut est conservé tel quel (pas de decode/re-encode).
     */
    private function defragmentRuns(string $xml): string
    {
        return preg_replace_callback(
            '/<w:p[ >].*?<\/w:p>/s',
            function (array $match) {
                $para = $match[0];

                // Extraire le texte brut XML de tous les <w:t> (sans décoder les entités)
                preg_match_all('/<w:t[^>]*>(.*?)<\/w:t>/s', $para, $texts);
                $fullText = implode('', $texts[1]);

                // Si pas de [variable] ni {{variable}} dans ce paragraphe → ne rien toucher
                if (!preg_match('/\[[^\[\]]+\]/', $fullText) && strpos($fullText, '{{') === false) {
                    return $para;
                }

                // Récupérer le rPr du premier run (pour conserver le formatage)
                $firstRpr = '';
                if (preg_match('/<w:r[ >].*?(<w:rPr>.*?<\/w:rPr>)/s', $para, $rprMatch)) {
                    $firstRpr = $rprMatch[1];
                }

                // Extraire le pPr (propriétés du paragraphe) si présent
                $pPr = '';
                if (preg_match('/<w:pPr>.*?<\/w:pPr>/s', $para, $pPrMatch)) {
                    $pPr = $pPrMatch[0];
                }

                // Extraire le tag ouvrant <w:p ...>
                preg_match('/^<w:p[^>]*>/', $para, $openTag);
                $open = $openTag[0] ?? '<w:p>';

                // Reconstruire le paragraphe : pPr + un seul run avec tout le texte brut
                return $open
                    . $pPr
                    . '<w:r>'
                    . $firstRpr
                    . '<w:t xml:space="preserve">' . $fullText . '</w:t>'
                    . '</w:r>'
                    . '</w:p>';
            },
            $xml
        );
    }
}

