<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\User;
use App\Models\Document;
use App\Models\Signature;
use App\Models\Workflow;
use App\Models\DocumentTemplate;
use App\Models\IssuingAdministration;
use App\Models\RecipientAdministration;
use App\Models\DirectionType;
use App\Models\RoutingRule;
use App\Models\AdministrationProfile;
use App\Models\SubEntity;
use App\Models\RequestedAct;
use App\Models\Instruction;
use App\Models\UserDirectionAssignment;
use App\Models\SignatureProviderConfig;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\QueryException;

class AdminController extends Controller
{
    private function isSuperAdminProfile(?AdministrationProfile $profile): bool
    {
        if (!$profile || !is_string($profile->name)) {
            return false;
        }

        $normalized = strtoupper(trim(str_replace(['_', '-'], ' ', $profile->name)));
        $normalized = preg_replace('/\s+/', ' ', $normalized) ?? $normalized;

        return $normalized === 'SUPER ADMIN';
    }

    private function extractOrigin(?string $url): ?string
    {
        $url = trim((string) $url);
        if ($url === '') {
            return null;
        }

        $parts = parse_url(rtrim($url, '/'));
        if (!is_array($parts) || empty($parts['host'])) {
            return null;
        }

        $scheme = $parts['scheme'] ?? 'https';
        $origin = $scheme . '://' . $parts['host'];
        if (!empty($parts['port'])) {
            $origin .= ':' . $parts['port'];
        }

        return $origin;
    }

    private function resolveAppPublicBaseUrl(?string $appPublicUrl, ?string $onlyofficeUrl = null): string
    {
        $basePath = rtrim(request()->getBaseUrl(), '/');
        $requestOrigin = rtrim(request()->getSchemeAndHttpHost(), '/');

        $appOrigin = $this->extractOrigin($appPublicUrl);
        $ooOrigin = $this->extractOrigin($onlyofficeUrl);

        // If app_public_url is empty or mistakenly set to the OnlyOffice server host,
        // fallback to the current application host.
        if (!$appOrigin || ($ooOrigin && strcasecmp($appOrigin, $ooOrigin) === 0)) {
            return $requestOrigin . $basePath;
        }

        $configuredPath = '';
        $appParts = parse_url(rtrim((string) $appPublicUrl, '/'));
        if (is_array($appParts) && !empty($appParts['path'])) {
            $configuredPath = rtrim((string) $appParts['path'], '/');
        }

        // Respecte le path configuré (ex: /public) si présent, sinon fallback sur le basePath de la requête.
        return $appOrigin . ($configuredPath !== '' ? $configuredPath : $basePath);
    }

    /**
     * Résout le périmètre d'administration de l'utilisateur connecté.
     *
     * Retourne ['type' => 'emitter'|'recipient', 'id' => uuid] si l'utilisateur
     * est limité à une administration, ou null si super-admin (accès total).
     */
    private function resolveAdminScope(): ?array
    {
        $user = auth()->user();
        if (!$user) return null;

        $profile = null;
        if ($user->profile_id) {
            $profile = AdministrationProfile::find($user->profile_id);
        }

        // Exception métier: profil applicatif SUPER ADMIN => accès global sans scope.
        if ($this->isSuperAdminProfile($profile)) {
            return null;
        }

        // Super-admin : rôle admin sans profil applicatif
        if ($user->role === 'admin' && !$user->profile_id) {
            return null;
        }

        // Source prioritaire : UserDirectionAssignment (contient type + id)
        $assignment = UserDirectionAssignment::where('user_id', $user->id)->first();
        if ($assignment && $assignment->direction_scope_id) {
            return [
                'type' => $assignment->direction_scope_type,
                'id'   => $assignment->direction_scope_id,
            ];
        }

        // Fallback : profil applicatif → administration émettrice
        if ($profile) {
            if ($profile && $profile->administration_id) {
                return ['type' => 'emitter', 'id' => $profile->administration_id];
            }
        }

        return null;
    }

    public function index(Request $request)
    {
        $tab = $request->get('tab', 'overview');

        $stats = [
            'users'      => User::count(),
            'documents'  => Document::whereNull('deleted_at')->count(),
            'signatures' => Signature::count(),
            'workflows'  => Workflow::count(),
        ];

        $settings       = collect();
        $users          = collect();
        $templates      = collect();
        $emitters       = collect();
        $recipients     = collect();
        $directionTypes = collect();
        $subEntities    = collect();
        $requestedActs  = collect();
        $routingRules   = collect();
        $profiles       = collect();
        $profilesList   = collect();
        $instructions   = collect();
        $allUsers       = collect();
        $shareMap       = [];
        $onlyofficeUrl  = '';
        $onlyofficeSecret = '';
        $dirAssignments = collect();
        $sigProviders   = collect();
        $courrierArchivalDays = 0;

        // Périmètre d'administration de l'utilisateur connecté (null = super-admin)
        $adminScope = $this->resolveAdminScope();

        try {
            $settings = AppSetting::all()->keyBy('key');

            // ── Utilisateurs ─────────────────────────────────────────────────
            $usersQuery = User::latest();
            if ($adminScope) {
                $scopedUserIds = UserDirectionAssignment::where('direction_scope_type', $adminScope['type'])
                    ->where('direction_scope_id', $adminScope['id'])
                    ->pluck('user_id');
                $usersQuery->whereIn('id', $scopedUserIds);
            }
            $users = $usersQuery->paginate(20, ['*'], 'users_page');

            // ── Templates ────────────────────────────────────────────────────
            $tplQuery = DocumentTemplate::with(['variables', 'administration'])->latest();
            if ($adminScope && $adminScope['type'] === 'emitter') {
                $tplQuery->where('administration_id', $adminScope['id']);
            } elseif ($adminScope && $adminScope['type'] === 'recipient') {
                $tplQuery->whereNull('id'); // recipients n'ont pas de templates propres
            }
            $templates = $tplQuery->paginate(200, ['*'], 'tpl_page');

            // ── Administrations émettrices ────────────────────────────────────
            if ($adminScope && $adminScope['type'] === 'emitter') {
                $emitters = IssuingAdministration::where('id', $adminScope['id'])->get();
            } elseif ($adminScope && $adminScope['type'] === 'recipient') {
                $emitters = collect();
            } else {
                $emitters = IssuingAdministration::latest()->get();
            }

            // ── Administrations destinataires ────────────────────────────────
            if ($adminScope && $adminScope['type'] === 'recipient') {
                $recipients = RecipientAdministration::where('id', $adminScope['id'])->get();
            } else {
                $recipients = RecipientAdministration::latest()->get();
            }

            $directionTypes = DirectionType::latest()->get();

            // ── Entités sous tutelle ──────────────────────────────────────────
            $subEntitiesQuery = SubEntity::with('directionType')->latest();
            if ($adminScope) {
                $subEntitiesQuery->where('scope_type', $adminScope['type'])
                                 ->where('scope_id', $adminScope['id']);
            }
            $subEntities = $subEntitiesQuery->get();

            // Compat prod: certaines bases anciennes n'ont pas la colonne `code`.
            $emitterCodeColumn = Schema::hasColumn('issuing_administrations', 'code') ? 'code' : 'name';
            $recipientCodeColumn = Schema::hasColumn('recipient_administrations', 'code') ? 'code' : 'name';

            $emitterCodes   = IssuingAdministration::pluck($emitterCodeColumn, 'id');
            $recipientCodes = RecipientAdministration::pluck($recipientCodeColumn, 'id');
            $subEntities    = $subEntities->map(function (SubEntity $subEntity) use ($emitterCodes, $recipientCodes) {
                $adminCode = $subEntity->scope_type === 'recipient'
                    ? ($recipientCodes[$subEntity->scope_id] ?? null)
                    : ($emitterCodes[$subEntity->scope_id] ?? null);
                $subEntity->setAttribute('administration_code', $adminCode);
                return $subEntity;
            });

            // ── Actes demandés ────────────────────────────────────────────────
            $actsQuery = RequestedAct::with(['administration', 'recipientAdministration'])->latest();
            if ($adminScope && $adminScope['type'] === 'emitter') {
                $actsQuery->where('administration_id', $adminScope['id']);
            } elseif ($adminScope && $adminScope['type'] === 'recipient') {
                $actsQuery->where('recipient_administration_id', $adminScope['id']);
            }
            $requestedActs = $actsQuery->get();

            // ── Règles de routage ─────────────────────────────────────────────
            $routingQuery = RoutingRule::with(['template', 'recipient'])->latest();
            if ($adminScope && $adminScope['type'] === 'emitter') {
                $scopedTplIds = DocumentTemplate::where('administration_id', $adminScope['id'])->pluck('id');
                $routingQuery->whereIn('template_id', $scopedTplIds);
            } elseif ($adminScope && $adminScope['type'] === 'recipient') {
                $routingQuery->where('recipient_id', $adminScope['id']);
            }
            $routingRules = $routingQuery->paginate(15, ['*'], 'routing_page');

            // ── Profils ───────────────────────────────────────────────────────
            $profilesQuery = AdministrationProfile::with('administration')->latest();
            $profilesListQuery = AdministrationProfile::select('id','name','administration_id')->orderBy('name');
            if ($adminScope && $adminScope['type'] === 'emitter') {
                $profilesQuery->where('administration_id', $adminScope['id']);
                $profilesListQuery->where('administration_id', $adminScope['id']);
            } elseif ($adminScope && $adminScope['type'] === 'recipient') {
                $profilesQuery->whereNull('id');
                $profilesListQuery->whereNull('id');
            }
            $profiles     = $profilesQuery->paginate(15, ['*'], 'profiles_page');
            $profilesList = $profilesListQuery->get();

            $instructions   = Instruction::latest()->get();
            $allUsers       = User::select('id','name','email')->latest()->get();
            $shareMapRaw    = AppSetting::where('key', 'template_share_map')->value('value');
            $shareMap       = json_decode($shareMapRaw ?: '{}', true) ?: [];
            $onlyofficeUrl  = AppSetting::where('key', 'onlyoffice_server_url')->value('value') ?: '';
            $onlyofficeSecret = AppSetting::where('key', 'onlyoffice_secret')->value('value') ?: '';

            // ── Assignments de direction ──────────────────────────────────────
            $dirAssignQuery = UserDirectionAssignment::query();
            if ($adminScope) {
                $dirAssignQuery->where('direction_scope_type', $adminScope['type'])
                               ->where('direction_scope_id', $adminScope['id']);
            }
            $dirAssignments = $dirAssignQuery->get()->keyBy('user_id');

            // ── Fournisseurs de signature ─────────────────────────────────────
            $sigQuery = SignatureProviderConfig::query();
            if ($adminScope && $adminScope['type'] === 'emitter') {
                $sigQuery->where('administration_id', $adminScope['id']);
            } elseif ($adminScope && $adminScope['type'] === 'recipient') {
                $sigQuery->whereNull('id');
            }
            $sigProviders = $sigQuery->get()->keyBy('administration_id');

            $courrierArchivalDays = (int) AppSetting::where('key', 'courrier_archival_days')->value('value');
        } catch (\Throwable $e) {
            if ($tab !== 'emitters') {
                throw $e;
            }

            Log::warning('Admin emitters tab fallback due to missing dependency', [
                'message' => $e->getMessage(),
            ]);

            try {
                $settings = AppSetting::all()->keyBy('key');
            } catch (\Throwable $ignored) {
                $settings = collect();
            }

            try {
                $emitters = IssuingAdministration::latest()->get();
            } catch (\Throwable $ignored) {
                $emitters = collect();
            }
        }

        // Génération du JWT OnlyOffice (HS256) sans dépendance externe
        $onlyofficeJwt = '';
        $appPublicUrl  = AppSetting::where('key', 'app_public_url')->value('value') ?: '';
        if ($onlyofficeSecret) {
            $base   = $this->resolveAppPublicBaseUrl($appPublicUrl, $onlyofficeUrl ?? '');
            $docUrl = $base . '/oo-blank/docx';
            $payload = [
                'document' => [
                    'fileType' => 'docx',
                    'key'      => 'tpl-editor-' . time(),
                    'title'    => 'Nouveau modele',
                    'url'      => $docUrl,
                    'permissions' => ['edit' => true, 'download' => false, 'print' => false],
                ],
                'documentType' => 'word',
                'editorConfig' => [
                    'mode' => 'edit',
                    'lang' => 'fr',
                    'callbackUrl' => '',
                    'user' => ['id' => 'admin-' . auth()->id(), 'name' => auth()->user()->name ?? 'Admin'],
                    'customization' => [
                        'autosave' => false,
                        'compactHeader' => true,
                        'hideRightMenu' => true,
                        'forcesave' => false,
                    ],
                ],
            ];
            $header    = rtrim(strtr(base64_encode(json_encode(['alg'=>'HS256','typ'=>'JWT'])), '+/', '-_'), '=');
            $body      = rtrim(strtr(base64_encode(json_encode($payload)), '+/', '-_'), '=');
            $signature = rtrim(strtr(base64_encode(hash_hmac('sha256', "$header.$body", $onlyofficeSecret, true)), '+/', '-_'), '=');
            $onlyofficeJwt = "$header.$body.$signature";
        }

        return view('admin.index', compact(
            'tab', 'stats', 'settings', 'users',
            'templates', 'emitters', 'recipients',
            'directionTypes', 'subEntities', 'requestedActs', 'routingRules', 'profiles', 'profilesList',
            'instructions',
            'allUsers', 'shareMap', 'onlyofficeUrl', 'onlyofficeJwt', 'appPublicUrl', 'dirAssignments',
            'sigProviders', 'courrierArchivalDays', 'adminScope'
        ));
    }

