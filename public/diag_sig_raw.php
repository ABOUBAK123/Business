<?php
/**
 * Diagnostic signature API - réponses brutes complètes.
 * Génère un ticket support prêt à envoyer.
 *
 * ACCÈS: https://e-administration.gedsante.ci/diag_sig_raw.php?secret=DIAG2026
 * SUPPRIMER APRÈS USAGE.
 */

// ─── Sécurité minimale ────────────────────────────────────────────────────────
$secret = $_GET['secret'] ?? '';
if ($secret !== 'DIAG2026') {
    http_response_code(403);
    echo 'Accès interdit. Ajouter ?secret=DIAG2026';
    exit;
}

// ─── Bootstrap Laravel ───────────────────────────────────────────────────────
define('LARAVEL_START', microtime(true));
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$kernel->handle($request);

use Illuminate\Support\Facades\DB;
use App\Models\SignatureProviderConfig;
use Illuminate\Support\Facades\Http;

// ─── Config ──────────────────────────────────────────────────────────────────
$cfg = SignatureProviderConfig::where('is_active', true)->first();
if (!$cfg) {
    die('<pre>ERREUR: Aucune configuration API signature active trouvée en base.</pre>');
}

$rawEndpoint = $cfg->endpoint;
$endpoint    = rtrim($rawEndpoint, '/');
if (str_ends_with($endpoint, '/api')) {
    $endpoint = substr($endpoint, 0, -4);
}

$token     = $cfg->api_key;
$timeout   = max(10, (int) round(($cfg->timeout_ms ?: 30000) / 1000));
$verifySSL = (bool) $cfg->verify_ssl;

$client = Http::withToken($token)
    ->timeout($timeout)
    ->when(!$verifySSL, fn($h) => $h->withoutVerifying());

// ─── Helpers ─────────────────────────────────────────────────────────────────
function call(string $method, string $url, array $payload, $client): array {
    $t = microtime(true);
    try {
        $resp = match (strtoupper($method)) {
            'POST'  => $client->post($url, $payload),
            'PATCH' => $client->patch($url, $payload),
            'GET'   => $client->get($url, $payload),
            'PUT'   => $client->put($url, $payload),
            default => $client->post($url, $payload),
        };
        $elapsed = round((microtime(true) - $t) * 1000);
        return [
            'method'   => $method,
            'url'      => $url,
            'payload'  => $payload,
            'status'   => $resp->status(),
            'body'     => $resp->body(),
            'json'     => $resp->json(),
            'ok'       => $resp->successful(),
            'ms'       => $elapsed,
            'error'    => null,
        ];
    } catch (\Throwable $e) {
        return [
            'method'  => $method,
            'url'     => $url,
            'payload' => $payload,
            'status'  => 0,
            'body'    => '',
            'json'    => null,
            'ok'      => false,
            'ms'      => round((microtime(true) - $t) * 1000),
            'error'   => $e->getMessage(),
        ];
    }
}

function row(string $label, string $value, string $color = '#1f2937'): string {
    return "<tr><td style='padding:4px 12px 4px 4px;font-weight:600;color:#6b7280;white-space:nowrap'>{$label}</td>"
         . "<td style='padding:4px;color:{$color};word-break:break-all'>" . nl2br(htmlspecialchars($value)) . "</td></tr>";
}

function statusBadge(int $status): string {
    $ok = $status >= 200 && $status < 300;
    $bg = $ok ? '#16a34a' : '#dc2626';
    return "<span style='background:{$bg};color:white;padding:1px 6px;border-radius:4px;font-size:11px'>{$status}</span>";
}

