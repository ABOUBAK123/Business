<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CourrierOcrService
{
    private string $ollamaUrl;
    private string $ollamaModel;
    private int    $timeout;

    public function __construct()
    {
        $this->ollamaUrl   = rtrim(config('services.ollama.url', 'http://localhost:11434'), '/');
        $this->ollamaModel = config('services.ollama.model', 'llama3');
        $this->timeout     = (int) config('services.ollama.timeout', 60);
    }

    /**
     * Extrait les champs d'un courrier depuis un fichier scanné (PDF ou image).
     * Retourne un tableau avec les clés : objet, expediteur, destinataire,
     * date_emission, numero_emission, urgence.
     */
    public function extractFields(UploadedFile $file): array
    {
        $text = $this->extractText($file);

        if (empty(trim($text))) {
            return ['error' => 'Impossible d\'extraire le texte du fichier. Vérifiez que le fichier est lisible.'];
        }

        return $this->extractFieldsWithLlm($text);
    }

    private function extractText(UploadedFile $file): string
    {
        $ext = strtolower($file->getClientOriginalExtension());
        $tmp = $file->getPathname();

        if ($ext === 'pdf') {
            return $this->extractFromPdf($tmp);
        }

        if (in_array($ext, ['jpg', 'jpeg', 'png', 'tiff', 'tif', 'bmp'])) {
            return $this->extractFromImage($tmp);
        }

        return '';
    }

    private function extractFromPdf(string $path): string
    {
        $escaped  = escapeshellarg($path);
        $devNull  = PHP_OS_FAMILY === 'Windows' ? '2>nul' : '2>/dev/null';

        // Try pdftotext (text-based PDF)
        $output = @shell_exec("pdftotext -layout {$escaped} - {$devNull}");
        if (!empty(trim((string) $output))) {
            return (string) $output;
        }

        // Try ghostscript text extraction
        $output = @shell_exec("gs -dBATCH -dNOPAUSE -sDEVICE=txtwrite -sOutputFile=- {$escaped} {$devNull}");
        if (!empty(trim((string) $output))) {
            return (string) $output;
        }

        // PDF is image-based (scanned): convert pages to PNG then run tesseract
        return $this->extractFromScannedPdf($path, $devNull);
    }

    private function extractFromScannedPdf(string $path, string $devNull): string
    {
        $tmpDir  = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ocr_' . uniqid();
        @mkdir($tmpDir, 0700, true);

        try {
            $escapedPdf = escapeshellarg($path);
            $outPattern = escapeshellarg($tmpDir . DIRECTORY_SEPARATOR . 'page_%04d.png');

            // Convert PDF pages to PNG at 200 DPI
            @shell_exec("gs -dBATCH -dNOPAUSE -sDEVICE=png16m -r200 -sOutputFile={$outPattern} {$escapedPdf} {$devNull}");

            $pages = glob($tmpDir . DIRECTORY_SEPARATOR . '*.png') ?: [];
            sort($pages);

            $text = '';
            foreach (array_slice($pages, 0, 5) as $page) {
                $escapedPage = escapeshellarg($page);
                $out = @shell_exec("tesseract {$escapedPage} stdout -l fra {$devNull}");
                if (empty(trim((string) $out))) {
                    $out = @shell_exec("tesseract {$escapedPage} stdout {$devNull}");
                }
                $text .= (string) $out;
            }

            return $text;
        } finally {
            // Clean up temp files
            foreach (glob($tmpDir . DIRECTORY_SEPARATOR . '*') ?: [] as $f) {
                @unlink($f);
            }
            @rmdir($tmpDir);
        }
    }

    private function extractFromImage(string $path): string
    {
        $escaped = escapeshellarg($path);
        $devNull = PHP_OS_FAMILY === 'Windows' ? '2>nul' : '2>/dev/null';

        $output = @shell_exec("tesseract {$escaped} stdout -l fra {$devNull}");
        if (!empty(trim((string) $output))) {
            return (string) $output;
        }

        $output = @shell_exec("tesseract {$escaped} stdout {$devNull}");
        return (string) ($output ?? '');
    }

    private function extractFieldsWithLlm(string $text): array
    {
        // Essayer Ollama d'abord, fallback regex si indisponible
        if ($this->isOllamaAvailable()) {
            $result = $this->extractWithOllama($text);
            if (!isset($result['error'])) {
                return $result;
            }
        }

        // Fallback : extraction par expressions régulières
        return $this->extractWithRegex($text);
    }

    private function isOllamaAvailable(): bool
    {
        try {
            $response = Http::timeout(3)->get("{$this->ollamaUrl}");
            return $response->successful();
        } catch (\Throwable) {
            return false;
        }
    }

    private function extractWithOllama(string $text): array
    {
        $textTruncated = mb_substr($text, 0, 3000);

        $prompt = <<<PROMPT
Tu es un assistant expert en courriers administratifs francophones.
Analyse ce texte extrait d'un courrier et extrais les informations suivantes au format JSON strict.

Champs à extraire :
- "objet": l'objet ou le sujet principal du courrier (string ou null)
- "expediteur": la personne ou organisation qui envoie le courrier (string ou null)
- "destinataire": la personne ou organisation destinataire (string ou null)
- "date_emission": la date du courrier au format YYYY-MM-DD (string ou null)
- "numero_emission": le numéro de référence, d'enregistrement ou de courrier (string ou null)
- "urgence": "normale", "urgent" ou "tres_urgent" selon les marqueurs d'urgence détectés (string)

Texte du courrier :
{$textTruncated}

Réponds UNIQUEMENT avec un objet JSON valide, sans commentaire ni explication. Exemple :
{"objet":"Demande de congé","expediteur":"Jean Dupont","destinataire":"Direction RH","date_emission":"2026-05-11","numero_emission":"REF-2026-001","urgence":"normale"}
PROMPT;

        try {
            $response = Http::timeout($this->timeout)->post("{$this->ollamaUrl}/api/generate", [
                'model'  => $this->ollamaModel,
                'prompt' => $prompt,
                'stream' => false,
            ]);

            if (!$response->successful()) {
                return ['error' => 'Ollama indisponible'];
            }

            $raw = (string) $response->json('response', '');
            if (preg_match('/\{[^{}]*\}/s', $raw, $m)) {
                $data = json_decode($m[0], true);
                if (is_array($data)) {
                    return $this->sanitizeFields($data);
                }
            }

            return ['error' => 'Réponse Ollama non structurée'];

        } catch (\Throwable $e) {
            Log::warning('CourrierOcr: Ollama error', ['error' => $e->getMessage()]);
            return ['error' => 'Ollama error'];
        }
    }

    private function extractWithRegex(string $text): array
    {
        $data = [
            'objet'           => null,
            'expediteur'      => null,
            'destinataire'    => null,
            'date_emission'   => null,
            'numero_emission' => null,
            'urgence'         => 'normale',
        ];

        // ── Objet ──────────────────────────────────────────────────────────────
        if (preg_match('/(?:^|\n)\s*Objet\s*[:\/]\s*(.+)/im', $text, $m)) {
            $data['objet'] = mb_substr(trim($m[1]), 0, 500);
        } elseif (preg_match('/(?:^|\n)\s*(?:RE|Object|Sujet|Réf\.?\s*:)\s*[:\/]?\s*(.+)/im', $text, $m)) {
            $data['objet'] = mb_substr(trim($m[1]), 0, 500);
        }

        // ── Numéro de référence ────────────────────────────────────────────────
        if (preg_match('/(?:N[°º\/]|Réf\.?|Référence|Ref\.?)\s*:?\s*([A-Z0-9\/\-\._ ]{3,40})/im', $text, $m)) {
            $data['numero_emission'] = mb_substr(trim($m[1]), 0, 100);
        }

        // ── Date ──────────────────────────────────────────────────────────────
        $data['date_emission'] = $this->extractDate($text);

        // ── Expéditeur ────────────────────────────────────────────────────────
        foreach (['Expéditeur', 'De', 'From', 'Émetteur', 'L\'émetteur', 'Signataire'] as $lbl) {
            if (preg_match('/(?:^|\n)\s*' . preg_quote($lbl, '/') . '\s*[:]\s*(.+)/im', $text, $m)) {
                $data['expediteur'] = mb_substr(trim($m[1]), 0, 300);
                break;
            }
        }

        // ── Destinataire ──────────────────────────────────────────────────────
        foreach (['Destinataire', 'À', 'A', 'Monsieur', 'Madame', 'To'] as $lbl) {
            if (preg_match('/(?:^|\n)\s*' . preg_quote($lbl, '/') . '\s*[:]\s*(.+)/im', $text, $m)) {
                $data['destinataire'] = mb_substr(trim($m[1]), 0, 300);
                break;
            }
        }

        // ── Urgence ───────────────────────────────────────────────────────────
        if (preg_match('/très\s*urgent|TRÈS\s*URGENT|tres\s*urgent|PRIORITAIRE\s*URGENT/i', $text)) {
            $data['urgence'] = 'tres_urgent';
        } elseif (preg_match('/\bURGENT\b|\bURGENCE\b|\bPRIORITAIRE\b/i', $text)) {
            $data['urgence'] = 'urgent';
        }

        return $this->sanitizeFields($data);
    }

    private function extractDate(string $text): ?string
    {
        $moisFr = [
            'janvier' => '01', 'février' => '02', 'fevrier' => '02', 'mars' => '03',
            'avril' => '04', 'mai' => '05', 'juin' => '06', 'juillet' => '07',
            'août' => '08', 'aout' => '08', 'septembre' => '09', 'octobre' => '10',
            'novembre' => '11', 'décembre' => '12', 'decembre' => '12',
        ];

        // Format: DD/MM/YYYY ou DD-MM-YYYY
        if (preg_match('/\b(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})\b/', $text, $m)) {
            return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
        }

        // Format: YYYY-MM-DD
        if (preg_match('/\b(20\d{2})-(\d{2})-(\d{2})\b/', $text, $m)) {
            return $m[0];
        }

        // Format littéral: "le 12 mars 2026" ou "12 mars 2026"
        $moisPattern = implode('|', array_keys($moisFr));
        if (preg_match('/\b(?:le\s+)?(\d{1,2})\s+(' . $moisPattern . ')\s+(20\d{2})\b/i', $text, $m)) {
            $mois = strtolower($m[2]);
            return sprintf('%04d-%02d-%02d', $m[3], $moisFr[$mois] ?? '01', (int) $m[1]);
        }

        return null;
    }

    private function sanitizeFields(array $data): array
    {
        $valid = ['normale', 'urgent', 'tres_urgent'];

        return [
            'objet'           => isset($data['objet'])           ? mb_substr(trim((string) $data['objet']),           0, 500) : null,
            'expediteur'      => isset($data['expediteur'])      ? mb_substr(trim((string) $data['expediteur']),      0, 300) : null,
            'destinataire'    => isset($data['destinataire'])    ? mb_substr(trim((string) $data['destinataire']),    0, 300) : null,
            'date_emission'   => $this->sanitizeDate($data['date_emission'] ?? null),
            'numero_emission' => isset($data['numero_emission']) ? mb_substr(trim((string) $data['numero_emission']), 0, 100) : null,
            'urgence'         => in_array($data['urgence'] ?? '', $valid) ? $data['urgence'] : 'normale',
        ];
    }

    private function sanitizeDate(mixed $value): ?string
    {
        if (empty($value)) {
            return null;
        }
        $str = trim((string) $value);
        // Accepter uniquement YYYY-MM-DD
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $str)) {
            return $str;
        }
        // Essayer de parser d'autres formats courants
        try {
            $dt = new \DateTime($str);
            return $dt->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }
}
