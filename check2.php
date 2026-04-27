<?php
require "vendor/autoload.php";
$app = require_once "bootstrap/app.php";
$app->make("Illuminate\Contracts\Console\Kernel")->bootstrap();

$t = App\Models\DocumentTemplate::find("019dc816-5d53-7043-bc74-aea1d8d27900");
if (!$t) {
    die("Template non trouvé\n");
}
$absPath = public_path($t->storage_path);

echo "=== VARIABLES EN BASE ===\n";
foreach ($t->variables as $v) {
    echo "  key=\"{$v->key}\" | label=\"{$v->label}\"\n";
}

echo "\n=== CONTENU XML BRUT (paragraphes avec variables) ===\n";
$zip = new ZipArchive();
if ($zip->open($absPath) !== TRUE) {
    die("Impossible d'ouvrir le fichier : $absPath\n");
}
$xml = $zip->getFromName("word/document.xml");
$zip->close();

preg_match_all("/<w:p[ >].*?<\/w:p>/s", $xml, $paras);
foreach ($paras[0] as $p) {
    preg_match_all("/<w:t[^>]*>(.*?)<\/w:t>/s", $p, $texts);
    $text = implode("", $texts[1]);
    if (strpos($text, "{{") !== false || strpos($text, "[") !== false) {
        echo "\n--- PARA brut (premiers 300 chars) ---\n";
        echo substr($p, 0, 300) . "\n";
        echo "--- TEXT concat: " . $text . "\n";
    }
}

echo "\n=== TEST DEFRAGMENTATION ===\n";
function defragmentRuns(string $xml): string {
    return preg_replace_callback(
        "/<w:p[ >].*?<\/w:p>/s",
        function (array $match) {
            $para = $match[0];
            preg_match_all("/<w:t[^>]*>(.*?)<\/w:t>/s", $para, $texts);
            $fullText = implode("", $texts[1]);
            if (!preg_match("/\[[^\[\]]+\]/", $fullText) && strpos($fullText, "{{") === false) {
                return $para;
            }
            $firstRpr = "";
            if (preg_match("/<w:r[ >].*?(<w:rPr>.*?<\/w:rPr>)/s", $para, $rprMatch)) {
                $firstRpr = $rprMatch[1];
            }
            $pPr = "";
            if (preg_match("/<w:pPr>.*?<\/w:pPr>/s", $para, $pPrMatch)) {
                $pPr = $pPrMatch[0];
            }
            preg_match("/^<w:p[^>]*>/", $para, $openTag);
            $open = $openTag[0] ?? "<w:p>";
            return $open . $pPr . "<w:r>" . $firstRpr . "<w:t xml:space=\"preserve\">" . $fullText . "</w:t>" . "</w:r>" . "</w:p>";
        },
        $xml
    );
}

$defragXml = defragmentRuns($xml);

$firstVar = $t->variables->first();
if ($firstVar) {
    $slug = $firstVar->key;
    $label = $firstVar->label;
    $testValue = "VALEUR_TEST_123";

    echo "Test: remplacer \"$label\" (slug: $slug) par \"$testValue\"\n";

    $pattern1 = "/\{\{\s*" . preg_quote($label, "/") . "\s*\}\}/iu";
    $count1 = preg_match_all($pattern1, $defragXml, $m1);
    echo "Pattern {{ $label }} -> trouvé: $count1 fois\n";
    if ($count1 > 0) echo "Match: " . $m1[0][0] . "\n";

    $pattern2 = "/\{\{\s*" . preg_quote($slug, "/") . "\s*\}\}/iu";
    $count2 = preg_match_all($pattern2, $defragXml, $m2);
    echo "Pattern {{ $slug }} -> trouvé: $count2 fois\n";
}

$pos = strpos($defragXml, "{{");
if ($pos !== false) {
    echo "\nContexte autour de {{ dans XML défragmenté:\n";
    echo substr($defragXml, max(0,$pos-50), 200) . "\n";
} else {
    echo "\n!!! {{ PAS TROUVÉ dans XML défragmenté !!!\n";
}