    // ── Token OnlyOffice (API, génère un JWT frais pour un document) ──────────
    public function onlyofficeToken(Request $request)
    {
        $onlyofficeSecret = AppSetting::where('key', 'onlyoffice_secret')->value('value') ?: '';
        if (!$onlyofficeSecret) {
            return response()->json(['token' => '', 'docUrl' => ''], 200);
        }

        $appPublicUrl = AppSetting::where('key', 'app_public_url')->value('value') ?: '';
        $onlyofficeUrl = AppSetting::where('key', 'onlyoffice_server_url')->value('value') ?: '';
        $docKey       = $request->input('key', 'doc-' . time());
        $docUrl       = $request->input('url', '');
        $docType      = $request->input('fileType', 'docx');
        $docTitle     = $request->input('title', 'Document');

        // Si aucune URL fournie, utiliser la route publique /oo-blank/docx
        if (!$docUrl) {
            $base   = $this->resolveAppPublicBaseUrl($appPublicUrl, $onlyofficeUrl);
            $docUrl = $base . '/oo-blank/' . $docType;
        }

        $payload = [
            'document' => [
                'fileType' => $docType,
                'key'      => $docKey,
                'title'    => $docTitle,
                'url'      => $docUrl,
                'permissions' => ['edit' => true, 'download' => false, 'print' => false],
            ],
            'documentType' => $docType === 'xlsx' ? 'cell' : ($docType === 'pptx' ? 'slide' : 'word'),
            'editorConfig' => [
                'mode'        => 'edit',
                'lang'        => 'fr',
                'callbackUrl' => $request->input('callbackUrl', ''),
                'user'        => ['id' => 'u-' . auth()->id(), 'name' => auth()->user()->name ?? 'Utilisateur'],
                'customization' => [
                    'autosave'      => true,
                    'compactHeader' => true,
                    'hideRightMenu' => true,
                    'forcesave'     => false,
                ],
            ],
        ];

        $header    = rtrim(strtr(base64_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT'])), '+/', '-_'), '=');
        $body      = rtrim(strtr(base64_encode(json_encode($payload)), '+/', '-_'), '=');
        $signature = rtrim(strtr(base64_encode(hash_hmac('sha256', "$header.$body", $onlyofficeSecret, true)), '+/', '-_'), '=');

        return response()->json(['token' => "$header.$body.$signature", 'docUrl' => $docUrl]);
    }

    // ── Upload d'un fichier template ──────────────────────────────────────────
    public function uploadTemplateFile(Request $request)
    {
        $request->validate([
            'file' => [
                'required', 'file', 'max:20480',
                function ($attribute, $value, $fail) {
                    $ext = strtolower($value->getClientOriginalExtension());
                    if (!in_array($ext, ['pdf', 'docx', 'xlsx', 'pptx'])) {
                        $fail('Le fichier doit être de type PDF, DOCX, XLSX ou PPTX.');
                    }
                },
            ],
            'name'              => 'required|string|max:255',
            'administration_id' => 'nullable|string',
            'template_id'       => 'nullable|string',
        ]);

        $file      = $request->file('file');
        $ext       = strtolower($file->getClientOriginalExtension());
        $origName  = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeSlug  = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $origName);
        $fileName  = $safeSlug . '_' . time() . '.' . $ext;

        // Stocker dans storage/app/public/templates/
        $storedPath = $file->storeAs('templates', $fileName, 'public');

        $targetTemplateId = (string) ($request->input('template_id') ?: '');
        $template = null;
        if ($targetTemplateId !== '') {
            $template = DocumentTemplate::find($targetTemplateId);
        }

        if ($template) {
            $template->name = (string) $request->input('name');
            $template->file_name = (string) $file->getClientOriginalName();
            $template->file_type = (string) $ext;
            $template->storage_path = (string) $storedPath;
            $template->administration_id = $request->input('administration_id') ?: null;
            $template->save();
        } else {
            // Créer le template en base
            $template = DocumentTemplate::create([
                'name'              => $request->input('name'),
                'file_name'         => $file->getClientOriginalName(),
                'file_type'         => $ext,
                'storage_path'      => $storedPath,
                'content'           => '',
                'administration_id' => $request->input('administration_id') ?: null,
                'created_by'        => auth()->id(),
            ]);
        }

        // ── Auto-extraction des variables {{}} depuis le fichier Office ────────
        $extractedVars = [];
        if (in_array($ext, ['docx', 'xlsx', 'pptx'])) {
            $absPath = \Illuminate\Support\Facades\Storage::disk('public')->path($storedPath);
            $extractedVars = $this->extractVarsFromUploadedFile($absPath);
            foreach ($extractedVars as $v) {
                \App\Models\TemplateVariable::firstOrCreate(
                    ['template_id' => $template->id, 'key' => $v['key']],
                    [
                        'label'         => $v['label'],
                        'field_type'    => 'text',
                        'required'      => false,
                        'placeholder'   => '',
                        'default_value' => '',
                        'options'       => json_encode([]),
                    ]
                );
            }
        }

        // Générer la config OnlyOffice pour ce fichier
        $onlyofficeSecret = AppSetting::where('key', 'onlyoffice_secret')->value('value') ?: '';
        $onlyofficeUrl    = preg_replace('/\s+/', '', (string) (AppSetting::where('key', 'onlyoffice_server_url')->value('value') ?: ''));
        $appPublicUrl     = preg_replace('/\s+/', '', (string) (AppSetting::where('key', 'app_public_url')->value('value') ?: ''));

        $docKey   = 'tpl-up-' . $template->id . '-' . time();
        $docType  = $ext === 'xlsx' ? 'cell' : ($ext === 'pptx' ? 'slide' : ($ext === 'pdf' ? 'pdf' : 'word'));

        // Compute paths BEFORE building docUrl so basePath is always included
        $storagePubPath = \Illuminate\Support\Facades\Storage::url($storedPath); // ex: /storage/templates/file.pdf
        $basePath       = rtrim($request->getBaseUrl(), '/');                     // ex: /e-administration_laravel/public
        $localDocUrl    = rtrim($request->getSchemeAndHttpHost(), '/') . $basePath . $storagePubPath;

        $baseForDocUrl = $this->resolveAppPublicBaseUrl($appPublicUrl, $onlyofficeUrl);
        $docUrl = $baseForDocUrl . $storagePubPath;

        $token = '';
        if ($onlyofficeSecret) {
            $payload = [
                'document' => [
                    'fileType' => $ext,
                    'key'      => $docKey,
                    'title'    => $template->name,
                    'url'      => $docUrl,
                    'permissions' => ['edit' => true, 'download' => false, 'print' => false],
                ],
                'documentType' => $docType,
                'editorConfig' => [
                    'mode'        => 'edit',
                    'lang'        => 'fr',
                    'callbackUrl' => '',
                    'user'        => ['id' => 'admin-' . auth()->id(), 'name' => auth()->user()->name ?? 'Admin'],
                    'customization' => ['autosave' => true, 'compactHeader' => true, 'hideRightMenu' => true],
                ],
            ];
            $header    = rtrim(strtr(base64_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT'])), '+/', '-_'), '=');
            $body      = rtrim(strtr(base64_encode(json_encode($payload)), '+/', '-_'), '=');
            $signature = rtrim(strtr(base64_encode(hash_hmac('sha256', "$header.$body", $onlyofficeSecret, true)), '+/', '-_'), '=');
            $token = "$header.$body.$signature";
        }

        return response()->json([
            'success'        => true,
            'template_id'    => $template->id,
            'template_name'  => $template->name,
            'ooUrl'          => $onlyofficeUrl,
            'token'          => $token,
            'docUrl'         => $docUrl,
            'localDocUrl'    => $localDocUrl,
            'storagePubPath' => $storagePubPath,
            'docKey'         => $docKey,
            'documentType'   => $docType,
            'fileType'       => $ext,
            'variables'      => $extractedVars,
            'variables_count'=> count($extractedVars),
        ]);
    }

    // ── Paramètres ────────────────────────────────────────────────────────────

    /** Liste blanche des clés de paramètre autorisées via le formulaire. */
    private const ALLOWED_SETTING_KEYS = [
        'app_name', 'app_public_url', 'app_logo',
        'onlyoffice_server_url', 'onlyoffice_secret', 'onlyoffice_doc_viewer',
        'mail_from_address', 'mail_from_name',
        'qr_image_page', 'qr_image_x', 'qr_image_y', 'qr_image_width', 'qr_image_height',
        'signature_qr_position',
        'theme_primary_color', 'theme_secondary_color', 'theme_logo',
        'courrier_archival_days',
        'email_notifications_enabled', 'email_notifications_from',
    ];

    public function updateSettings(Request $request)
    {
        // Seules les clés de la liste blanche sont acceptées
        $data = array_filter(
            $request->except('_token', '_method', 'tab'),
            fn($key) => in_array($key, self::ALLOWED_SETTING_KEYS, true),
            ARRAY_FILTER_USE_KEY
        );

        foreach (['app_public_url', 'onlyoffice_server_url'] as $urlKey) {
            if (array_key_exists($urlKey, $data) && is_string($data[$urlKey])) {
                $data[$urlKey] = preg_replace('/\s+/', '', trim($data[$urlKey]));
            }
        }

        foreach ($data as $key => $value) {
            AppSetting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        // Keep signature QR position in sync when edited from OnlyOffice settings.
        if (
            $request->has('qr_image_page') ||
            $request->has('qr_image_x') ||
            $request->has('qr_image_y') ||
            $request->has('qr_image_width') ||
            $request->has('qr_image_height')
        ) {
            $existing = AppSetting::where('key', 'signature_qr_position')->value('value');
            $existingDecoded = json_decode((string) $existing, true);
            if (!is_array($existingDecoded)) {
                $existingDecoded = [];
            }

            $qrPosition = json_encode([
                'imagePage' => (int) $request->input('qr_image_page', $existingDecoded['imagePage'] ?? -1),
                'imageX' => (int) $request->input('qr_image_x', $existingDecoded['imageX'] ?? 390),
                'imageY' => (int) $request->input('qr_image_y', $existingDecoded['imageY'] ?? 710),
                'imageWidth' => (int) $request->input('qr_image_width', $existingDecoded['imageWidth'] ?? 150),
                'imageHeight' => (int) $request->input('qr_image_height', $existingDecoded['imageHeight'] ?? 80),
            ]);

            AppSetting::updateOrCreate(
                ['key' => 'signature_qr_position'],
                ['value' => $qrPosition, 'description' => 'Position QR sur document PDF']
            );
        }

        return back()->with('success', 'Paramètres enregistrés.')->withInput(['tab' => $request->input('tab', 'settings')]);
    }

    // ── API Signature ─────────────────────────────────────────────────────────
    public function saveSignatureProvider(Request $request)
    {
        $administrationId = $request->input('sig_admin_id');
        $adminType        = $request->input('sig_admin_type', 'emitter');

        if (!$administrationId) {
            return back()->withErrors(['sig_admin_id' => 'Sélectionnez une administration.'])
                         ->withInput()->with('tab', 'signature-provider');
        }

        SignatureProviderConfig::updateOrCreate(
            ['administration_id' => $administrationId, 'administration_type' => $adminType],
            [
                'is_active'                => (bool) $request->input('is_active', false),
                'endpoint'                 => rtrim(trim($request->input('endpoint', '')), '/'),
                'sign_path'                => $request->input('sign_path', '/v1/sign'),
                'api_key'                  => $request->input('api_key', ''),
                'tenant_id'                => trim($request->input('tenant_id', '')),
                'consent_page_id'          => trim($request->input('consent_page_id', '')),
                'consent_page_id_approval' => trim($request->input('consent_page_id_approval', '')),
                'signature_profile_id'     => trim($request->input('signature_profile_id', '')),
                'provider_owner_user_id'   => trim($request->input('provider_owner_user_id', '')),
                'verify_ssl'               => (bool) $request->input('verify_ssl', true),
                'timeout_ms'               => (int) $request->input('timeout_ms', 30000),
            ]
        );

        // Backward compatibility: if QR fields are posted from this form, persist them.
        if (
            $request->has('qr_page') ||
            $request->has('qr_x') ||
            $request->has('qr_y') ||
            $request->has('qr_width') ||
            $request->has('qr_height')
        ) {
            $qrPosition = json_encode([
                'imagePage'   => (int) $request->input('qr_page', -1),
                'imageX'      => (int) $request->input('qr_x', 390),
                'imageY'      => (int) $request->input('qr_y', 710),
                'imageWidth'  => (int) $request->input('qr_width', 150),
                'imageHeight' => (int) $request->input('qr_height', 80),
            ]);
            AppSetting::updateOrCreate(
                ['key' => 'signature_qr_position'],
                ['value' => $qrPosition, 'description' => 'Position QR sur document PDF']
            );
        }

        return redirect()->route('admin.index', [
                'tab'           => 'signature-provider',
                'sig_admin_type'=> $adminType,
                'sig_admin_id'  => $administrationId,
            ])
            ->with('sig_success', 'Configuration API Signature enregistrée avec succès.');
    }

    // ── Test connexion API Signature ──────────────────────────────────────────
    public function testSignatureConnection(Request $request): \Illuminate\Http\JsonResponse
    {
        $endpoint = rtrim(trim($request->input('endpoint', '')), '/');
        $apiKey   = trim($request->input('api_key', ''));
        $email    = trim($request->input('email', ''));

        if ($endpoint === '' || $apiKey === '') {
            return response()->json(['ok' => false, 'message' => 'Endpoint et API Key requis.'], 422);
        }

        try {
            // 1. Vérifier la connexion via /api/users/me (clé API)
            $response = Http::withToken($apiKey)
                ->timeout(10)
                ->get($endpoint . '/api/users/me');

            if (!$response->successful()) {
                return response()->json([
                    'ok'      => false,
                    'message' => 'Erreur API (' . $response->status() . '): ' . ($response->json('message') ?? $response->body()),
                ], 422);
            }

            $data     = $response->json();
            $tenantId = $data['tenantId'] ?? null;
            $result   = [
                'ok'        => true,
                'message'   => 'Connexion réussie.',
                'tenant_id' => $tenantId,
            ];

            // 2. Si un email est fourni, chercher l'utilisateur par email
            if ($email !== '') {
                $cfg = new \App\Models\SignatureProviderConfig([
                    'endpoint'   => $endpoint,
                    'api_key'    => $apiKey,
                    'verify_ssl' => true,
                    'timeout_ms' => 10000,
                ]);

                // Invalider le cache pour cet email afin de forcer une recherche fraîche
                $cacheKey = 'sunnystamp_uid_' . md5($endpoint . '|' . $email);
                \Illuminate\Support\Facades\Cache::forget($cacheKey);

                $platformUserId = \App\Http\Controllers\SignatureController::resolvePlatformUserIdByEmail($cfg, $email);

                $result['platform_user_id'] = $platformUserId;
                $result['email']            = $email;
                if ($platformUserId) {
                    $result['message'] = 'Connexion réussie. Utilisateur trouvé sur la plateforme.';
                } else {
                    $result['message'] = 'Connexion réussie, mais aucun utilisateur trouvé pour l\'e-mail "' . $email . '" sur la plateforme.';
                }
            }

            return response()->json($result);

        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => 'Impossible de joindre le serveur: ' . $e->getMessage()], 422);
        }
    }

    // ── Apparence (theming) ───────────────────────────────────────────────────
    public function saveTheming(Request $request)
    {
        $tType = $request->input('t_type', 'emitter');
        $tId   = $request->input('t_id', '');

        if (!$tId) {
            return back()->withErrors(['t_id' => 'Sélectionnez une administration.'])
                         ->withInput()->with('tab', 'theming');
        }

        $prefix = "theme_{$tType}_{$tId}_";

        $textFields = [
            'app_name'            => $request->input('app_name', ''),
            'web_url'             => $request->input('web_url', ''),
            'slogan'              => $request->input('slogan', ''),
            'menu_color'          => $request->input('menu_color', '#173b9f'),
            'bg_color'            => $request->input('bg_color', '#495F55'),
            'legal_notice_url'    => $request->input('legal_notice_url', ''),
            'privacy_policy_url'  => $request->input('privacy_policy_url', ''),
            'disable_user_theming'=> $request->input('disable_user_theming', 'false'),
        ];

        foreach ($textFields as $suffix => $value) {
            AppSetting::updateOrCreate(
                ['key' => $prefix . $suffix],
                ['value' => $value, 'description' => 'Theming ' . $tType . ' ' . $tId]
            );
        }

        // Synchronise la couleur de menu globale
        AppSetting::updateOrCreate(
            ['key' => 'theme_menu_color'],
            ['value' => $request->input('menu_color', '#173b9f'), 'description' => 'Couleur globale du menu']
        );

        $fileFields = [
            'logo_file'        => 'logo',
            'bg_image_file'    => 'login_background_image',
            'header_logo_file' => 'header_logo',
            'favicon_file'     => 'favicon',
        ];

        foreach ($fileFields as $inputName => $suffix) {
            if ($request->hasFile($inputName) && $request->file($inputName)->isValid()) {
                $path = $request->file($inputName)->store("theming/{$tType}/{$tId}", 'public');
                AppSetting::updateOrCreate(
                    ['key' => $prefix . $suffix],
                    ['value' => $path, 'description' => 'Theming ' . $tType . ' ' . $tId]
                );
                if ($suffix === 'login_background_image') {
                    AppSetting::updateOrCreate(
                        ['key' => 'theme_login_background_image'],
                        ['value' => $path, 'description' => 'Image globale de fond de connexion']
                    );
                }
            }
        }

        return redirect()->route('admin.index', ['tab' => 'theming', 't_type' => $tType, 't_id' => $tId])
                         ->with('theming_success', 'Les paramètres d\'apparence ont été enregistrés avec succès.');
    }

    // ── Émetteurs ─────────────────────────────────────────────────────────────
    private function extractEmitterMetadata(Request $request): array
    {
        return [
            'adminType'         => $request->input('admin_type', ''),
            'sector'            => $request->input('sector', ''),
            'description'       => $request->input('description', ''),
            'contactEmail'      => $request->input('contact_email', ''),
            'techEmail'         => $request->input('tech_email', ''),
            'contactPhone'      => $request->input('contact_phone', ''),
            'referentMetier'    => $request->input('referent_metier', ''),
            'postalAddress'     => $request->input('postal_address', ''),
            'transmissionMethod'=> $request->input('transmission_method', 'api'),
            'endpointUrl'       => $request->input('endpoint_url', ''),
            'dataFormat'        => $request->input('data_format', 'json'),
            'authMethod'        => $request->input('auth_method', 'api_key'),
            'apiKey'            => $request->input('api_key', ''),
            'timeout'           => (int)$request->input('timeout', 30),
            'requireTls'        => $request->boolean('require_tls', true),
            'enableRetry'       => $request->boolean('enable_retry', true),
            'docTypes'          => (array)$request->input('doc_types', ['pdf']),
            'defaultWorkflow'   => $request->input('default_workflow', ''),
            'dossierPrefix'     => $request->input('dossier_prefix', ''),
            'autoConvertPdf'    => $request->boolean('auto_convert_pdf', true),
            'requiredMetadata'  => $request->input('required_metadata', ''),
            'signatureLevel'    => $request->input('signature_level', 'qualifiee'),
            'logRetention'      => (int)$request->input('log_retention', 365),
            'businessHours'     => $request->input('business_hours', ''),
            'slaResponse'       => $request->input('sla_response', '24h'),
            'timezone'          => $request->input('timezone', 'Europe/Paris'),
            'duplicateHandling' => $request->input('duplicate_handling', 'update'),
            'gdprCompliant'     => $request->boolean('gdpr_compliant', true),
            'enableAudit'       => $request->boolean('enable_audit', true),
            'fileEncryption'    => $request->boolean('file_encryption', false),
            'ipWhitelist'       => $request->input('ip_whitelist', ''),
            'externalRefField'  => $request->input('external_ref_field', ''),
            'trackingUrl'       => $request->input('tracking_url', ''),
            'webhookUrl'        => $request->input('webhook_url', ''),
            'webhookSecret'     => $request->input('webhook_secret', ''),
            'tags'              => $request->input('tags', ''),
        ];
    }

    private function issuingAdministrationHasSubEntityCode(): bool
    {
        try {
            return Schema::hasColumn('issuing_administrations', 'sub_entity_code');
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function storeEmitter(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:issuing_administrations,code',
        ]);

        $logoPath = null;
        if ($request->hasFile('logo_file')) {
            $file = $request->file('logo_file');
            $filename = uniqid('logo_') . '.' . $file->getClientOriginalExtension();
            $logoDir = public_path('images/logos');
            if (!is_dir($logoDir)) {
                mkdir($logoDir, 0755, true);
            }
            $file->move(public_path('images/logos'), $filename);
            $logoPath = 'images/logos/' . $filename;
        }

        $payload = [
            'id'              => Str::uuid(),
            'name'            => $request->input('name'),
            'code'            => strtoupper($request->input('code')),
            'is_active'       => $request->boolean('is_active', true),
            'logo'            => $logoPath,
            'metadata'        => $this->extractEmitterMetadata($request),
        ];

        if ($this->issuingAdministrationHasSubEntityCode()) {
            $payload['sub_entity_code'] = strtoupper((string) $request->input('sub_entity_code', ''));
        }

        IssuingAdministration::create($payload);
        return redirect()->route('admin.index', ['tab' => 'emitters'])
            ->with('success', 'Administration émettrice créée.');
    }

    public function updateEmitter(Request $request, IssuingAdministration $emitter)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:issuing_administrations,code,' . $emitter->id,
        ]);