function section(string $title, array $result): string {
    $badge = statusBadge($result['status']);
    $jsonPretty = is_array($result['json']) ? json_encode($result['json'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '(non-JSON)';
    $bg = $result['ok'] ? '#f0fdf4' : '#fef2f2';
    $border = $result['ok'] ? '#86efac' : '#fca5a5';
    $html = "<div style='margin-bottom:20px;background:{$bg};border:1px solid {$border};border-radius:8px;padding:14px'>";
    $html .= "<h3 style='margin:0 0 10px;font-size:15px;color:#1f2937'>{$title} {$badge} <span style='font-weight:400;font-size:12px;color:#6b7280'>{$result['ms']} ms</span></h3>";
    $html .= "<table style='width:100%;border-collapse:collapse;font-size:12px;font-family:monospace'>";
    $html .= row('Méthode', $result['method']);
    $html .= row('URL', $result['url']);
    if (!empty($result['payload'])) {
        $html .= row('Payload envoyé', json_encode($result['payload'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
    $html .= row('Status', (string) $result['status'], $result['ok'] ? '#16a34a' : '#dc2626');
    $html .= row('Body brut', $result['body']);
    $html .= row('Body JSON', $jsonPretty);
    if ($result['error']) {
        $html .= row('Exception', $result['error'], '#dc2626');
    }
    $html .= '</table></div>';
    return $html;
}

// ─── Début des appels ─────────────────────────────────────────────────────────
$steps = [];
$workflowId = null;
$ownerId = null;
$signerEmail = null;

// Récupérer l'email du premier utilisateur en base (signataire de test)
$firstUser = \App\Models\User::whereNotNull('email')->first();
$signerEmail = $firstUser ? $firstUser->email : 'test@example.com';
$signerName  = $firstUser ? ($firstUser->name ?? 'Utilisateur Test') : 'Utilisateur Test';
$nameParts   = preg_split('/\s+/', trim($signerName)) ?: [];
$firstName   = $nameParts[0] ?? 'Utilisateur';
$lastName    = trim(implode(' ', array_slice($nameParts, 1))) ?: 'Test';

// ── Étape 0: GET /api/users/me ───────────────────────────────────────────────
$r0 = call('GET', "{$endpoint}/api/users/me", [], $client);
$steps['S0 — GET /api/users/me'] = $r0;
if ($r0['ok'] && is_array($r0['json'])) {
    $ownerId = $r0['json']['id'] ?? $r0['json']['data']['id'] ?? $r0['json']['user']['id'] ?? null;
}

// ── Étape 1: GET /api/users?email= ───────────────────────────────────────────
$r1 = call('GET', "{$endpoint}/api/users", ['email' => $signerEmail], $client);
$steps["S1 — GET /api/users?email={$signerEmail}"] = $r1;

// ── Étape 2: POST workflow ─────────────────────────────────────────────────────
if ($ownerId) {
    $wflPayload = [
        'name'  => '[DIAG] Test workflow ' . date('Y-m-d H:i:s'),
        'steps' => [[
            'stepType'           => 'signature',
            'recipients'         => [[
                'email'     => $signerEmail,
                'firstName' => $firstName,
                'lastName'  => $lastName,
                'name'      => $signerName,
                'maxInvites'=> 1,
            ]],
            'requiredRecipients' => 1,
            'validityPeriod'     => 86400000,
            'invitePeriod'       => 86400000,
            'maxInvites'         => 1,
            'sendDownloadLink'   => false,
        ]],
        'workflowMode'   => 'FULL',
        'notifiedEvents' => ['workflowFinished'],
    ];
    $r2 = call('POST', "{$endpoint}/api/users/{$ownerId}/workflows", $wflPayload, $client);
    $steps['S2 — POST workflow (FULL mode)'] = $r2;

    if ($r2['ok'] && is_array($r2['json'])) {
        $workflowId = $r2['json']['id'] ?? null;
    }

    // Fallback workflow payload si le premier a échoué
    if (!$r2['ok']) {
        $wflPayloadSimple = [
            'name'  => '[DIAG] Test workflow simple ' . date('H:i:s'),
            'steps' => [[
                'stepType'   => 'signature',
                'recipients' => [[
                    'email'     => $signerEmail,
                    'firstName' => $firstName,
                    'lastName'  => $lastName,
                ]],
                'requiredRecipients' => 1,
            ]],
        ];
        $r2b = call('POST', "{$endpoint}/api/users/{$ownerId}/workflows", $wflPayloadSimple, $client);
        $steps['S2b — POST workflow (payload simple)'] = $r2b;
        if ($r2b['ok'] && is_array($r2b['json'])) {
            $workflowId = $r2b['json']['id'] ?? null;
        }
    }
}

// ── Étape 3: POST upload PDF de test (1 page vide) ────────────────────────────
if ($workflowId) {
    // Mini PDF valide 1 page (encodé en base64 inline)
    $miniPdf = base64_decode(
        'JVBERi0xLjQKMSAwIG9iago8PAovVHlwZSAvQ2F0YWxvZwovUGFnZXMgMiAwIFIKPj4KZW5kb2Jq' .
        'CjIgMCBvYmoKPDwKL1R5cGUgL1BhZ2VzCi9LaWRzIFszIDAgUl0KL0NvdW50IDEKPJ4KZW5kb2Jq' .
        'CjMgMCBvYmoKPDwKL1R5cGUgL1BhZ2UKL1BhcmVudCAyIDAgUgovTWVkaWFCb3ggWzAgMCA2MTIg' .
        'NzkyXQo+PgplbmRvYmoKeHJlZgowIDQKMDAwMDAwMDAwMCA2NTUzNSBmIAowMDAwMDAwMDA5IDAw' .
        'MDAwIG4gCjAwMDAwMDAwNTggMDAwMDAgbiAKMDAwMDAwMDExNSAwMDAwMCBuIAp0cmFpbGVyCjw8' .
        'Ci9TaXplIDQKL1Jvb3QgMSAwIFIKPj4Kc3RhcnR4cmVmCjE5MAolJUVPRgo='
    );

    $tmpPdf = sys_get_temp_dir() . '/diag_sig_test.pdf';
    file_put_contents($tmpPdf, $miniPdf);

    $uploadUrl = "{$endpoint}/api/workflows/{$workflowId}/parts?createDocuments=true";

    foreach (['document', 'file', 'part'] as $field) {
        $t = microtime(true);
        try {
            $uResp = Http::withToken($token)
                ->timeout($timeout)
                ->when(!$verifySSL, fn($h) => $h->withoutVerifying())
                ->attach($field, $miniPdf, 'diagnostic.pdf')
                ->post($uploadUrl);
            $elapsed = round((microtime(true) - $t) * 1000);
            $uResult = [
                'method'  => 'POST (multipart)',
                'url'     => $uploadUrl,
                'payload' => ['field' => $field, 'filename' => 'diagnostic.pdf'],
                'status'  => $uResp->status(),
                'body'    => $uResp->body(),
                'json'    => $uResp->json(),
                'ok'      => $uResp->successful(),
                'ms'      => $elapsed,
                'error'   => null,
            ];
        } catch (\Throwable $e) {
            $uResult = [
                'method'  => 'POST (multipart)',
                'url'     => $uploadUrl,
                'payload' => ['field' => $field],
                'status'  => 0,
                'body'    => '',
                'json'    => null,
                'ok'      => false,
                'ms'      => round((microtime(true) - $t) * 1000),
                'error'   => $e->getMessage(),
            ];
        }
        $steps["S3 — Upload PDF (field={$field})"] = $uResult;
        if ($uResult['ok']) break;
    }
    @unlink($tmpPdf);
}

// ── Étape 4: START workflow — toutes les variantes ────────────────────────────
if ($workflowId) {
    $startVariants = [
        ['PATCH', "{$endpoint}/api/workflows/{$workflowId}", ['workflowStatus' => 'started']],
        ['PATCH', "{$endpoint}/api/workflows/{$workflowId}", ['status' => 'started']],
        ['POST',  "{$endpoint}/api/workflows/{$workflowId}/start", []],
        ['POST',  "{$endpoint}/api/workflows/{$workflowId}/start", ['workflowStatus' => 'started']],
        ['PUT',   "{$endpoint}/api/workflows/{$workflowId}", ['workflowStatus' => 'started']],
    ];

    $startOk = false;
    foreach ($startVariants as $i => [$m, $u, $p]) {
        $r = call($m, $u, $p, $client);
        $steps["S4." . ($i + 1) . " — START ({$m} " . basename($u) . " status={$p['workflowStatus'] ?? $p['status'] ?? 'none'})"] = $r;
        if ($r['ok'] && !$startOk) {
            $startOk = true;
        }
        if ($r['ok']) break;
    }
}

// ── Étape 5: GET workflow details ─────────────────────────────────────────────
if ($workflowId) {
    $r5 = call('GET', "{$endpoint}/api/workflows/{$workflowId}", [], $client);
    $steps["S5 — GET workflow details (/{$workflowId})"] = $r5;
}

// ── Étape 6: INVITE — toutes les variantes ────────────────────────────────────
if ($workflowId) {
    $inviteVariants = [
        ['POST', "{$endpoint}/api/workflows/{$workflowId}/invite",      ['recipientEmail' => $signerEmail]],
        ['POST', "{$endpoint}/api/workflows/{$workflowId}/invite",      ['email' => $signerEmail]],
        ['POST', "{$endpoint}/api/workflows/{$workflowId}/invites",     ['recipientEmail' => $signerEmail]],
        ['POST', "{$endpoint}/api/workflows/{$workflowId}/invites",     ['email' => $signerEmail]],
        ['GET',  "{$endpoint}/api/workflows/{$workflowId}/invite",      ['recipientEmail' => $signerEmail]],
        ['POST', "{$endpoint}/api/workflows/{$workflowId}/invite-link", ['recipientEmail' => $signerEmail]],
    ];

    foreach ($inviteVariants as $i => [$m, $u, $p]) {
        $r = call($m, $u, $p, $client);
        $label = "S6." . ($i + 1) . " — INVITE ({$m} " . basename(parse_url($u, PHP_URL_PATH)) . ")";
        $steps[$label] = $r;
    }
}

// ── Étape 7: GET /api/workflows (liste) ──────────────────────────────────────
$r7 = call('GET', "{$endpoint}/api/workflows", [], $client);
$steps['S7 — GET /api/workflows (liste)'] = $r7;

// ── Étape 8: GET /api/users/{ownerId}/workflows ───────────────────────────────
if ($ownerId) {
    $r8 = call('GET', "{$endpoint}/api/users/{$ownerId}/workflows", [], $client);
    $steps["S8 — GET /api/users/{$ownerId}/workflows"] = $r8;
}

// ─── Construction du ticket support ──────────────────────────────────────────
$ticketLines = [];
$ticketLines[] = '=== TICKET SUPPORT SIGNATURE API ===';
$ticketLines[] = 'Date          : ' . date('Y-m-d H:i:s') . ' UTC';
$ticketLines[] = 'Application   : e-Parapheur (e-administration.gedsante.ci)';
$ticketLines[] = 'Endpoint conf : ' . $rawEndpoint;
$ticketLines[] = 'Endpoint norm : ' . $endpoint;
$ticketLines[] = 'Workflow ID   : ' . ($workflowId ?? 'N/A (création échouée)');
$ticketLines[] = 'Owner user ID : ' . ($ownerId ?? 'N/A');
$ticketLines[] = 'Signer email  : ' . $signerEmail;
$ticketLines[] = '';
$ticketLines[] = '--- RÉSULTATS BRUTS ---';

foreach ($steps as $label => $r) {
    $ticketLines[] = '';
    $ticketLines[] = "[$label]";
    $ticketLines[] = "  Methode  : {$r['method']}";
    $ticketLines[] = "  URL      : {$r['url']}";
    if (!empty($r['payload'])) {
        $ticketLines[] = "  Payload  : " . json_encode($r['payload']);
    }
    $ticketLines[] = "  Status   : {$r['status']}";
    $ticketLines[] = "  Body     : " . substr($r['body'], 0, 1000) . (strlen($r['body']) > 1000 ? '...[tronqué]' : '');
    if ($r['error']) {
        $ticketLines[] = "  Exception: {$r['error']}";
    }
}

$ticketLines[] = '';
$ticketLines[] = '--- PROBLÈME ---';
$ticketLines[] = 'Les endpoints /invite, /invites et /invite-link retournent 404 ou sans URL/token exploitable.';
$ticketLines[] = 'Le détail du workflow (GET /{workflowId}) ne contient aucun champ URL ni token.';
$ticketLines[] = 'Question: quel est l\'endpoint officiel pour obtenir le lien de signature après start ?';
$ticketLines[] = '=== FIN DU TICKET ===';

$ticketText = implode("\n", $ticketLines);

?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Diagnostic Signature API — Ticket Support</title>
<style>
  body { font-family: system-ui, sans-serif; background: #f9fafb; margin: 0; padding: 20px; color: #111827; }
  h1 { font-size: 20px; margin-bottom: 4px; }
  .meta { font-size: 13px; color: #6b7280; margin-bottom: 20px; }
  .card { background: white; border-radius: 8px; padding: 16px; box-shadow: 0 1px 3px rgba(0,0,0,.1); margin-bottom: 20px; }
  pre { background: #f3f4f6; padding: 12px; border-radius: 6px; overflow-x: auto; font-size: 11px; white-space: pre-wrap; word-break: break-all; }
  .btn-copy { background: #2563eb; color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-size: 13px; }
  .btn-copy:hover { background: #1d4ed8; }
  .summary-table td, .summary-table th { padding: 6px 10px; font-size: 12px; border-bottom: 1px solid #e5e7eb; }
  .ok { color: #16a34a; font-weight: 600; }
  .fail { color: #dc2626; font-weight: 600; }
</style>
</head>
<body>
<h1>Diagnostic Signature API</h1>
<div class="meta">
  Endpoint: <strong><?= htmlspecialchars($rawEndpoint) ?></strong> →
  normalisé: <strong><?= htmlspecialchars($endpoint) ?></strong>
  &nbsp;|&nbsp; workflowId: <strong><?= htmlspecialchars($workflowId ?? 'N/A') ?></strong>
  &nbsp;|&nbsp; ownerId: <strong><?= htmlspecialchars($ownerId ?? 'N/A') ?></strong>
</div>

<!-- Résumé rapide -->
<div class="card">
  <h2 style="margin:0 0 12px;font-size:15px">Résumé des étapes</h2>
  <table class="summary-table" style="width:100%;border-collapse:collapse">
    <thead><tr style="background:#f3f4f6">
      <th style="text-align:left">Étape</th>
      <th>Status HTTP</th>
      <th>OK ?</th>
      <th>ms</th>
    </tr></thead>
    <tbody>
<?php foreach ($steps as $label => $r): ?>
      <tr>
        <td><?= htmlspecialchars($label) ?></td>
        <td style="text-align:center"><?= $r['status'] ?></td>
        <td style="text-align:center" class="<?= $r['ok'] ? 'ok' : 'fail' ?>"><?= $r['ok'] ? '✓' : '✗' ?></td>
        <td style="text-align:right"><?= $r['ms'] ?></td>
      </tr>
<?php endforeach; ?>
    </tbody>
  </table>
</div>

<!-- Ticket support -->
<div class="card">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px">
    <h2 style="margin:0;font-size:15px">Ticket Support (prêt à copier)</h2>
    <button class="btn-copy" onclick="copyTicket()">Copier le ticket</button>
  </div>
  <pre id="ticket-text"><?= htmlspecialchars($ticketText) ?></pre>
</div>

<!-- Détails par étape -->
<h2 style="font-size:16px;margin-bottom:12px">Détails complets par étape</h2>
<?php foreach ($steps as $label => $r): ?>
  <?= section(htmlspecialchars($label), $r) ?>
<?php endforeach; ?>

<script>
function copyTicket() {
  const text = document.getElementById('ticket-text').innerText;
  navigator.clipboard.writeText(text).then(() => {
    const btn = document.querySelector('.btn-copy');
    btn.textContent = '✓ Copié !';
    btn.style.background = '#16a34a';
    setTimeout(() => { btn.textContent = 'Copier le ticket'; btn.style.background = ''; }, 2500);
  }).catch(() => {
    const area = document.createElement('textarea');
    area.value = text;
    area.style.position = 'fixed'; area.style.top = '-9999px';
    document.body.appendChild(area);
    area.select(); document.execCommand('copy');
    document.body.removeChild(area);
    alert('Ticket copié dans le presse-papiers.');
  });
}
</script>
</body>
</html>
