<?php
/**
 * Diagnostic complet du flux API de signature (sigfae / SunnyStamp)
 * Accès: http://localhost/e-administration_laravel/check_sig_flow.php
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

header('Content-Type: text/html; charset=utf-8');

function box(string $title, string $content, string $color = '#e8f5e9'): void
{
    echo "<div style='background:{$color};border:1px solid #aaa;border-radius:6px;padding:12px;margin-bottom:14px;'>";
    echo "<strong style='font-size:1.05em'>{$title}</strong>";
    echo "<pre style='white-space:pre-wrap;word-break:break-all;margin:8px 0 0;font-size:0.85em;'>{$content}</pre>";
    echo "</div>";
}

function prettyJson(mixed $v): string
{
    if (is_string($v)) {
        $decoded = json_decode($v, true);
        if ($decoded !== null) return json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        return $v;
    }
    return json_encode($v, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Diagnostic Signature API</title></head><body style='font-family:monospace;padding:20px;max-width:900px;margin:auto;'>";
echo "<h2>🔬 Diagnostic flux API Signature (sigfae)</h2>";
echo "<div style='background:#e3f2fd;border:1px solid #90caf9;border-radius:6px;padding:8px 12px;margin-bottom:12px;'>Version diagnostic: <strong>v2026-05-05-step3c-full-start-matrix</strong></div>";

// ─── Récupérer la config ────────────────────────────────────────────────────
$cfg = DB::table('signature_provider_configs')->where('is_active', 1)->first();

if (!$cfg) {
    box('❌ Config introuvable', 'Aucune configuration API active en base.', '#ffebee');
    echo "</body></html>";
    exit;
}

$rawEndpoint = $cfg->endpoint;
$endpoint    = rtrim($rawEndpoint, '/');
if (str_ends_with($endpoint, '/api')) {
    $endpoint = substr($endpoint, 0, -4);
}
$token = $cfg->api_key;

box('📋 Configuration lue', implode("\n", [
    "endpoint brut   : {$rawEndpoint}",
    "endpoint normalisé: {$endpoint}",
    "api_key (début) : " . substr($token, 0, 30) . '...',
    "consent_page_id : " . ($cfg->consent_page_id ?? '—'),
    "sig_profile_id  : " . ($cfg->signature_profile_id ?? '—'),
    "verify_ssl      : " . ($cfg->verify_ssl ? 'true' : 'false'),
]));

$client = Http::withToken($token)
    ->timeout(15)
    ->when(!(bool)$cfg->verify_ssl, fn($h) => $h->withoutVerifying());

// ─── STEP 1 : GET /api/users/me ─────────────────────────────────────────────
echo "<h3>Étape 1 — GET /api/users/me</h3>";
try {
    $r = $client->get("{$endpoint}/api/users/me");
    $color = $r->successful() ? '#e8f5e9' : '#ffebee';
    box("HTTP {$r->status()}", prettyJson($r->body()), $color);

    $me = $r->json();
    $ownerUserId = $me['id'] ?? $me['data']['id'] ?? null;
    box('→ ownerUserId extrait', (string)$ownerUserId);
} catch (\Throwable $e) {
    box('❌ Exception', $e->getMessage(), '#ffebee');
    $ownerUserId = null;
}

if (!$ownerUserId) {
    box('⛔ Arrêt', 'Impossible de récupérer ownerUserId — vérifiez la clé API.', '#ffebee');
    echo "</body></html>";
    exit;
}

// ─── STEP 2 : GET /api/users?email= ─────────────────────────────────────────
echo "<h3>Étape 2 — Recherche utilisateur par email</h3>";
$testEmail = $me['email'] ?? 'a.kamagate@modernisation.gouv.ci';
echo "<p>Email testé : <strong>{$testEmail}</strong></p>";
try {
    $r = $client->get("{$endpoint}/api/users", ['email' => $testEmail]);
    $color = $r->successful() ? '#e8f5e9' : '#ffebee';
    box("HTTP {$r->status()}", prettyJson($r->body()), $color);
} catch (\Throwable $e) {
    box('❌ Exception', $e->getMessage(), '#ffebee');
}

// ─── STEP 3 : Créer un workflow test ────────────────────────────────────────
echo "<h3>Étape 3 — Création du workflow</h3>";

$recipient = [
    'id'        => $ownerUserId,
    'userId'    => $ownerUserId,
    'email'     => $testEmail,
    'firstName' => $me['firstName'] ?? 'Test',
    'lastName'  => $me['lastName'] ?? 'User',
    'name'      => $me['name'] ?? ($me['firstName'] ?? 'Test') . ' ' . ($me['lastName'] ?? 'User'),
];

$payload = [
    'name'         => 'Test Diag ' . date('H:i:s'),
    'description'  => 'Workflow diagnostic automatique',
    'workflowMode' => 'FULL',
    'steps'        => [[
        'stepType'           => 'signature',
        'name'               => 'Signature',
        'recipients'         => [$recipient],
        'requiredRecipients' => 1,
        'validityPeriod'     => 86400000,
        'invitePeriod'       => 86400000,
        'maxInvites'         => 1,
        'sendDownloadLink'   => false,
    ]],
    'recipients'   => [$recipient],
];

if (!empty($cfg->consent_page_id)) {
    $payload['consentPageId'] = $cfg->consent_page_id;
}
if (!empty($cfg->signature_profile_id)) {
    $payload['signatureProfileId'] = $cfg->signature_profile_id;
}

box('Payload envoyé', prettyJson($payload), '#f3f4f6');
try {
    $r = $client->post("{$endpoint}/api/users/{$ownerUserId}/workflows", $payload);
    $color = $r->successful() ? '#e8f5e9' : '#ffebee';
    box("HTTP {$r->status()}", prettyJson($r->body()), $color);

    $wfBody      = $r->json();
    $workflowId  = $wfBody['id'] ?? $wfBody['workflowId'] ?? $wfBody['data']['id'] ?? null;
    box('→ workflowId extrait', (string)$workflowId);
} catch (\Throwable $e) {
    box('❌ Exception', $e->getMessage(), '#ffebee');
    $workflowId = null;
}

if (!$workflowId) {
    box('⛔ Arrêt', 'workflowId non obtenu.', '#ffebee');
    echo "</body></html>";
    exit;
}

// ─── STEP 3b : Upload d'un PDF existant dans le workflow ───────────────────
echo "<h3>Étape 3b — Upload d'un PDF dans le workflow</h3>";

$pdfAbsolutePath = null;
$pdfDebug = [];

$docs = DB::table('documents')
    ->select(['id', 'title', 'file_path', 'mime_type'])
    ->whereNotNull('file_path')
    ->orderByDesc('created_at')
    ->limit(30)
    ->get();

foreach ($docs as $doc) {
    $fp = trim((string) ($doc->file_path ?? ''));
    if ($fp === '') {
        continue;
    }

    $normalized = ltrim($fp, '/');
    if (str_starts_with($normalized, 'public/')) {
        $normalized = substr($normalized, 7);
    }
    if (str_starts_with($normalized, 'storage/')) {
        $normalized = substr($normalized, 8);
    }

    $candidates = array_values(array_unique(array_filter([
        __DIR__ . '/public/' . ltrim($fp, '/'),
        __DIR__ . '/storage/app/public/' . $normalized,
        __DIR__ . '/' . ltrim($fp, '/'),
    ])));

    foreach ($candidates as $cand) {
        if (is_string($cand) && $cand !== '' && is_file($cand) && is_readable($cand)) {
            $pdfAbsolutePath = $cand;
            $pdfDebug[] = "document source: {$doc->id} | {$doc->title} | {$fp}";
            $pdfDebug[] = "path retenu: {$cand}";
            break 2;
        }
    }
}

if (!$pdfAbsolutePath) {
    box('⚠️ PDF introuvable', "Aucun PDF existant lisible trouvé dans la base/fichiers. Le start échouera avec NoDocumentToSignInWorkflow.", '#fff3e0');
} else {
    box('PDF sélectionné', implode("\n", $pdfDebug), '#e3f2fd');

    $uploadQuery = ['createDocuments' => 'true'];
    if (!empty($cfg->signature_profile_id)) {
        $uploadQuery['signatureProfileId'] = $cfg->signature_profile_id;
    }

    $pdfBytes = file_get_contents($pdfAbsolutePath);
    if ($pdfBytes === false) {
        box('❌ Upload', 'Impossible de lire le PDF sélectionné.', '#ffebee');
    } else {
        $uploadVariants = [
            ['field' => 'document', 'url' => "{$endpoint}/api/workflows/{$workflowId}/parts?" . http_build_query($uploadQuery)],
            ['field' => 'file',     'url' => "{$endpoint}/api/workflows/{$workflowId}/parts?" . http_build_query($uploadQuery)],
            ['field' => 'part',     'url' => "{$endpoint}/api/workflows/{$workflowId}/parts?" . http_build_query($uploadQuery)],
            ['field' => 'document', 'url' => "{$endpoint}/api/workflows/{$workflowId}/parts"],
            ['field' => 'file',     'url' => "{$endpoint}/api/workflows/{$workflowId}/parts"],
            ['field' => 'document', 'url' => "{$endpoint}/api/workflows/{$workflowId}/documents"],
            ['field' => 'file',     'url' => "{$endpoint}/api/workflows/{$workflowId}/documents"],
        ];

        $uploadOk = false;
        foreach ($uploadVariants as $uv) {
            try {
                $r = $client
                    ->attach($uv['field'], $pdfBytes, basename($pdfAbsolutePath), ['Content-Type' => 'application/pdf'])
                    ->post($uv['url']);
                $color = $r->successful() ? '#e8f5e9' : '#f5f5f5';
                box("UPLOAD {$uv['field']} {$uv['url']} → HTTP {$r->status()}", prettyJson($r->body()), $color);
                if ($r->successful()) {
                    $uploadOk = true;
                    break;
                }
            } catch (\Throwable $e) {
                box("❌ Exception upload {$uv['field']} {$uv['url']}", $e->getMessage(), '#ffebee');
            }
        }

        if (!$uploadOk) {
            box('⚠️ Aucun upload réussi', 'Le start échouera probablement faute de document.', '#fff3e0');
        }
    }
}

// ─── STEP 4 : GET état du workflow (avant start) ────────────────────────────
echo "<h3>Étape 4 — État workflow avant start</h3>";
try {
    $r = $client->get("{$endpoint}/api/workflows/{$workflowId}");
    box("HTTP {$r->status()}", prettyJson($r->body()), $r->successful() ? '#e8f5e9' : '#fff3e0');
} catch (\Throwable $e) {
    box('❌ Exception', $e->getMessage(), '#ffebee');
}

// ─── STEP 5 : Start workflow ─────────────────────────────────────────────────
echo "<h3>Étape 5 — Start workflow</h3>";

$startPayloads = [
    ['method' => 'PATCH', 'ct' => 'application/merge-patch+json', 'body' => ['workflowStatus' => 'started']],
    ['method' => 'PATCH', 'ct' => 'application/merge-patch+json', 'body' => ['workflowStatus' => 'in_progress']],
    ['method' => 'PATCH', 'ct' => 'application/merge-patch+json', 'body' => ['workflowStatus' => 'STARTED']],
    ['method' => 'PATCH', 'ct' => 'application/json',             'body' => ['workflowStatus' => 'started']],
    ['method' => 'PATCH', 'ct' => 'application/json',             'body' => ['workflowStatus' => 'in_progress']],
    ['method' => 'PATCH', 'ct' => 'application/json',             'body' => ['status' => 'started']],
    ['method' => 'PATCH', 'ct' => 'application/json',             'body' => ['status' => 'in_progress']],
    ['method' => 'PUT',   'ct' => 'application/json',             'body' => ['workflowStatus' => 'started']],
    ['method' => 'PUT',   'ct' => 'application/json',             'body' => ['workflowStatus' => 'in_progress']],
    ['method' => 'POST',  'ct' => 'application/json',             'body' => [], 'url_suffix' => '/start'],
    ['method' => 'POST',  'ct' => 'application/json',             'body' => ['workflowStatus' => 'started'], 'url_suffix' => '/start'],
    ['method' => 'POST',  'ct' => 'application/json',             'body' => ['status' => 'started'], 'url_suffix' => '/start'],
];

$startedOk = false;
foreach ($startPayloads as $sp) {
    $url = "{$endpoint}/api/workflows/{$workflowId}" . ($sp['url_suffix'] ?? '');
    try {
        $req = $client->withHeader('Content-Type', $sp['ct']);
        $r = match($sp['method']) {
            'PATCH' => $req->patch($url, $sp['body']),
            'PUT'   => $req->put($url, $sp['body']),
            'POST'  => $req->post($url, $sp['body']),
        };
        $label = "{$sp['method']} {$url} (CT: {$sp['ct']}) → HTTP {$r->status()}";
        $color  = $r->successful() ? '#e8f5e9' : '#fff3e0';
        box($label, prettyJson($r->body()), $color);

        if ($r->successful()) {
            $startedOk = true;
            break;
        }
    } catch (\Throwable $e) {
        box("❌ Exception {$sp['method']} {$url}", $e->getMessage(), '#ffebee');
    }
}

if (!$startedOk) {
    box('⚠️ Aucun start réussi', 'Toutes les tentatives de démarrage ont échoué.', '#fff3e0');
}

// ─── STEP 6 : GET état après start ──────────────────────────────────────────
echo "<h3>Étape 6 — État workflow APRÈS start</h3>";
try {
    $r = $client->get("{$endpoint}/api/workflows/{$workflowId}");
    box("HTTP {$r->status()}", prettyJson($r->body()), $r->successful() ? '#e8f5e9' : '#fff3e0');

    // Chercher une URL de signature directe dans la réponse
    $wfDetail = $r->json();
    $directUrl = null;
    foreach (['signingUrl','accessUrl','signUrl','inviteUrl','url','link'] as $f) {
        if (!empty($wfDetail[$f]) && is_string($wfDetail[$f])) {
            $directUrl = $wfDetail[$f]; break;
        }
        if (!empty($wfDetail['data'][$f]) && is_string($wfDetail['data'][$f])) {
            $directUrl = $wfDetail['data'][$f]; break;
        }
    }
    if ($directUrl) {
        box('✅ URL directe dans workflow details', $directUrl, '#e3f2fd');
    }
} catch (\Throwable $e) {
    box('❌ Exception', $e->getMessage(), '#ffebee');
}

// ─── STEP 7 : Toutes les variantes d'invite ──────────────────────────────────
echo "<h3>Étape 7 — Tentatives endpoint invite (toutes variantes)</h3>";

$inviteVariants = [
    ['method'=>'POST','url'=>"{$endpoint}/api/workflows/{$workflowId}/invite",      'body'=>['recipientEmail'=>$testEmail]],
    ['method'=>'POST','url'=>"{$endpoint}/api/workflows/{$workflowId}/invite",      'body'=>['email'=>$testEmail]],
    ['method'=>'POST','url'=>"{$endpoint}/api/workflows/{$workflowId}/invite",      'body'=>['recipientId'=>$ownerUserId]],
    ['method'=>'GET', 'url'=>"{$endpoint}/api/workflows/{$workflowId}/invite",      'query'=>['recipientEmail'=>$testEmail]],
    ['method'=>'POST','url'=>"{$endpoint}/api/workflows/{$workflowId}/invites",     'body'=>['recipientEmail'=>$testEmail]],
    ['method'=>'POST','url'=>"{$endpoint}/api/workflows/{$workflowId}/invite-link", 'body'=>['recipientEmail'=>$testEmail]],
    ['method'=>'GET', 'url'=>"{$endpoint}/api/workflows/{$workflowId}/invite-link", 'query'=>['recipientEmail'=>$testEmail]],
    ['method'=>'POST','url'=>"{$endpoint}/api/users/{$ownerUserId}/invite",         'body'=>['workflowId'=>$workflowId]],
    ['method'=>'GET', 'url'=>"{$endpoint}/api/workflows/{$workflowId}/signing-url", 'query'=>[]],
    ['method'=>'GET', 'url'=>"{$endpoint}/api/workflows/{$workflowId}/sign",        'query'=>[]],
];

$foundInviteUrl = null;
foreach ($inviteVariants as $iv) {
    try {
        if ($iv['method'] === 'GET') {
            $r = $client->get($iv['url'], $iv['query'] ?? []);
        } else {
            $r = $client->post($iv['url'], $iv['body'] ?? []);
        }
        $color = $r->successful() ? '#e8f5e9' : '#f5f5f5';
        box("{$iv['method']} {$iv['url']} → HTTP {$r->status()}", prettyJson($r->body()), $color);

        if ($r->successful() && !$foundInviteUrl) {
            $body = $r->json();
            // Chercher URL ou token
            foreach (['inviteUrl','invite_url','url','link','signingUrl','accessUrl'] as $f) {
                if (!empty($body[$f]) && is_string($body[$f])) {
                    $foundInviteUrl = $body[$f];
                    break;
                }
            }
            foreach (['token','inviteToken','invite_token','accessToken'] as $f) {
                if (!empty($body[$f]) && is_string($body[$f])) {
                    $foundInviteUrl = rtrim($endpoint, '/') . '/invite?token=' . $body[$f];
                    break;
                }
            }
        }
    } catch (\Throwable $e) {
        box("❌ Exception {$iv['method']} {$iv['url']}", $e->getMessage(), '#ffebee');
    }
}

// ─── Résumé ─────────────────────────────────────────────────────────────────
echo "<h3>📊 Résumé</h3>";
if ($foundInviteUrl) {
    box('✅ URL d\'invitation trouvée', $foundInviteUrl, '#e8f5e9');
    echo "<p><a href='" . htmlspecialchars($foundInviteUrl) . "' target='_blank' style='font-size:1.1em;color:blue;'>➡ Ouvrir cette URL</a></p>";
} else {
    box('⚠️ Aucune URL d\'invitation extraite', "Vérifiez les réponses des étapes ci-dessus.", '#fff3e0');
}

echo "<p style='color:#888;font-size:0.8em;margin-top:30px'>workflowId créé pour ce test: <code>{$workflowId}</code></p>";
echo "</body></html>";
