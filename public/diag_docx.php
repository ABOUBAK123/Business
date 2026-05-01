<?php
/**
 * Diagnostic DOCX – scan des placeholders non remplacés
 * Accès : https://e-administration.gedsante.ci/diag_docx.php
 *
 * SUPPRIMER ce fichier après diagnostic (données sensibles potentielles).
 */

// ─── Sécurité minimale ──────────────────────────────────────────────────────
define('DIAG_TOKEN', 'eparapheur_diag_2026');   // token à saisir dans l'URL
if (($_GET['token'] ?? '') !== DIAG_TOKEN) {
    http_response_code(403);
    echo '<h3>403 – Accès refusé. Ajoutez ?token=' . DIAG_TOKEN . ' à l\'URL.</h3>';
    exit;
}

// ─── Chemin des documents générés ──────────────────────────────────────────
$docRoot  = dirname(__DIR__) . '/storage/app/public/documents';
$filter   = trim($_GET['filter'] ?? '');   // ?filter=ccm   pour filtrer par nom
$limit    = max(1, (int)($_GET['limit'] ?? 10));

// ─── Regex de détection de placeholders ───────────────────────────────────
$phPatterns = [
    'double-accolade' => '/\{\{[^}]{1,120}\}\}/',
    'crochets'        => '/\[[^\[\]]{1,120}\]/',
];

// ─── Fonctions helpers ─────────────────────────────────────────────────────
function scanDocx(string $path, array $patterns): array
{
    if (!class_exists('ZipArchive')) {
        return [['error' => 'ZipArchive non disponible']];
    }
    $zip = new ZipArchive();
    if ($zip->open($path) !== true) {
        return [['error' => 'Impossible d\'ouvrir le ZIP']];
    }

    $results = [];
    for ($i = 0, $n = $zip->numFiles; $i < $n; $i++) {
        $stat = $zip->statIndex($i);
        $name = $stat['name'];
        if (!preg_match('/\.xml$/i', $name)) continue;
        if (preg_match('#\[Content_Types\]|_rels/#', $name)) continue;

        $xml = $zip->getFromIndex($i);
        if ($xml === false) continue;

        // Extraire le texte lisible (contenu des balises <w:t>)
        preg_match_all('/<w:t[^>]*>([^<]*)<\/w:t>/u', $xml, $tMatches);
        $plainText = implode('', $tMatches[1]);

        $found = [];
        foreach ($patterns as $label => $regex) {
            if (preg_match_all($regex, $plainText, $m)) {
                $found[$label] = array_values(array_unique($m[0]));
            }
            // Chercher aussi dans le XML brut (cas de fragmentation)
            if (preg_match_all($regex, $xml, $m2)) {
                $rawMatches = array_values(array_unique($m2[0]));
                if (!empty($rawMatches)) {
                    $found[$label . ' (xml brut)'] = $rawMatches;
                }
            }
        }

        if (!empty($found)) {
            $results[] = [
                'file'  => $name,
                'found' => $found,
            ];
        }
    }
    $zip->close();
    return $results;
}

// ─── Lister les fichiers DOCX ──────────────────────────────────────────────
$docxFiles = [];
if (is_dir($docRoot)) {
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($docRoot));
    foreach ($it as $f) {
        if (!$f->isFile()) continue;
        if (strtolower($f->getExtension()) !== 'docx') continue;
        if ($filter && stripos($f->getFilename(), $filter) === false) continue;
        $docxFiles[] = [
            'path'  => $f->getRealPath(),
            'name'  => $f->getFilename(),
            'mtime' => $f->getMTime(),
        ];
    }
    usort($docxFiles, fn($a, $b) => $b['mtime'] - $a['mtime']);
    $docxFiles = array_slice($docxFiles, 0, $limit);
}