        $logoPath = $emitter->logo;
        if ($request->hasFile('logo_file')) {
            $file = $request->file('logo_file');
            $filename = uniqid('logo_') . '.' . $file->getClientOriginalExtension();
            $logoDir = public_path('images/logos');
            if (!is_dir($logoDir)) {
                mkdir($logoDir, 0755, true);
            }
            $file->move(public_path('images/logos'), $filename);
            $logoPath = 'images/logos/' . $filename;
        }

        $payload = [
            'name'            => $request->input('name'),
            'code'            => strtoupper($request->input('code')),
            'is_active'       => $request->boolean('is_active', true),
            'logo'            => $logoPath,
            'metadata'        => $this->extractEmitterMetadata($request),
        ];

        if ($this->issuingAdministrationHasSubEntityCode()) {
            $payload['sub_entity_code'] = strtoupper((string) $request->input('sub_entity_code', ''));
        }

        $emitter->update($payload);
        return redirect()->route('admin.index', ['tab' => 'emitters'])
            ->with('success', 'Administration émettrice mise à jour.');
    }

    public function destroyEmitter(IssuingAdministration $emitter)
    {
        $emitter->delete();
        return back()->with('success', 'Émetteur supprimé.')->withInput(['tab' => 'emitters']);
    }

    // ── Destinataires ─────────────────────────────────────────────────────────
    private function extractRecipientMetadata(Request $request): array
    {
        return [
            'adminType'          => $request->input('admin_type', ''),
            'sector'             => $request->input('sector', ''),
            'description'        => $request->input('description', ''),
            'contactEmail'       => $request->input('contact_email', ''),
            'techEmail'          => $request->input('tech_email', ''),
            'contactPhone'       => $request->input('contact_phone', ''),
            'contactFax'         => $request->input('contact_fax', ''),
            'referentMetier'     => $request->input('referent_metier', ''),
            'referentTechnique'  => $request->input('referent_technique', ''),
            'postalAddress'      => $request->input('postal_address', ''),
            // Méthode de réception
            'apiEndpoint'        => $request->input('api_endpoint_meta', ''),
            'apiMethod'          => $request->input('api_method', 'POST'),
            'apiFormat'          => $request->input('api_format', 'multipart'),
            'apiAuth'            => $request->input('api_auth', 'api_key'),
            'apiTimeout'         => (int)$request->input('api_timeout', 30),
            'emailAddress'       => $request->input('email_address_meta', ''),
            'emailSubject'       => $request->input('email_subject', ''),
            'emailBody'          => $request->input('email_body', ''),
            'lerProvider'        => $request->input('ler_provider', 'laposte'),
            'lerAccountId'       => $request->input('ler_account_id', ''),
            // Documents acceptés
            'docTypes'           => (array)$request->input('doc_types', ['pdf']),
            'maxFileSize'        => (int)$request->input('max_file_size', 50),
            'maxFiles'           => (int)$request->input('max_files', 10),
            'enableRetry'        => $request->boolean('enable_retry', true),
            'enableNotification' => $request->boolean('enable_notification', true),
            'compressFiles'      => $request->boolean('compress_files', false),
            'encryptFiles'       => $request->boolean('encrypt_files', false),
            // Accusé de réception
            'receiptMethod'      => $request->input('receipt_method', 'automatic'),
            'receiptTimeout'     => (int)$request->input('receipt_timeout', 24),
            'receiptWebhookUrl'  => $request->input('receipt_webhook_url', ''),
            'activateImmediately'=> $request->boolean('activate_immediately', true),
        ];
    }

    public function storeRecipient(Request $request)
    {
        $request->validate([
            'name'    => 'required|string|max:255',
            'channel' => 'required|in:api,email,ler,application',
        ]);

        $logoPath = null;
        if ($request->hasFile('logo_file')) {
            $file = $request->file('logo_file');
            $filename = uniqid('logo_') . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('images/logos'), $filename);
            $logoPath = 'images/logos/' . $filename;
        }

        RecipientAdministration::create([
            'id'            => Str::uuid(),
            'name'          => $request->input('name'),
            'code'          => strtoupper($request->input('code', '')),
            'channel'       => $request->input('channel'),
            'email_address' => $request->input('email_address_meta'),
            'api_endpoint'  => $request->input('api_endpoint_meta'),
            'is_active'     => $request->boolean('is_active', true),
            'logo'          => $logoPath,
            'metadata'      => $this->extractRecipientMetadata($request),
        ]);
        return redirect()->route('admin.index', ['tab' => 'recipients'])
            ->with('success', 'Administration destinataire créée.');
    }

    public function updateRecipient(Request $request, RecipientAdministration $recipient)
    {
        $request->validate([
            'name'    => 'required|string|max:255',
            'channel' => 'required|in:api,email,ler,application',
        ]);

        $logoPath = $recipient->logo;
        if ($request->hasFile('logo_file')) {
            $file = $request->file('logo_file');
            $filename = uniqid('logo_') . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('images/logos'), $filename);
            $logoPath = 'images/logos/' . $filename;
        }

        $recipient->update([
            'name'          => $request->input('name'),
            'code'          => strtoupper($request->input('code', '')),
            'channel'       => $request->input('channel'),
            'email_address' => $request->input('email_address_meta'),
            'api_endpoint'  => $request->input('api_endpoint_meta'),
            'is_active'     => $request->boolean('is_active', true),
            'logo'          => $logoPath,
            'metadata'      => $this->extractRecipientMetadata($request),
        ]);
        return redirect()->route('admin.index', ['tab' => 'recipients'])
            ->with('success', 'Administration destinataire mise à jour.');
    }

    public function destroyRecipient(RecipientAdministration $recipient)
    {
        $recipient->delete();
        return back()->with('success', 'Destinataire supprimé.')->withInput(['tab' => 'recipients']);
    }

    // ── Types de direction ────────────────────────────────────────────────────
    public function storeDirectionType(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|max:255', 'description' => 'nullable|string']);
        DirectionType::create(['id' => Str::uuid()] + $data);
        return back()->with('success', 'Type de direction créé.')->withInput(['tab' => 'direction-types']);
    }

    public function updateDirectionType(Request $request, DirectionType $directionType)
    {
        $data = $request->validate(['name' => 'required|string|max:255', 'description' => 'nullable|string']);
        $directionType->update($data);
        return back()->with('success', 'Type de direction mis à jour.')->withInput(['tab' => 'direction-types']);
    }

    public function destroyDirectionType(DirectionType $directionType)
    {
        $directionType->delete();
        return back()->with('success', 'Type de direction supprimé.')->withInput(['tab' => 'direction-types']);
    }

    // ── Templates ─────────────────────────────────────────────────────────────
    // ── Config OnlyOffice pour un template existant ───────────────────────────
    public function getTemplateOoConfig(DocumentTemplate $template)
    {
        $onlyofficeSecret = AppSetting::where('key', 'onlyoffice_secret')->value('value') ?: '';
        $onlyofficeUrl    = AppSetting::where('key', 'onlyoffice_server_url')->value('value') ?: '';
        $appPublicUrl     = AppSetting::where('key', 'app_public_url')->value('value') ?: '';

        $ext = $template->file_type ?: 'docx';
        $ext = strtolower(ltrim($ext, '.'));

        if ($ext !== 'pdf' && !$onlyofficeUrl) {
            return response()->json([
                'success' => false,
                'error' => 'Le serveur OnlyOffice n\'est pas configuré. Renseignez l\'adresse du ONLYOFFICE Docs dans l\'onglet OnlyOffice.',
            ], 422);
        }

        // Construire l'URL du document
        if ($template->storage_path) {
            // Résoudre selon l'emplacement réel du fichier
            if (str_starts_with($template->storage_path, 'images/')) {
                $absCheck = public_path($template->storage_path);
                $exists   = file_exists($absCheck);
                $storagePubPath = '/' . ltrim($template->storage_path, '/');
            } else {
                $exists = \Illuminate\Support\Facades\Storage::disk('public')->exists($template->storage_path);
                $storagePubPath = \Illuminate\Support\Facades\Storage::url($template->storage_path);
            }
        } else {
            $exists = false;
            $storagePubPath = null;
        }

        // Si un storage_path est défini mais le fichier est introuvable, ne pas
        // basculer silencieusement vers un template vierge: remonter une erreur claire.
        if (!$exists && !empty($template->storage_path)) {
            \Log::warning('OO getTemplateOoConfig file missing for existing storage_path', [
                'template_id' => (string) $template->id,
                'storage_path' => (string) $template->storage_path,
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Le fichier du modèle est introuvable sur le serveur. Réimportez le modèle avant ouverture dans OnlyOffice.',
            ], 422);
        }

        // Fallback robuste: créer un fichier bootstrap local seulement si le template Office
        // n'a vraiment aucun fichier associé.
        if (!$exists && empty($template->storage_path) && in_array($ext, ['docx', 'xlsx', 'pptx'], true)) {
            $this->ensureTemplateBootstrapFile($template->fresh() ?: $template);
            $template = $template->fresh() ?: $template;
            if ($template->storage_path) {
                if (str_starts_with($template->storage_path, 'images/')) {
                    $absCheck = public_path($template->storage_path);
                    $exists = file_exists($absCheck);
                    $storagePubPath = '/' . ltrim($template->storage_path, '/');
                } else {
                    $exists = \Illuminate\Support\Facades\Storage::disk('public')->exists($template->storage_path);
                    $storagePubPath = \Illuminate\Support\Facades\Storage::url($template->storage_path);
                }
            }
        }

        // Compute path prefix from the current request (e.g. /e-administration_laravel/public)
        $reqBasePath = rtrim(request()->getBaseUrl(), '/');
        $localDocUrl = $storagePubPath ? rtrim(request()->getSchemeAndHttpHost(), '/') . $reqBasePath . $storagePubPath : null;

        $base = $this->resolveAppPublicBaseUrl($appPublicUrl, $onlyofficeUrl);

        $buildDocUrl = function (string $baseUrl) use ($exists, $ext, $storagePubPath, $template): string {
            if (!$exists) {
                $blankMap = ['docx' => 'docx', 'xlsx' => 'xlsx', 'pptx' => 'pptx'];
                $blankType = $blankMap[$ext] ?? 'docx';
                return $baseUrl . '/oo-blank/' . $blankType;
            }

            if ($storagePubPath && str_starts_with((string) $template->storage_path, 'images/')) {
                return $baseUrl . $storagePubPath;
            }

            $expires = time() + 900;
            $access  = hash_hmac('sha256', 'tplfile|' . $template->id . '|' . $expires, (string) config('app.key'));
            return $baseUrl . '/api/oo-file/template/' . $template->id . '?expires=' . $expires . '&access=' . $access;
        };

        $docUrl = $buildDocUrl($base);

        $docUrl = preg_replace('/\s+/', '', (string) $docUrl);

        \Log::info('OO getTemplateOoConfig docUrl=' . $docUrl . ' template=' . $template->id);

        $docUrlAccessError = $this->validateOnlyofficeAccessibleUrl($docUrl);
        $docUrlAccessWarning = null;

        // Fallback automatique: si l'URL configurée contient /public et répond 404,
        // réessayer avec la même URL sans /public.
        if (
            $docUrlAccessError
            && str_contains($docUrlAccessError, ' répond 404 ')
            && str_ends_with($base, '/public')
        ) {
            $fallbackBase = substr($base, 0, -7);
            if ($fallbackBase !== '') {
                $fallbackDocUrl = preg_replace('/\s+/', '', (string) $buildDocUrl($fallbackBase));
                $fallbackError = $this->validateOnlyofficeAccessibleUrl($fallbackDocUrl);
                if (!$fallbackError) {
                    $oldBase = $base;
                    $base = $fallbackBase;
                    $docUrl = $fallbackDocUrl;
                    $docUrlAccessError = null;
                    \Log::warning('OO getTemplateOoConfig fallback base sans /public utilisé', [
                        'template' => $template->id,
                        'old_base' => $oldBase,
                        'new_base' => $fallbackBase,
                    ]);
                    \Log::info('OO getTemplateOoConfig docUrl fallback=' . $docUrl . ' template=' . $template->id);
                }
            }
        }

        if ($docUrlAccessError) {
            // Ne bloque pas l'éditeur sur timeout réseau (ngrok / tunnel instable).
            // On laisse OnlyOffice tenter le chargement réel du document.
            if (str_contains($docUrlAccessError, 'cURL error 28') || str_contains($docUrlAccessError, 'Operation timed out')) {
                $docUrlAccessWarning = $docUrlAccessError;
            } else {
                return response()->json([
                    'success' => false,
                    'error' => $docUrlAccessError,
                    'docUrl' => $docUrl,
                ], 422);
            }
        }

        $docKey  = 'tpl-' . $template->id . '-' . time();
        $docType = $ext === 'xlsx' ? 'cell' : ($ext === 'pptx' ? 'slide' : ($ext === 'pdf' ? 'pdf' : 'word'));

        $tplHmac     = hash_hmac('sha256', 'cb|tpl|' . $template->id, (string) config('app.key'));
        $callbackUrl = $base . '/api/oo-callback/template/' . $template->id . '?access=' . $tplHmac;

        $userId   = 'admin-' . auth()->id();
        $userName = auth()->user()->name ?? 'Admin';

        // Config EXACTE qui sera passée à DocsAPI — le JWT signe exactement ce payload
        $ooConfigPayload = [
            'document' => [
                'fileType'    => $ext,
                'key'         => $docKey,
                'title'       => $template->name,
                'url'         => $docUrl,
                'permissions' => ['download' => false, 'edit' => true, 'print' => false],
            ],
            'documentType' => $docType,
            'editorConfig' => [
                'callbackUrl'   => $callbackUrl,
                'lang'          => 'fr',
                'mode'          => 'edit',
                'user'          => ['id' => $userId, 'name' => $userName],
                'customization' => [
                    'autosave'       => true,
                    'compactHeader'  => true,
                    'forcesave'      => true,
                    'hideRightMenu'  => true,
                ],
            ],
        ];

        $token = '';
        if ($onlyofficeSecret) {
            $header    = rtrim(strtr(base64_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT'])), '+/', '-_'), '=');
            $body      = rtrim(strtr(base64_encode(json_encode($ooConfigPayload)), '+/', '-_'), '=');
            $signature = rtrim(strtr(base64_encode(hash_hmac('sha256', "$header.$body", $onlyofficeSecret, true)), '+/', '-_'), '=');
            $token = "$header.$body.$signature";
        }

        return response()->json([
            'success'       => true,
            'template_id'   => $template->id,
            'template_name' => $template->name,
            'fileType'      => $ext,
            'has_file'      => $exists,
            'docUrl'        => $docUrl,
            'localDocUrl'   => $localDocUrl,
            'storagePubPath'=> $storagePubPath,
            'ooUrl'         => $onlyofficeUrl,
            'token'         => $token,
            'ooConfig'      => $ooConfigPayload,  // config complète prête pour DocsAPI
            'warning'       => $docUrlAccessWarning,
        ]);
    }

    private function validateOnlyofficeAccessibleUrl(string $url): ?string
    {
        $disableCert = filter_var(
            AppSetting::where('key', 'onlyoffice_disable_cert')->value('value') ?? false,
            FILTER_VALIDATE_BOOLEAN
        );

        try {
            $response = $this->probeOnlyofficeUrl($url, $disableCert);

            if ($response->successful() || $response->redirect()) {
                // Vérification complémentaire SANS header ngrok-skip-browser-warning.
                // Si l'URL publique est un tunnel ngrok gratuit, OnlyOffice peut recevoir
                // la page d'avertissement (ERR_NGROK_6024) et ouvrir un document "blanc".
                $rawResponse = $this->probeOnlyofficeUrlRaw($url, $disableCert);
                $rawBody = (string) $rawResponse->body();
                if (str_contains($rawBody, 'ERR_NGROK_6024')) {
                    return 'URL publique bloquée par l\'interstitiel ngrok (ERR_NGROK_6024). OnlyOffice reçoit une page HTML au lieu du DOCX, ce qui provoque un éditeur vide. Utilisez une URL publique sans interstitiel (ngrok avec bypass côté client serveur, tunnel cloudflared, ou domaine public direct).';
                }

                return null;
            }

            return 'OnlyOffice ne peut pas télécharger le fichier car l\'URL publique configurée répond ' . $response->status() . ' : ' . $url . '. Vérifiez la valeur "URL publique de cette application" dans l\'onglet OnlyOffice.';
        } catch (\Throwable $e) {
            if (!$disableCert && str_contains($e->getMessage(), 'cURL error 60')) {
                try {
                    $response = $this->probeOnlyofficeUrl($url, true);
                    if ($response->successful() || $response->redirect()) {
                        $rawResponse = $this->probeOnlyofficeUrlRaw($url, true);
                        $rawBody = (string) $rawResponse->body();
                        if (str_contains($rawBody, 'ERR_NGROK_6024')) {
                            return 'URL publique bloquée par l\'interstitiel ngrok (ERR_NGROK_6024). OnlyOffice reçoit une page HTML au lieu du DOCX, ce qui provoque un éditeur vide. Utilisez une URL publique sans interstitiel (ngrok avec bypass côté client serveur, tunnel cloudflared, ou domaine public direct).';
                        }

                        return null;
                    }

                    return 'OnlyOffice ne peut pas télécharger le fichier car l\'URL publique configurée répond ' . $response->status() . ' : ' . $url . '. Vérifiez la valeur "URL publique de cette application" dans l\'onglet OnlyOffice.';
                } catch (\Throwable $retryException) {
                    return 'OnlyOffice ne peut pas joindre l\'URL publique du document : ' . $url . '. Détail : ' . $retryException->getMessage();
                }
            }

            return 'OnlyOffice ne peut pas joindre l\'URL publique du document : ' . $url . '. Détail : ' . $e->getMessage();
        }
    }

    private function probeOnlyofficeUrl(string $url, bool $disableCert = false)
    {
        $client = Http::timeout(10)
            ->withHeaders([
                'ngrok-skip-browser-warning' => '1',
                'Accept' => '*/*',
            ])
            ->withUserAgent('OnlyOfficeUrlProbe/1.0');

        if ($disableCert) {
            $client = $client->withoutVerifying();
        }

        $response = $client->head($url);
        if ($response->status() === 405) {
            $response = $client->get($url);
        }

        return $response;
    }

    private function probeOnlyofficeUrlRaw(string $url, bool $disableCert = false)
    {
        $client = Http::timeout(10)
            ->withHeaders([
                'Accept' => '*/*',
            ])
            ->withUserAgent('OnlyOfficeRawProbe/1.0');

        if ($disableCert) {
            $client = $client->withoutVerifying();
        }

        return $client->get($url);
    }

    public function ooTemplateCallback(Request $request, string $templateId)
    {
        // ── Vérification HMAC (même principe que document callback) ──────────
        $access   = (string) $request->query('access', '');
        $expected = hash_hmac('sha256', 'cb|tpl|' . $templateId, (string) config('app.key'));
        if (!hash_equals($expected, $access)) {
            \Log::warning('OO callback HMAC mismatch template=' . $templateId . ' received=' . substr($access, 0, 8) . '... expected=' . substr($expected, 0, 8) . '...');
            // Retourner 0 pour ne pas bloquer l'éditeur OO ("impossible d'enregistrer")
            return response()->json(['error' => 0]);
        }

        $data   = $request->json()->all();
        $status = (int) ($data['status'] ?? 0);

        \Log::info('OO callback template=' . $templateId . ' status=' . $status . ' has_url=' . (!empty($data['url']) ? '1' : '0'));

        // status 2 = session terminée avec changements | status 6 = forcesave/autosave
        // Certaines versions/paramétrages OO envoient status 4 avec URL de fichier à persister.
        if (in_array($status, [2, 4, 6], true) && !empty($data['url'])) {
            // ── Validation SSRF : l'URL doit provenir du serveur OO configuré ──
            $ooServerUrl = (string) AppSetting::where('key', 'onlyoffice_server_url')->value('value');
            $fileUrl     = (string) $data['url'];

            if ($ooServerUrl !== '') {
                $ooHost   = parse_url(rtrim($ooServerUrl, '/'), PHP_URL_HOST);
                $fileHost = parse_url($fileUrl, PHP_URL_HOST);
                if (!$ooHost || !$fileHost || strtolower($ooHost) !== strtolower($fileHost)) {
                    \Log::warning('OO template callback: URL rejetée (hôte non autorisé)', [
                        'template_id'  => $templateId,
                        'allowed_host' => $ooHost,
                        'received_host'=> $fileHost,
                    ]);
                    return response()->json(['error' => 0]);
                }
            }

            $template = \App\Models\DocumentTemplate::find($templateId);
            if ($template) {
                try {
                    $disableCert = filter_var(
                        AppSetting::where('key', 'onlyoffice_disable_cert')->value('value') ?? false,
                        FILTER_VALIDATE_BOOLEAN
                    );

                    $client = Http::timeout(60);
                    if ($disableCert) {
                        $client = $client->withoutVerifying();
                    }

                    try {
                        $response = $client->get($fileUrl);
                    } catch (\Throwable $downloadError) {
                        // Fallback auto sur erreur certificat TLS (cURL 60)
                        if (!$disableCert && str_contains($downloadError->getMessage(), 'cURL error 60')) {
                            $response = Http::timeout(60)->withoutVerifying()->get($fileUrl);
                        } else {
                            throw $downloadError;
                        }
                    }

                    if ($response->successful()) {
                        $fileContent = $response->body();
                        $ext      = strtolower($template->file_type ?: 'docx');
                        $filename = 'tpl_' . preg_replace('/[^a-zA-Z0-9_\-]/', '', $templateId) . '_' . time() . '.' . $ext;
                        $destDir  = public_path('images/templates');
                        $savedAbsPath = '';
                        $savedStoragePath = '';

                        // Supprimer l'ancien fichier si existant dans le dossier attendu
                        if ($template->storage_path && str_starts_with($template->storage_path, 'images/templates/')) {
                            $old = public_path($template->storage_path);
                            if (file_exists($old)) {
                                @unlink($old);
                            }
                        }

                        // Essai 1: écriture legacy dans public/images/templates
                        try {
                            if (!is_dir($destDir)) {
                                mkdir($destDir, 0755, true);
                            }
                            file_put_contents($destDir . '/' . $filename, $fileContent);
                            $savedAbsPath = $destDir . '/' . $filename;
                            $savedStoragePath = 'images/templates/' . $filename;
                        } catch (\Throwable $writePublicError) {
                            // Essai 2 (fallback): disque public Laravel (storage/app/public/templates)
                            $fallbackStoragePath = 'templates/' . $filename;
                            \Illuminate\Support\Facades\Storage::disk('public')->put($fallbackStoragePath, $fileContent);
                            $savedAbsPath = \Illuminate\Support\Facades\Storage::disk('public')->path($fallbackStoragePath);
                            $savedStoragePath = $fallbackStoragePath;

                            \Log::warning('OO callback write fallback to storage/public', [
                                'template_id' => $templateId,
                                'error' => $writePublicError->getMessage(),
                                'saved_path' => $savedStoragePath,
                            ]);
                        }

                        $template->storage_path = $savedStoragePath;
                        $template->save();
                        \Log::info('OO file saved: ' . $savedStoragePath . ' size=' . strlen($fileContent));

                        // ── Extraction automatique des variables après sauvegarde OO ──
                        $absNewPath = $savedAbsPath;
                        if (in_array($ext, ['docx', 'xlsx', 'pptx']) && file_exists($absNewPath)) {
                            foreach ($this->extractVarsFromUploadedFile($absNewPath) as $fileVar) {
                                \App\Models\TemplateVariable::firstOrCreate(
                                    ['template_id' => $template->id, 'key' => $fileVar['key']],
                                    [
                                        'label'         => $fileVar['label'],
                                        'field_type'    => 'text',
                                        'required'      => false,
                                        'placeholder'   => '',
                                        'default_value' => '',
                                        'options'       => json_encode([]),
                                    ]
                                );
                            }
                            \Log::info('OO vars extracted for template ' . $templateId);
                        }
                    } else {
                        \Log::warning('OO template callback: échec téléchargement', [
                            'template_id' => $templateId,
                            'http_status' => $response->status(),
                        ]);
                    }
                } catch (\Throwable $e) {
                    \Log::warning('OO callback save failed for template ' . $templateId . ': ' . $e->getMessage());
                }
            }
        } else {
            // Si callback sans URL et template Office sans fichier, créer un bootstrap pour sortir du blocage UI.
            try {
                $template = \App\Models\DocumentTemplate::find($templateId);
                if (
                    $template
                    && empty($template->storage_path)
                    && in_array(strtolower((string) ($template->file_type ?: 'docx')), ['docx', 'xlsx', 'pptx'], true)
                ) {
                    $this->ensureTemplateBootstrapFile($template);
                }
            } catch (\Throwable $e) {
                \Log::warning('OO callback bootstrap fallback error', [
                    'template_id' => $templateId,
                    'error' => $e->getMessage(),
                ]);
            }
            \Log::info('OO callback ignored template=' . $templateId . ' status=' . $status . ' reason=' . (!in_array($status, [2, 4, 6], true) ? 'status_not_persistable' : 'missing_url'));
        }

        return response()->json(['error' => 0]);
    }

    /**
     * Fichier template pour OnlyOffice via URL signée temporaire (sans session utilisateur).
     */
    public function ooTemplateFile(Request $request, string $templateId)
    {
        $expires = (int) $request->query('expires', 0);
        $access  = (string) $request->query('access', '');

        if ($expires <= 0 || $expires < time()) {
            abort(403, 'Lien expiré ou invalide');
        }

        $expected = hash_hmac('sha256', 'tplfile|' . $templateId . '|' . $expires, (string) config('app.key'));
        if (!hash_equals($expected, $access)) {
            abort(403, 'Lien expiré ou invalide');
        }

        $template = DocumentTemplate::findOrFail($templateId);
        if (!$template->storage_path) {
            abort(404, 'Template sans fichier');
        }

        $fileName = $template->file_name ?: ('template-' . $template->id);
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION) ?: ($template->file_type ?: 'bin'));

        $mimeMap = [
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'pdf'  => 'application/pdf',
        ];
        $mime = $mimeMap[$ext] ?? 'application/octet-stream';

        if (str_starts_with($template->storage_path, 'images/')) {
            $absPath = public_path($template->storage_path);
            if (!file_exists($absPath)) {
                abort(404, 'Fichier template introuvable');
            }

            return response()->file($absPath, [
                'Content-Type' => $mime,
                'Content-Disposition' => 'inline; filename="' . addslashes($fileName) . '"',
                'Cache-Control' => 'no-store',
            ]);
        }

        if (!Storage::disk('public')->exists($template->storage_path)) {
            abort(404, 'Fichier template introuvable');
        }

        return Storage::disk('public')->response($template->storage_path, $fileName, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="' . addslashes($fileName) . '"',
            'Cache-Control' => 'no-store',
        ]);
    }

    public function storeTemplate(Request $request)
    {
        $request->validate([
            'name'      => 'required|string|max:255',
            'file_name' => 'nullable|string|max:255',
            'file_type' => 'required|in:pdf,docx,xlsx,pptx',
            'content'   => 'nullable|string',
        ]);
        $adminScope = $this->resolveAdminScope();
        // Forcer l'administration si l'utilisateur est scoped
        $administrationId = ($adminScope && $adminScope['type'] === 'emitter')
            ? $adminScope['id']
            : ($request->input('administration_id') ?: null);
        $template = DocumentTemplate::create([
            'id'               => Str::uuid(),
            'name'             => $request->input('name'),
            'file_name'        => $request->input('file_name', ''),
            'file_type'        => $request->input('file_type'),
            'content'          => $request->input('content', ''),
            'administration_id'=> $administrationId,
            'created_by'       => auth()->id(),
        ]);

        // Créer immédiatement un fichier source pour les templates Office
        // afin d'éviter les templates "sans fichier" bloqués en CloseGuard.
        $this->ensureTemplateBootstrapFile($template);

        $this->saveDetectedTemplateVars(
            $template,
            array_values($this->extractVarsFromTemplateText($template->content))
        );

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success'       => true,
                'id'            => $template->id,
                'name'          => $template->name,
                'file_name'     => $template->file_name,
                'file_type'     => $template->file_type,
                'has_file'      => (bool) ($template->storage_path && file_exists(public_path($template->storage_path))),
                'administration'=> $template->administration?->name ?? null,
                'variables_url' => route('admin.index', ['tab' => 'templates', 'selected_template' => $template->id]),
                'share_url'     => route('admin.templates.share', $template->id),
            ]);
        }
        return back()->with('success', 'Template créé.')->withInput(['tab' => 'templates']);
    }

    private function ensureTemplateBootstrapFile(DocumentTemplate $template): void
    {
        $ext = strtolower((string) ($template->file_type ?: 'docx'));
        if (!in_array($ext, ['docx', 'xlsx', 'pptx'], true)) {
            return;
        }

        if ($template->storage_path) {
            if (str_starts_with($template->storage_path, 'images/')) {
                $existing = public_path($template->storage_path);
                if (file_exists($existing)) {
                    return;
                }
            } else {
                if (\Illuminate\Support\Facades\Storage::disk('public')->exists($template->storage_path)) {
                    return;
                }
            }
        }

        $blankMap = [
            'docx' => public_path('empty_template.docx'),
            'xlsx' => public_path('blank_xlsx.xlsx'),
            'pptx' => public_path('blank_pptx.pptx'),
        ];

        $src = $blankMap[$ext] ?? null;
        if (!$src || !file_exists($src)) {
            \Log::warning('Template bootstrap skipped: blank file missing', [
                'template_id' => (string) $template->id,
                'ext' => $ext,
                'src' => $src,
            ]);
            return;
        }

        $destDir = public_path('images/templates');
        if (!is_dir($destDir)) {
            @mkdir($destDir, 0755, true);
        }

        $fileName = 'tpl_' . preg_replace('/[^a-zA-Z0-9_\-]/', '', (string) $template->id) . '_' . time() . '.' . $ext;
        $destPath = $destDir . DIRECTORY_SEPARATOR . $fileName;

        if (!@copy($src, $destPath)) {
            \Log::warning('Template bootstrap failed: copy error', [
                'template_id' => (string) $template->id,
                'src' => $src,
                'dest' => $destPath,
            ]);
            return;
        }

        if ($ext === 'docx') {
            $this->injectBootstrapDocxHint($destPath, $template);
        }

        $template->storage_path = 'images/templates/' . $fileName;
        $template->save();
    }

    private function injectBootstrapDocxHint(string $absDocxPath, DocumentTemplate $template): void
    {
        if (!class_exists('ZipArchive') || !file_exists($absDocxPath)) {
            return;
        }

        $zip = new \ZipArchive();
        if ($zip->open($absDocxPath) !== true) {
            return;
        }

        $xml = $zip->getFromName('word/document.xml');
        if ($xml === false) {
            $zip->close();
            return;
        }

        $hint = 'NOUVEAU TEMPLATE : saisissez votre contenu ici puis enregistrez (Ctrl+S).';
        $hint2 = 'Exemple de variable: {{ NOM DU DEMANDEUR }}';
        $safe1 = htmlspecialchars($hint, ENT_XML1, 'UTF-8');
        $safe2 = htmlspecialchars($hint2, ENT_XML1, 'UTF-8');

        // Insère un paragraphe visible au début du document pour éviter l'effet "page blanche".
        $insert = '<w:p><w:r><w:rPr><w:b/></w:rPr><w:t xml:space="preserve">' . $safe1 . '</w:t></w:r></w:p>'
            . '<w:p><w:r><w:t xml:space="preserve">' . $safe2 . '</w:t></w:r></w:p>';

        $newXml = preg_replace('/<w:body>/i', '<w:body>' . $insert, $xml, 1);
        if (is_string($newXml) && $newXml !== $xml) {
            $zip->addFromString('word/document.xml', $newXml);
        }

        $zip->close();
    }

    public function updateTemplate(Request $request, DocumentTemplate $template)
    {
        $request->validate([
            'name'      => 'required|string|max:255',
            'file_name' => 'nullable|string|max:255',
            'file_type' => 'required|in:pdf,docx,xlsx,pptx',
            'content'   => 'nullable|string',
        ]);
        $template->update([
            'name'             => $request->input('name'),
            'file_name'        => $request->input('file_name', $template->file_name),
            'file_type'        => $request->input('file_type'),
            'content'          => $request->input('content', ''),
            'administration_id'=> $request->input('administration_id', $template->administration_id),
        ]);

        $this->saveDetectedTemplateVars(
            $template,
            array_values($this->extractVarsFromTemplateText($template->content))
        );

        return back()->with('success', 'Template mis à jour.')->withInput(['tab' => 'templates']);
    }

    public function destroyTemplate(DocumentTemplate $template)
    {
        $template->delete();
        return back()->with('success', 'Template supprimé.')->withInput(['tab' => 'templates']);
    }

    public function storeTemplateVariable(Request $request, DocumentTemplate $template)
    {
        $request->validate([
            'label'      => 'required|string|max:255',
            'field_type' => 'required|in:text,date,number,select,textarea',
        ]);
        $key = \Str::slug($request->input('label'), '_');
        $template->variables()->updateOrCreate(
            ['key' => $key],
            [
                'id'          => Str::uuid(),
                'label'       => $request->input('label'),
                'field_type'  => $request->input('field_type'),
                'placeholder' => $request->input('placeholder', ''),
            ]
        );
        return back()->with('success', 'Variable ajoutée.')->withInput(['tab' => 'templates', 'selected_template' => $template->id]);
    }

    public function destroyTemplateVariable(DocumentTemplate $template, string $variableId)
    {
        $template->variables()->where('id', $variableId)->delete();
        return back()->with('success', 'Variable supprimée.')->withInput(['tab' => 'templates', 'selected_template' => $template->id]);
    }

    public function updateTemplateShare(Request $request, DocumentTemplate $template)
    {
        $userId  = $request->input('user_id');
        $action  = $request->input('action', 'toggle'); // 'add' | 'remove' | 'toggle'
        $setting = AppSetting::firstOrCreate(['key' => 'template_share_map'], ['value' => '{}']);
        $map     = json_decode($setting->value ?: '{}', true) ?: [];
        $shared  = $map[$template->id] ?? [];
        if ($action === 'add' || ($action === 'toggle' && !in_array($userId, $shared))) {
            $shared[] = $userId;
        } else {
            $shared = array_values(array_filter($shared, fn($id) => $id !== $userId));
        }
        // Récupérer les nouveaux bénéficiaires (présents maintenant, absents avant)
        $prevShared = $map[$template->id] ?? [];
        $map[$template->id] = array_values(array_unique($shared));
        $newlyAdded = array_diff($map[$template->id], $prevShared);
        $setting->update(['value' => json_encode($map)]);

        // Notifier les utilisateurs nouvellement ajoutés
        foreach ($newlyAdded as $uid) {
            NotificationService::templateShared($template, $uid, auth()->user()->name);
        }

        return back()->with('success', 'Partage mis à jour.')->withInput(['tab' => 'templates', 'selected_template' => $template->id]);
    }

    public function saveTemplateZones(Request $request, DocumentTemplate $template)
    {
        $zones = $request->input('zones', []);
        if (!is_array($zones)) {
            return response()->json(['success' => false, 'message' => 'Format de zones invalide.'], 422);
        }
        $sanitized = array_map(function ($z) {
            return [
                'x'      => round((float) ($z['x'] ?? 0), 4),
                'y'      => round((float) ($z['y'] ?? 0), 4),
                'w'      => round((float) ($z['w'] ?? 22), 4),
                'h'      => round((float) ($z['h'] ?? 18), 4),
                'sealed' => (bool) ($z['sealed'] ?? true),
                'label'  => trim(strip_tags((string) ($z['label'] ?? ''))),
            ];
        }, array_values($zones));

        $template->update(['signature_zones' => json_encode($sanitized)]);
        return response()->json(['success' => true, 'message' => count($sanitized) . ' zone(s) de signature enregistrée(s).' ]);
    }

    public function forceSaveTemplate(Request $request, DocumentTemplate $template)
    {
        $request->validate([
            'doc_key' => 'required|string|max:255',
        ]);

        $onlyofficeUrl = rtrim((string) (AppSetting::where('key', 'onlyoffice_server_url')->value('value') ?: ''), '/');
        $onlyofficeSecret = (string) (AppSetting::where('key', 'onlyoffice_secret')->value('value') ?: '');

        if ($onlyofficeUrl === '') {
            return response()->json([
                'success' => false,
                'message' => 'Serveur OnlyOffice non configuré.',
            ], 422);
        }

        $disableCert = filter_var(
            AppSetting::where('key', 'onlyoffice_disable_cert')->value('value') ?? false,
            FILTER_VALIDATE_BOOLEAN
        );

        $docKey = (string) $request->input('doc_key');
        $commandPayload = [
            'c' => 'forcesave',
            'key' => $docKey,
            'userdata' => 'tpl-force-' . $template->id . '-' . time(),
        ];

        if ($onlyofficeSecret !== '') {
            $header = rtrim(strtr(base64_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT'])), '+/', '-_'), '=');
            $body = rtrim(strtr(base64_encode(json_encode($commandPayload)), '+/', '-_'), '=');
            $signature = rtrim(strtr(base64_encode(hash_hmac('sha256', "$header.$body", $onlyofficeSecret, true)), '+/', '-_'), '=');
            $jwt = "$header.$body.$signature";
            $commandPayload['token'] = $jwt;
        }

        try {
            $client = Http::timeout(20)->acceptJson();
            if ($disableCert) {
                $client = $client->withoutVerifying();
            }

            $response = $client->post($onlyofficeUrl . '/coauthoring/CommandService.ashx', $commandPayload);
            $data = $response->json();
            $errorCode = is_array($data) ? (int) ($data['error'] ?? -1) : -1;

            Log::info('OnlyOffice CommandService forcesave', [
                'template_id' => (string) $template->id,
                'doc_key' => $docKey,
                'http_status' => $response->status(),
                'oo_error' => $errorCode,
                'oo_response' => $data,
            ]);

            if ($response->successful() && $errorCode === 0) {
                return response()->json([
                    'success' => true,
                    'message' => 'Forcesave déclenché.',
                ]);
            }

            // error=4 : aucune modification avant le forcesave → session déjà fermée ou doc non modifié
            // Ce n'est pas une vraie erreur, le fichier est déjà dans son dernier état.
            if ($errorCode === 4 || $errorCode === 1) {
                return response()->json([
                    'success' => true,
                    'message' => 'Document déjà sauvegardé (aucun changement en attente).',
                    'oo_code' => $errorCode,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'OnlyOffice a refusé le forcesave (code ' . $errorCode . ').',
                'details' => $data,
            ], 422);
        } catch (\Throwable $e) {
            Log::warning('OnlyOffice CommandService forcesave failed', [
                'template_id' => (string) $template->id,
                'doc_key' => $docKey,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur réseau lors du forcesave OnlyOffice: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ── Règles de routage ─────────────────────────────────────────────────────
    public function storeRoutingRule(Request $request)
    {
        $data = $request->validate([
            'template_id'        => 'required|exists:document_templates,id',
            'recipient_id'       => 'required|exists:recipient_administrations,id',
            'condition_field'    => 'nullable|string|max:100',
            'condition_operator' => 'nullable|string|max:20',
            'condition_value'    => 'nullable|string|max:255',
            'priority'           => 'integer|min:0',
        ]);
        RoutingRule::create(['id' => Str::uuid(), 'is_active' => true] + $data);
        return back()->with('success', 'Règle de routage créée.')->withInput(['tab' => 'routing']);
    }

    public function destroyRoutingRule(RoutingRule $routingRule)
    {
        $routingRule->delete();
        return back()->with('success', 'Règle supprimée.')->withInput(['tab' => 'routing']);
    }

    // ── Entités sous tutelle ─────────────────────────────────────────────────
    public function storeSubEntity(Request $request)
    {
        $adminScope = $this->resolveAdminScope();
        $request->validate([
            'scope_type'        => $adminScope ? 'nullable|in:emitter,recipient' : 'required|in:emitter,recipient',
            'scope_id'          => $adminScope ? 'nullable|string|max:50' : 'required|string|max:50',
            'name'              => 'required|string|max:255',
            'code'              => 'required|string|max:50',
            'parent_code'       => 'nullable|string|max:50',
            'direction_type_id' => 'nullable|string',
            'manager_name'      => 'nullable|string|max:255',
            'manager_email'     => 'nullable|email|max:255',
            'description'       => 'nullable|string',
        ]);

        // Forcer scope_type + scope_id si l'utilisateur est scoped
        if (isset($adminScope) && $adminScope) {
            $scopeType = $adminScope['type'];
            $scopeId   = $adminScope['id'];
        } else {
            $scopeType = $request->input('scope_type');
            $scopeId   = $request->input('scope_id');
        }
        $code      = strtoupper(trim((string) $request->input('code')));

        // Unicité du code par administration (scope_type + scope_id), pas globale
        $exists = SubEntity::where('scope_type', $scopeType)
            ->where('scope_id', $scopeId)
            ->whereRaw('UPPER(code) = ?', [$code])
            ->exists();

        if ($exists) {
            return back()
                ->withErrors(['code' => 'Ce code est déjà utilisé par une entité de cette administration.'])
                ->withInput();
        }

        $data = $request->only([
            'name', 'parent_code',
            'direction_type_id', 'manager_name', 'manager_email', 'description',
        ]);
        $data['scope_type'] = $scopeType;
        $data['scope_id']   = $scopeId;
        $data['code']      = $code;
        $data['is_active'] = $request->boolean('is_active', true);
        SubEntity::create($data);
        return back()->with('success', 'Entité sous tutelle créée.')->withInput(['tab' => 'sub-entities']);
    }

    public function updateSubEntity(Request $request, SubEntity $subEntity)
    {
        $request->validate([
            'scope_type'        => 'required|in:emitter,recipient',
            'scope_id'          => 'required|string|max:50',
            'name'              => 'required|string|max:255',
            'code'              => 'required|string|max:50',
            'parent_code'       => 'nullable|string|max:50',
            'direction_type_id' => 'nullable|string',
            'manager_name'      => 'nullable|string|max:255',
            'manager_email'     => 'nullable|email|max:255',
            'description'       => 'nullable|string',
        ]);

        $scopeType = $request->input('scope_type');
        $scopeId   = $request->input('scope_id');
        $code      = strtoupper(trim((string) $request->input('code')));

        // Unicité du code par administration (scope_type + scope_id), en excluant l'entité elle-même
        $exists = SubEntity::where('scope_type', $scopeType)
            ->where('scope_id', $scopeId)
            ->whereRaw('UPPER(code) = ?', [$code])
            ->where('id', '!=', $subEntity->id)
            ->exists();

        if ($exists) {
            return back()
                ->withErrors(['code' => 'Ce code est déjà utilisé par une entité de cette administration.'])
                ->withInput();
        }

        $data = $request->only([
            'scope_type', 'scope_id', 'name', 'parent_code',
            'direction_type_id', 'manager_name', 'manager_email', 'description',
        ]);
        $data['code']      = $code;
        $data['is_active'] = $request->boolean('is_active', true);
        $subEntity->update($data);
        return back()->with('success', 'Entité sous tutelle mise à jour.')->withInput(['tab' => 'sub-entities']);
    }

    public function destroySubEntity(SubEntity $subEntity)
    {
        $subEntity->delete();
        return back()->with('success', 'Entité sous tutelle supprimée.')->withInput(['tab' => 'sub-entities']);
    }

    // ── Actes demandés ────────────────────────────────────────────────────────
    public function storeRequestedAct(Request $request)
    {
        $data = $request->validate([
            'administration_id'  => 'nullable|string',
            'direction_code'     => 'nullable|string|max:50',
            'document_name'      => 'required|string|max:255',
            'required_documents' => 'nullable|string',
            'applicant_fields'   => 'nullable|string',
        ]);
        // Forcer l'administration si l'utilisateur est scoped
        $adminScope = $this->resolveAdminScope();
        if ($adminScope && $adminScope['type'] === 'emitter') {
            $data['administration_id'] = $adminScope['id'];
        }
        $data['required_documents'] = json_decode($data['required_documents'] ?? '[]', true) ?: [];
        $data['applicant_fields']   = json_decode($data['applicant_fields']   ?? '[]', true) ?: [];
        RequestedAct::create($data);
        return back()->with('success', 'Acte demandé créé.')->withInput(['tab' => 'requested-acts']);
    }

    public function updateRequestedAct(Request $request, RequestedAct $requestedAct)
    {
        $data = $request->validate([
            'administration_id'  => 'required|string',
            'direction_code'     => 'nullable|string|max:50',
            'document_name'      => 'required|string|max:255',
            'required_documents' => 'nullable|string',
            'applicant_fields'   => 'nullable|string',
        ]);
        $data['required_documents'] = json_decode($data['required_documents'] ?? '[]', true) ?: [];
        $data['applicant_fields']   = json_decode($data['applicant_fields']   ?? '[]', true) ?: [];
        $requestedAct->update($data);
        return back()->with('success', 'Acte demandé mis à jour.')->withInput(['tab' => 'requested-acts']);
    }

    public function destroyRequestedAct(RequestedAct $requestedAct)
    {
        $requestedAct->delete();
        return back()->with('success', 'Acte demandé supprimé.')->withInput(['tab' => 'requested-acts']);
    }

    // ── Profils / Rôles ───────────────────────────────────────────────────────
    public function storeProfile(Request $request)
    {
        $data = $request->validate([
            'name'              => 'required|string|max:255',
            'description'       => 'nullable|string|max:500',
            'administration_id' => 'nullable|exists:issuing_administrations,id',
            'permissions'       => 'nullable|array',
        ]);
        $menuPermissions = $data['permissions'] ?? [];
        // Forcer l'administration si l'utilisateur est scoped
        $adminScope = $this->resolveAdminScope();
        $administrationId = ($adminScope && $adminScope['type'] === 'emitter')
            ? $adminScope['id']
            : ($data['administration_id'] ?? null);
        AdministrationProfile::create([
            'id'                => Str::uuid(),
            'name'              => $data['name'],
            'description'       => $data['description'] ?? '',
            'administration_id' => $administrationId,
            'permissions'       => [
                'description'       => $data['description'] ?? '',
                'menuPermissions'   => $menuPermissions,
            ],
        ]);
        return back()->with('success', 'Profil créé.')->withInput(['tab' => 'user-profiles']);
    }

    public function updateProfile(Request $request, AdministrationProfile $profile)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'permissions' => 'nullable|array',
        ]);
        $menuPermissions = $data['permissions'] ?? [];
        $profile->update([
            'name'        => $data['name'],
            'description' => $data['description'] ?? '',
            'permissions' => [
                'description'     => $data['description'] ?? '',
                'menuPermissions' => $menuPermissions,
            ],
        ]);
        return back()->with('success', 'Profil mis à jour.')->withInput(['tab' => 'user-profiles']);
    }

    public function assignProfile(Request $request)
    {
        $data = $request->validate([
            'user_id'    => 'required|exists:users,id',
            'profile_id' => 'nullable|exists:administration_profiles,id',
        ]);
        \App\Models\User::where('id', $data['user_id'])->update(['profile_id' => $data['profile_id'] ?: null]);
        return back()->with('success', 'Profil assigné.')->withInput(['tab' => 'user-profiles']);
    }

    public function destroyProfile(AdministrationProfile $profile)
    {
        $profile->delete();
        return back()->with('success', 'Profil supprimé.')->withInput(['tab' => 'user-profiles']);
    }

    // ── Instructions de traitement courrier ───────────────────────────────────
    public function storeInstruction(Request $request)
    {
        $data = $request->validate([
            'nom'         => 'required|string|max:150',
            'description' => 'nullable|string|max:500',
        ]);
        Instruction::create(array_merge($data, ['actif' => true]));
        return back()->with('success', 'Instruction créée.')->withInput(['tab' => 'instructions']);
    }

    public function updateInstruction(Request $request, Instruction $instruction)
    {
        $data = $request->validate([
            'nom'         => 'required|string|max:150',
            'description' => 'nullable|string|max:500',
        ]);
        $instruction->update([
            'nom'         => $data['nom'],
            'description' => $data['description'] ?? null,
            'actif'       => $request->boolean('actif', true),
        ]);
        return back()->with('success', 'Instruction mise à jour.')->withInput(['tab' => 'instructions']);
    }

    public function destroyInstruction(Instruction $instruction)
    {
        $instruction->delete();
        return back()->with('success', 'Instruction supprimée.')->withInput(['tab' => 'instructions']);
    }

    // ── Utilisateurs (onglet admin) ────────────────────────────────────────
    public function storeUserTab(Request $request)
    {
        $data = $request->validate([
            'nom'            => 'required|string|max:100',
            'prenoms'        => 'nullable|string|max:150',
            'name'           => 'required|string|max:191',
            'email'          => 'required|email|unique:users,email',
            'role'           => 'required|in:admin,user,signer,manager',
            'profile_id'     => 'nullable|uuid|exists:administration_profiles,id',
            'password'       => 'required|string|min:8|confirmed',
            'status'         => 'nullable|in:active,inactive,suspended',
            'quota'          => 'nullable|string|max:50',
            'avatar'         => 'nullable|image|max:5120',
            'administration_type'  => 'nullable|in:emitter,recipient',
            'administration_id'    => 'nullable|string|max:36',
            'sub_entity_id'        => 'nullable|string|max:36',
        ]);

        try {
            $fullName = trim(($data['prenoms'] ?? '') . ' ' . $data['nom']);
            $avatarPath = null;
            if ($request->hasFile('avatar')) {
                $storedPath = $request->file('avatar')->store('avatars', 'public');
                $avatarPath = 'storage/' . ltrim($storedPath, '/');
            }

            $payload = [
                'name'       => $data['name'],
                'full_name'  => $fullName,
                'email'      => $data['email'],
                'password'   => Hash::make($data['password']),
                'role'       => $data['role'],
                'profile_id' => $data['profile_id'] ?? null,
                'status'     => $data['status'] ?? 'active',
                'quota'      => $data['quota'] ?? null,
                'avatar'     => $avatarPath,
                'locale'     => 'fr',
            ];

            try {
                $user = User::create($payload);
            } catch (QueryException $e) {
                $msg = strtolower($e->getMessage());
                if (str_contains($msg, 'unknown column') && str_contains($msg, 'locale')) {
                    unset($payload['locale']);
                    $user = User::create($payload);
                } else {
                    throw $e;
                }
            }

            if (!empty($data['administration_type']) && !empty($data['administration_id'])) {
                $subEntity = $data['sub_entity_id'] ? SubEntity::find($data['sub_entity_id']) : null;
                $admin = $data['administration_type'] === 'emitter'
                    ? IssuingAdministration::find($data['administration_id'])
                    : RecipientAdministration::find($data['administration_id']);
                UserDirectionAssignment::create([
                    'user_id'              => $user->id,
                    'direction_scope_type' => $data['administration_type'],
                    'direction_scope_id'   => $data['administration_id'],
                    'sub_entity_code'      => $subEntity?->code ?? null,
                    'direction_label'      => $subEntity?->name ?? $admin?->name ?? '',
                ]);
            }

            return redirect()->route('admin.index', ['tab' => 'users'])->with('success', 'Utilisateur créé avec succès.');
        } catch (\Throwable $e) {
            Log::error('storeUserTab failed', [
                'email' => $data['email'] ?? null,
                'message' => $e->getMessage(),
            ]);

            return back()
                ->withInput()
                ->withErrors(['users' => 'Échec de création utilisateur: ' . $e->getMessage()]);
        }
    }

    public function updateUserTab(Request $request, User $user)
    {
        $data = $request->validate([
            'nom'            => 'required|string|max:100',
            'prenoms'        => 'nullable|string|max:150',
            'name'           => 'required|string|max:191',
            'email'          => 'required|email|unique:users,email,' . $user->id,
            'role'           => 'required|in:admin,user,signer,manager',
            'profile_id'     => 'nullable|uuid|exists:administration_profiles,id',
            'password'       => 'nullable|string|min:8|confirmed',
            'status'         => 'nullable|in:active,inactive,suspended',
            'quota'          => 'nullable|string|max:50',
            'avatar'         => 'nullable|image|max:5120',
            'administration_type'  => 'nullable|in:emitter,recipient',
            'administration_id'    => 'nullable|string|max:36',
            'sub_entity_id'        => 'nullable|string|max:36',
        ]);

        $fullName = trim(($data['prenoms'] ?? '') . ' ' . $data['nom']);
        $update = [
            'name'       => $data['name'],
            'full_name'  => $fullName,
            'email'      => $data['email'],
            'role'       => $data['role'],
            'profile_id' => $data['profile_id'] ?? $user->profile_id,
            'status'     => $data['status'] ?? $user->status,
            'quota'      => $data['quota'] ?? $user->quota,
        ];
        if (!empty($data['password'])) {
            $update['password'] = Hash::make($data['password']);
        }
        if ($request->hasFile('avatar')) {
            if ($user->avatar && str_starts_with($user->avatar, 'storage/')) {
                $oldStorage = ltrim(substr($user->avatar, strlen('storage/')), '/');
                if (Storage::disk('public')->exists($oldStorage)) {
                    Storage::disk('public')->delete($oldStorage);
                }
            } elseif ($user->avatar && str_starts_with($user->avatar, 'images/')) {
                $old = public_path($user->avatar);
                if (file_exists($old)) @unlink($old);
            }
            $storedPath = $request->file('avatar')->store('avatars', 'public');
            $update['avatar'] = 'storage/' . ltrim($storedPath, '/');
        }
        $user->update($update);

        // Mise à jour direction
        $assignment = UserDirectionAssignment::where('user_id', $user->id)->first();
        if (!empty($data['administration_type']) && !empty($data['administration_id'])) {
            $subEntity = $data['sub_entity_id'] ? SubEntity::find($data['sub_entity_id']) : null;
            $admin = $data['administration_type'] === 'emitter'
                ? IssuingAdministration::find($data['administration_id'])
                : RecipientAdministration::find($data['administration_id']);
            $assignData = [
                'user_id'              => $user->id,
                'direction_scope_type' => $data['administration_type'],
                'direction_scope_id'   => $data['administration_id'],
                'sub_entity_code'      => $subEntity?->code ?? null,
                'direction_label'      => $subEntity?->name ?? $admin?->name ?? '',
            ];
            if ($assignment) $assignment->update($assignData);
            else UserDirectionAssignment::create($assignData);
        } elseif ($assignment) {
            $assignment->delete();
        }

        return redirect()->route('admin.index', ['tab' => 'users'])->with('success', 'Utilisateur mis à jour.');
    }

    public function destroyUserTab(User $user)
    {
        if ($user->avatar && str_starts_with($user->avatar, 'storage/')) {
            $oldStorage = ltrim(substr($user->avatar, strlen('storage/')), '/');
            if (Storage::disk('public')->exists($oldStorage)) {
                Storage::disk('public')->delete($oldStorage);
            }
        } elseif ($user->avatar && str_starts_with($user->avatar, 'images/')) {
            $old = public_path($user->avatar);
            if (file_exists($old)) @unlink($old);
        }
        UserDirectionAssignment::where('user_id', $user->id)->delete();
        $user->delete();
        return redirect()->route('admin.index', ['tab' => 'users'])->with('success', 'Utilisateur supprimé.');
    }

    public function toggleUserStatusTab(User $user)
    {
        $user->update(['status' => $user->status === 'active' ? 'inactive' : 'active']);
        return redirect()->route('admin.index', ['tab' => 'users'])->with('success', 'Statut mis à jour.');
    }

    // ── Enrichissement IA (Ollama) des variables d'un template ─────────────────────
    public function aiEnrichTemplateVars(\App\Models\DocumentTemplate $template)
    {
        try {
            $ollama = new \App\Services\OllamaService();

            // Vérifier la disponibilité d'Ollama
            $available = $ollama->isAvailable();

            // Toujours refaire une détection brute pour capter les nouvelles balises
            // ajoutées récemment dans OnlyOffice (ou dans le contenu HTML du template).
            $detected = $this->extractVarsFromTemplateText($template->content);

            if ($template->storage_path) {
                $ext = strtolower(pathinfo($template->storage_path, PATHINFO_EXTENSION));
                if (in_array($ext, ['docx', 'xlsx', 'pptx'])) {
                    $absPath = str_starts_with($template->storage_path, 'images/')
                        ? public_path($template->storage_path)
                        : Storage::disk('public')->path($template->storage_path);
                    foreach ($this->extractVarsFromUploadedFile($absPath) as $fileVar) {
                        $detected[$fileVar['key']] = $fileVar;
                    }
                }
            }

            if (!empty($detected)) {
                $this->saveDetectedTemplateVars($template, array_values($detected));
            }

            $existingVars = $template->variables()->get();
            if ($existingVars->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune variable détectée dans le fichier actuel. Si vous venez d\'éditer dans OnlyOffice, enregistrez/fermez le document puis réessayez.',
                ], 422);
            }

            $rawArray = $existingVars->map(fn ($v) => ['key' => $v->key, 'label' => $v->label])->toArray();

            // Enrichir (avec IA ou fallback heuristique)
            if ($available) {
                $enriched = $ollama->enrichVariables($rawArray);
                $source   = 'ollama';
            } else {
                $enriched = $ollama->fallbackEnrich($rawArray);
                $source   = 'fallback';
            }

            // Mettre à jour chaque variable en base
            $validTypes = ['text', 'date', 'number', 'select', 'textarea'];
            foreach ($enriched as $item) {
                $key = $item['key'] ?? null;
                if (!$key) continue;
                $type = in_array($item['field_type'] ?? '', $validTypes, true) ? $item['field_type'] : 'text';
                $template->variables()
                    ->where('key', $key)
                    ->update([
                        'label'       => $item['label']       ?? $key,
                        'field_type'  => $type,
                        'required'    => (bool) ($item['required'] ?? false),
                        'placeholder' => $item['placeholder']  ?? '',
                    ]);
            }

            return response()->json([
                'success'   => true,
                'source'    => $source,
                'available' => $available,
                'message'   => ($available
                    ? '✅ ' . count($enriched) . ' variable(s) enrichie(s) par Ollama (' . $ollama->getModel() . ').'
                    : '⚠️ Ollama indisponible. Enrichissement heuristique appliqué sur ' . count($enriched) . ' variable(s).'),
                'variables' => $enriched,
                'count'     => count($enriched),
            ]);

        } catch (\Throwable $e) {
            Log::error('aiEnrichTemplateVars: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'enrichissement IA : ' . $e->getMessage(),
            ], 500);
        }
    }

    // ── Re-détection des variables [variable] pour un template existant ─────────────
    public function detectTemplateVars(\App\Models\DocumentTemplate $template)
    {
        $vars = $this->extractVarsFromTemplateText($template->content);

        if ($template->storage_path) {
            $ext = strtolower(pathinfo($template->storage_path, PATHINFO_EXTENSION));
            if (in_array($ext, ['docx', 'xlsx', 'pptx'])) {
                if (str_starts_with($template->storage_path, 'images/')) {
                    $absPath = public_path($template->storage_path);
                } else {
                    $absPath = Storage::disk('public')->path($template->storage_path);
                }

                foreach ($this->extractVarsFromUploadedFile($absPath) as $fileVar) {
                    $vars[$fileVar['key']] = $fileVar;
                }
            }
        }

        $vars = array_values($vars);
        $saved = $this->saveDetectedTemplateVars($template, $vars);

        if (count($vars) === 0) {
            return response()->json([
                'success' => false,
                'message' => 'Aucune variable trouvée dans le contenu du modèle ni dans un fichier Office associé. Utilisez des balises [variable] ou {{ variable }} puis enregistrez le modèle.',
                'variables' => [],
                'count' => 0,
            ]);
        }

        return response()->json([
            'success'   => true,
            'message'   => count($vars) . ' variable(s) trouvée(s), ' . $saved . ' nouvelle(s) enregistrée(s).',
            'variables' => $vars,
            'count'     => count($vars),
        ]);
    }

    /**
     * Récupération d'urgence: réextrait les variables d'un fichier déjà stocké
     * (utilisé si le template montre 0 variables mais a un fichier stocké)
     */
    public function recoverTemplateVars(\App\Models\DocumentTemplate $template)
    {
        if (!$template->storage_path) {
            return response()->json([
                'success' => false,
                'message' => 'Ce modèle n\'a pas de fichier stocké. Ouvrez-le dans OnlyOffice et enregistrez-le (Ctrl+S).',
                'count' => 0,
            ]);
        }

        $ext = strtolower(pathinfo($template->storage_path, PATHINFO_EXTENSION));
        if (!in_array($ext, ['docx', 'xlsx', 'pptx'])) {
            return response()->json([
                'success' => false,
                'message' => 'Ce fichier n\'est pas un format Office reconnu (DOCX/XLSX/PPTX).',
                'count' => 0,
            ]);
        }

        try {
            if (str_starts_with($template->storage_path, 'images/')) {
                $absPath = public_path($template->storage_path);
            } else {
                $absPath = Storage::disk('public')->path($template->storage_path);
            }

            if (!file_exists($absPath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Le fichier stocké n\'existe pas ou a été supprimé.',
                    'count' => 0,
                ]);
            }

            // Extraire et sauvegarder les variables
            $extractedVars = $this->extractVarsFromUploadedFile($absPath);
            $saved = 0;

            foreach ($extractedVars as $fileVar) {
                $var = \App\Models\TemplateVariable::firstOrCreate(
                    ['template_id' => $template->id, 'key' => $fileVar['key']],
                    [
                        'label'         => $fileVar['label'],
                        'field_type'    => 'text',
                        'required'      => false,
                        'placeholder'   => '',
                        'default_value' => '',
                        'options'       => json_encode([]),
                    ]
                );
                if ($var->wasRecentlyCreated) {
                    $saved++;
                }
            }

            $count = count($extractedVars);
            \Log::info('Template recovery: ' . $template->id . ' → ' . $count . ' var(s), ' . $saved . ' new');

            return response()->json([
                'success' => true,
                'message' => $count . ' variable(s) trouvée(s) et récupérée(s) (' . $saved . ' nouvelles).',
                'count' => $count,
                'saved' => $saved,
            ]);
        } catch (\Throwable $e) {
            \Log::error('Template recovery failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération: ' . $e->getMessage(),
                'count' => 0,
            ], 500);
        }
    }

    // ── État de sauvegarde OO d'un template (pour blocage UI à la fermeture) ──
    public function templateSaveState(\App\Models\DocumentTemplate $template)
    {
        $fileExists = false;
        $storagePath = (string) ($template->storage_path ?? '');

        if ($storagePath !== '') {
            if (str_starts_with($storagePath, 'images/')) {
                $fileExists = file_exists(public_path($storagePath));
            } else {
                $fileExists = Storage::disk('public')->exists($storagePath);
            }
        }

        return response()->json([
            'success'         => true,
            'template_id'     => (string) $template->id,
            'has_storage_path'=> $storagePath !== '',
            'file_saved'      => $fileExists,
            'variables_count' => $template->variables()->count(),
            'updated_at'      => (string) $template->updated_at,
        ]);
    }

    // ── Extraction automatique des variables [variable] depuis un fichier Office ────
    private function extractVarsFromUploadedFile(string $absFilePath): array
    {
        if (!class_exists('ZipArchive') || !file_exists($absFilePath)) return [];
        $zip = new \ZipArchive();
        if ($zip->open($absFilePath) !== true) return [];

        $found = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (!preg_match('/\.xml$/i', $name)) continue;
            if (preg_match('#\[Content_Types\]|_rels/#', $name)) continue;
            $xml = $zip->getFromIndex($i);
            if ($xml === false) continue;
            // Défragmenter les runs Word pour mieux détecter {{ }} saisis dans OO
            $isWordContent = preg_match('#word/(document|header|footer|endnote|footnote)#i', $name);
            $normalizedXml = $isWordContent ? $this->defragmentRuns((string) $xml) : $xml;
            $text = html_entity_decode(strip_tags((string) $normalizedXml), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            // Support des deux syntaxes : {{variable}} (ancien) et [variable] (nouveau)
            preg_match_all('/(?:\{\s*\{)\s*([^{}]+?)\s*(?:\}\s*\})/u', $text, $m1);
            preg_match_all('/\[([^\[\]]+?)\]/u', $text, $m2);
            $allMatches = array_merge($m1[1], $m2[1]);
            foreach ($allMatches as $orig) {
                $orig = trim($orig);
                if (!$orig) continue;
                $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $orig);
                $slug  = $ascii !== false && $ascii !== '' ? $ascii : $orig;
                $slug  = strtolower($slug);
                $slug  = str_replace("'", '_', $slug);
                $slug  = preg_replace('/[^a-z0-9]+/', '_', $slug);
                $slug  = trim($slug, '_') ?: 'var';
                if (!isset($found[$slug])) $found[$slug] = $orig;
            }
        }
        $zip->close();

        $result = [];
        foreach ($found as $slug => $orig) {
            $result[] = ['key' => $slug, 'label' => $orig];
        }
        return $result;
    }

    /**
     * Regroupe les runs Word d'un paragraphe quand il contient des placeholders.
     * Cela évite de perdre la détection des {{ variables }} fragmentées par OnlyOffice.
     */
    private function defragmentRuns(string $xml): string
    {
        return preg_replace_callback(
            '/<w:p[ >].*?<\/w:p>/s',
            function (array $match) {
                $para = $match[0];

                preg_match_all('/<w:t[^>]*>(.*?)<\/w:t>/s', $para, $texts);
                $fullText = implode('', $texts[1]);

                if (!preg_match('/\[[^\[\]]+\]/', $fullText) && strpos($fullText, '{{') === false) {
                    return $para;
                }

                $firstRpr = '';
                if (preg_match('/<w:r[ >].*?(<w:rPr>.*?<\/w:rPr>)/s', $para, $rprMatch)) {
                    $firstRpr = $rprMatch[1];
                }

                $pPr = '';
                if (preg_match('/<w:pPr>.*?<\/w:pPr>/s', $para, $pPrMatch)) {
                    $pPr = $pPrMatch[0];
                }

                $newRun = '<w:r>' . $firstRpr . '<w:t xml:space="preserve">' . $fullText . '</w:t></w:r>';
                return '<w:p>' . $pPr . $newRun . '</w:p>';
            },
            $xml
        ) ?? $xml;
    }

    private function extractVarsFromTemplateText(?string $content): array
    {
        if (!$content) return [];

        $text = trim(strip_tags(html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8')));
        if ($text === '') return [];

        $found = [];
        preg_match_all('/\{\{\s*([^{}]+?)\s*\}\}/u', $text, $m1);
        preg_match_all('/\[([^\[\]]+?)\]/u', $text, $m2);

        foreach (array_merge($m1[1], $m2[1]) as $orig) {
            $orig = trim($orig);
            if (!$orig) continue;
            $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $orig);
            $slug  = $ascii !== false && $ascii !== '' ? $ascii : $orig;
            $slug  = strtolower($slug);
            $slug  = str_replace("'", '_', $slug);
            $slug  = preg_replace('/[^a-z0-9]+/', '_', $slug);
            $slug  = trim($slug, '_') ?: 'var';
            if (!isset($found[$slug])) {
                $found[$slug] = ['key' => $slug, 'label' => $orig];
            }
        }

        return $found;
    }

    private function saveDetectedTemplateVars(DocumentTemplate $template, array $vars): int
    {
        $saved = 0;

        foreach ($vars as $v) {
            $created = \App\Models\TemplateVariable::firstOrCreate(
                ['template_id' => $template->id, 'key' => $v['key']],
                [
                    'label' => $v['label'],
                    'field_type' => 'text',
                    'required' => false,
                    'placeholder' => '',
                    'default_value' => '',
                    'options' => json_encode([]),
                ]
            );
            if ($created->wasRecentlyCreated) $saved++;
        }

        return $saved;
    }
}
