<?php
/**
 * Diagnostic: inspecte le dernier template DOCX et simule exactement
 * ce que fait generateFromTemplate() étape par étape.
 *
 * Accès: http://localhost/e-administration_laravel/diag_template_zones.php
 */

$docxDir  = __DIR__ . '/public/storage/templates/';
$docxFiles = glob($docxDir . '*.docx');
if (!$docxFiles) {
    die('Aucun fichier .docx trouvé dans ' . $docxDir);
}
usort($docxFiles, fn($a,$b) => filemtime($b) - filemtime($a));

$docxPath = $docxFiles[0];
echo "<h2>Template inspecté : " . basename($docxPath) . "</h2>";

$z = new ZipArchive();
if ($z->open($docxPath) !== true) { die('Impossible d\'ouvrir le DOCX'); }
$xml = $z->getFromName('word/document.xml');
$z->close();

// ── Étape 1 : paragraphes bruts ───────────────────────────────────────────
echo "<h3>ÉTAPE 1 — Paragraphes dans le template original</h3><ol>";
preg_match_all('/<w:p[ >][\s\S]*?<\/w:p>/', $xml, $paras);
$allParas = $paras[0];
$nonEmpty = [];
foreach ($allParas as $i => $p) {
    preg_match_all('/<w:t[^>]*>([^<]*)<\/w:t>/', $p, $tm);
    $txt = implode('', $tm[1]);
    if (trim($txt) !== '') {
        $nonEmpty[$i+1] = $txt;
        $has3at = (strpos(strip_tags($p), '@@@') !== false);
        $flag   = $has3at ? ' <b style="color:red">[CONTIENT @@@]</b>' : '';
        echo "<li>#".($i+1)." ".htmlspecialchars($txt).$flag."</li>";
    }
}
echo "</ol>";

// ── Étape 2 : après sealSignatureZones ───────────────────────────────────
echo "<h3>ÉTAPE 2 — Après sealSignatureZones (paragraphes remplacés par @@@)</h3><ol>";
$signatureTableXml = '<w:tbl><w:tblPr><w:jc w:val="center"/></w:tblPr><w:tr><w:tc>'
    . '<w:p><w:r><w:t>⊛ Zone de signature</w:t></w:r></w:p>'
    . '</w:tc></w:tr></w:tbl>';

$sealed = preg_replace_callback(
    '/<w:p[ >][\s\S]*?<\/w:p>/',
    function (array $m) use ($signatureTableXml): string {
        if (str_contains(strip_tags($m[0]), '@@@')) {
            echo "<li style='color:red'>SUPPRIMÉ/REMPLACÉ: ".htmlspecialchars(strip_tags($m[0]))."</li>";
            return $signatureTableXml;
        }
        return $m[0];
    },
    $xml
);
echo "</ol>";

// Paragraphes restants après seal
echo "<h3>Paragraphes après sealSignatureZones</h3><ol>";
preg_match_all('/<w:p[ >][\s\S]*?<\/w:p>/', $sealed, $paras2);
foreach ($paras2[0] as $i => $p) {
    preg_match_all('/<w:t[^>]*>([^<]*)<\/w:t>/', $p, $tm);
    $txt = implode('', $tm[1]);
    if (trim($txt) !== '') {
        echo "<li>#".($i+1)." ".htmlspecialchars($txt)."</li>";
    }
}
echo "</ol>";

// ── Étape 3 : après replaceTemplateVariablesInDocxXml ────────────────────
echo "<h3>ÉTAPE 3 — Après remplacement variables (DATE=01/05/2026)</h3>";
$values = ['DATE' => '01/05/2026', 'Date_du_jour' => '01/05/2026', 'date du jour' => '01/05/2026'];

$normalizedValues = [];
foreach ($values as $k => $v) {
    $key = strtolower(preg_replace('/[\s\-_]+/', '_', trim((string)$k)));
    if ($key !== '') { $normalizedValues[$key] = (string)$v; }
}

