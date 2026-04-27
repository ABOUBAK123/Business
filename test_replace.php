<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Storage;

// Simuler le remplacement sur un docx existant
$srcPath  = Storage::disk('public')->path('templates/kam_1776561805.docx');
$testPath = sys_get_temp_dir() . '/test_replace.docx';
copy($srcPath, $testPath);

// Défragmenter et remplacer
function defragmentRuns(string $xml): string {
    return preg_replace_callback(
        '/<w:p[ >].*?<\/w:p>/s',
        function (array $match) {
            $para = $match[0];
            preg_match_all('/<w:t[^>]*>(.*?)<\/w:t>/s', $para, $texts);
            $fullText = implode('', $texts[1]);
            if (strpos($fullText, '{{') === false) return $para;

            $firstRpr = '';
            if (preg_match('/<w:r[ >].*?(<w:rPr>.*?<\/w:rPr>).*?<\/w:r>/s', $para, $rprMatch)) {
                $firstRpr = $rprMatch[1];
            }
            $pPr = '';
            if (preg_match('/<w:pPr>.*?<\/w:pPr>/s', $para, $pPrMatch)) {
                $pPr = $pPrMatch[0];
            }
            $escapedText = htmlspecialchars(html_entity_decode($fullText, ENT_XML1 | ENT_QUOTES, 'UTF-8'), ENT_XML1, 'UTF-8');
            preg_match('/^<w:p[^>]*>/', $para, $openTag);
            $open = $openTag[0] ?? '<w:p>';

            return $open . $pPr . '<w:r>' . $firstRpr . '<w:t xml:space="preserve">' . $escapedText . '</w:t></w:r></w:p>';
        },
        $xml
    );
}

$zip = new ZipArchive();
$zip->open($testPath);
$docXml = $zip->getFromName('word/document.xml');
$zip->close();

// Avant défragmentation : texte brut du premier {{
preg_match('/\{\{[^}]{0,60}\}\}/', strip_tags($docXml), $before);
echo "Avant (premier {{ trouvé via strip_tags): " . ($before[0] ?? 'aucun') . "\n";
preg_match('/\{\{[^}]{0,60}\}\}/', $docXml, $beforeRaw);
echo "Avant (dans XML brut) : " . ($beforeRaw[0] ?? 'FRAGMENTÉ - pas trouvé directement') . "\n";

// Défragmenter
$defrag = defragmentRuns($docXml);
preg_match('/\{\{[^}]{0,60}\}\}/', $defrag, $afterRaw);
echo "Après défrag (dans XML) : " . ($afterRaw[0] ?? 'toujours fragmenté??') . "\n";

// Faire le remplacement
$val = 'JEAN MARTIN TEST';
$result = preg_replace("/\{\{\s*N'DJOMON Ohouo Landry Marius\s*\}\}/u", $val, $defrag);
$changed = ($result !== $defrag);
echo "Remplacement effectué : " . ($changed ? "OUI ✓" : "NON ✗") . "\n";

// Vérifier dans le texte final
preg_match_all('/<w:t[^>]*>(.*?)<\/w:t>/s', $result, $texts);
$plain = implode('', $texts[1]);
echo "Texte brut après remplacement (300 chars) : " . substr($plain, 0, 300) . "\n";
