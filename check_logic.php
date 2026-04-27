<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$t = App\Models\DocumentTemplate::find('019dc816-5d53-7043-bc74-aea1d8d27900');
if (!$t) {
    echo "Template non trouve.\n";
    exit;
}
echo "=== VARIABLES EN BASE ===\n";
foreach ($t->variables as $v) {
    echo "key='{$v->key}' | label='{$v->label}' | type='{$v->field_type}'\n";
}

// Lire le XML Word pour voir le contenu brut
$absPath = public_path($t->storage_path);
echo "\n=== FICHIER: {$absPath} ===\n";
echo "Exists: " . (file_exists($absPath) ? 'OUI' : 'NON') . "\n";

if (file_exists($absPath)) {
    $zip = new ZipArchive();
    if ($zip->open($absPath) === TRUE) {
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();
        
        // Extraire les paragraphes contenant {{ ou [
        $paras = [];
        preg_match_all('/<w:p[ >].*?<\/w:p>/s', $xml, $m);
        foreach ($m[0] as $para) {
            preg_match_all('/<w:t[^>]*>(.*?)<\/w:t>/s', $para, $texts);
            $text = implode('', $texts[1]);
            if (strpos($text, '{') !== false || strpos($text, '[') !== false) {
                echo "PARA_TEXT: " . $text . "\n";
            }
        }
    } else {
        echo "Impossible d'ouvrir le fichier ZIP.\n";
    }
}
?>