$result = preg_replace_callback(
    '/<w:p[ >][\s\S]*?<\/w:p>/',
    function (array $m) use ($normalizedValues): string {
        $paraXml = $m[0];
        if (!preg_match_all('/<w:t(?:\s[^>]*)?>([^<]*)<\/w:t>/s', $paraXml, $tMatches)) {
            return $paraXml;
        }
        $fullText = implode('', $tMatches[1]);
        if (!str_contains($fullText, '{{')) { return $paraXml; }

        $replaced = preg_replace_callback(
            '/\{\{\s*([a-zA-Z0-9_\s\-]+?)\s*\}\}/u',
            function (array $mm) use ($normalizedValues): string {
                $raw  = trim((string)($mm[1] ?? ''));
                $key  = strtolower(preg_replace('/[\s\-_]+/', '_', $raw));
                if (array_key_exists($key, $normalizedValues)) return $normalizedValues[$key];
                $flat = preg_replace('/[\s\-_]+/', '', strtolower($raw));
                foreach ($normalizedValues as $nk => $nv) {
                    if (preg_replace('/[\s\-_]+/', '', $nk) === $flat) return $nv;
                }
                return $mm[0];
            },
            $fullText
        );

        if ($replaced === null || $replaced === $fullText) { return $paraXml; }

        $firstDone = false;
        $newPara = preg_replace_callback(
            '/<w:t(?:\s[^>]*)?>([^<]*)<\/w:t>/s',
            function (array $tm) use ($replaced, &$firstDone): string {
                if (!$firstDone) {
                    $firstDone = true;
                    preg_match('/^<w:t([^>]*)>/', $tm[0], $tagM);
                    $attrs = $tagM[1] ?? '';
                    if (!str_contains($attrs, 'xml:space') &&
                        (str_starts_with($replaced, ' ') || str_ends_with($replaced, ' '))) {
                        $attrs .= ' xml:space="preserve"';
                    }
                    $safe = htmlspecialchars($replaced, ENT_XML1 | ENT_COMPAT, 'UTF-8');
                    return "<w:t{$attrs}>{$safe}</w:t>";
                }
                return '<w:t/>';
            },
            $paraXml
        );

        return $newPara ?? $paraXml;
    },
    $sealed
);

echo "<ol>";
preg_match_all('/<w:p[ >][\s\S]*?<\/w:p>/', $result, $paras3);
foreach ($paras3[0] as $i => $p) {
    preg_match_all('/<w:t[^>]*>([^<]*)<\/w:t>/', $p, $tm);
    $txt = implode('', array_map(fn($t) => htmlspecialchars_decode($t), $tm[1]));
    if (trim($txt) !== '') {
        echo "<li>#".($i+1)." ".htmlspecialchars($txt)."</li>";
    }
}
echo "</ol>";

// ── Résumé des paragraphes perdus ─────────────────────────────────────────
echo "<h3 style='color:red'>PARAGRAPHES PERDUS entre étape 1 et étape finale</h3><ul>";
$finalTexts = [];
preg_match_all('/<w:p[ >][\s\S]*?<\/w:p>/', $result, $pf);
foreach ($pf[0] as $p) {
    preg_match_all('/<w:t[^>]*>([^<]*)<\/w:t>/', $p, $tm);
    $t = implode('', $tm[1]);
    if (trim($t) !== '') $finalTexts[] = $t;
}
foreach ($nonEmpty as $lineNum => $origTxt) {
    $found = false;
    foreach ($finalTexts as $ft) {
        if (str_contains($ft, substr(trim($origTxt), 0, 15))) { $found = true; break; }
    }
    if (!$found) {
        echo "<li style='color:red'>Ligne #$lineNum DISPARU: ".htmlspecialchars($origTxt)."</li>";
    }
}
echo "</ul>";