// ─── Sortie HTML ────────────────────────────────────────────────────────────
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>Diagnostic DOCX – Placeholders</title>
<style>
  body { font-family: monospace; background:#111; color:#eee; padding:20px; }
  h1   { color:#7ef; }
  h2   { color:#aef; border-top:1px solid #333; padding-top:10px; }
  h3   { color:#fa7; }
  .ok  { color:#4d4; }
  .ko  { color:#f66; }
  .warn{ color:#fa0; }
  pre  { background:#1e1e2e; border:1px solid #444; padding:10px; overflow-x:auto; white-space:pre-wrap; word-break:break-all; }
  .badge { display:inline-block; padding:2px 6px; border-radius:4px; font-size:.8em; }
  .badge-red { background:#600; color:#faa; }
  .badge-blue{ background:#006; color:#aaf; }
  table { border-collapse:collapse; width:100%; }
  td,th { border:1px solid #444; padding:6px 10px; }
  th { background:#222; }
</style>
</head>
<body>
<h1>Diagnostic DOCX – Placeholders non remplacés</h1>
<p>
  Répertoire scanné : <code><?= htmlspecialchars($docRoot) ?></code><br>
  Filtre : <code><?= htmlspecialchars($filter ?: '(aucun)') ?></code> |
  Limite : <code><?= $limit ?></code> fichiers les plus récents<br>
  <small>Options URL : <code>?token=<?= DIAG_TOKEN ?>&amp;filter=ccm&amp;limit=5</code></small>
</p>

<?php if (empty($docxFiles)): ?>
  <p class="warn">Aucun fichier DOCX trouvé dans <code><?= htmlspecialchars($docRoot) ?></code>.</p>
<?php else: ?>
  <p>Fichiers analysés :</p>
  <table>
    <tr><th>#</th><th>Nom</th><th>Date de modif</th><th>Taille</th></tr>
    <?php foreach ($docxFiles as $idx => $df): ?>
    <tr>
      <td><?= $idx+1 ?></td>
      <td><?= htmlspecialchars($df['name']) ?></td>
      <td><?= date('Y-m-d H:i:s', $df['mtime']) ?></td>
      <td><?= number_format(filesize($df['path'])) ?> o</td>
    </tr>
    <?php endforeach; ?>
  </table>

  <?php foreach ($docxFiles as $idx => $df):
    $results = scanDocx($df['path'], $phPatterns);
  ?>
  <h2><?= $idx+1 ?>. <?= htmlspecialchars($df['name']) ?></h2>
  <?php if (empty($results)): ?>
    <p class="ok">✔ Aucun placeholder détecté – document propre.</p>
  <?php else: ?>
    <p class="ko">✘ Placeholders encore présents dans <?= count($results) ?> fichier(s) XML :</p>
    <?php foreach ($results as $r): ?>
      <?php if (isset($r['error'])): ?>
        <p class="warn">Erreur : <?= htmlspecialchars($r['error']) ?></p>
      <?php else: ?>
      <h3><?= htmlspecialchars($r['file']) ?></h3>
      <?php foreach ($r['found'] as $type => $matches): ?>
        <p><span class="badge badge-red"><?= htmlspecialchars($type) ?></span></p>
        <pre><?php foreach ($matches as $m) echo htmlspecialchars($m) . "\n"; ?></pre>
      <?php endforeach; ?>
      <?php endif; ?>
    <?php endforeach; ?>
  <?php endif; ?>
  <?php endforeach; ?>
<?php endif; ?>

<hr>
<h2>Logs Laravel récents (laravel.log, 80 dernières lignes)</h2>
<?php
$logFile = dirname(__DIR__) . '/storage/logs/laravel.log';
if (is_readable($logFile)) {
    $lines = file($logFile);
    $last  = array_slice($lines, -80);
    // Filtrer uniquement les lignes pertinentes
    $relevant = array_filter($last, fn($l) =>
        str_contains($l, 'GENERATE') ||
        str_contains($l, 'replaceInOfficeFile') ||
        str_contains($l, 'defragment') ||
        str_contains($l, 'MISS') ||
        str_contains($l, 'buildLoose') ||
        str_contains($l, 'SharedTemplate')
    );
    if (empty($relevant)) {
        echo '<p class="warn">Aucune ligne de log GENERATE/replaceInOfficeFile dans les 80 dernières lignes.</p>';
        echo '<pre>' . htmlspecialchars(implode('', $last)) . '</pre>';
    } else {
        echo '<pre>' . htmlspecialchars(implode('', $relevant)) . '</pre>';
    }
} else {
    echo '<p class="warn">Fichier log illisible : ' . htmlspecialchars($logFile) . '</p>';
}
?>

<p style="color:#555;font-size:.8em;margin-top:40px">
  ⚠ Supprimer ce fichier (<code>public/diag_docx.php</code>) après usage.
</p>
</body>
</html>
