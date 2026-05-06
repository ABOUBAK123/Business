<?php
require __DIR__ . '/vendor/autoload.php';

// 1. Tester QR generation
echo "=== TEST 1: QR Code Generation ===\n";
try {
    $r = \Endroid\QrCode\Builder\Builder::create()
        ->writer(new \Endroid\QrCode\Writer\PngWriter())
        ->data('https://test.com/verify')
        ->size(300)
        ->margin(6)
        ->build();
    $qrBytes = $r->getString();
    echo "QR OK - taille PNG: " . strlen($qrBytes) . " bytes\n";

    $tmpPath = sys_get_temp_dir() . '/qr_test_' . uniqid() . '.png';
    file_put_contents($tmpPath, $qrBytes);
    echo "QR sauvegardé: " . $tmpPath . " (" . filesize($tmpPath) . " bytes)\n";
    @unlink($tmpPath);
} catch (\Throwable $e) {
    echo "QR ECHOUE: " . $e->getMessage() . "\n";
    echo "Classe erreur: " . get_class($e) . "\n";
}

// 2. Tester sys_get_temp_dir() writable
echo "\n=== TEST 2: sys_get_temp_dir() writable ===\n";
$tmp = sys_get_temp_dir();
echo "sys_get_temp_dir() = $tmp\n";
$testFile = $tmp . '/test_write_' . uniqid() . '.txt';
$ok = @file_put_contents($testFile, 'test');
echo "Writable: " . ($ok !== false ? "OUI" : "NON") . "\n";
if ($ok !== false) @unlink($testFile);

// 3. Vérifier si un DOCX template existe
echo "\n=== TEST 3: Templates DOCX partagés ===\n";
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$templates = \App\Models\DocumentTemplate::whereNotNull('storage_path')->limit(5)->get();
foreach ($templates as $tpl) {
    $path = \Illuminate\Support\Facades\Storage::disk('public')->path($tpl->storage_path);
    $exists = file_exists($path);
    $ext = pathinfo($tpl->storage_path, PATHINFO_EXTENSION);
    echo "Template [{$tpl->id}] '{$tpl->name}' admin_id=" . ($tpl->administration_id ?? 'NULL') . " ext={$ext} exists=" . ($exists ? 'OUI' : 'NON') . "\n";
}

// 4. Tester route qr.download existe
echo "\n=== TEST 4: Route qr.download ===\n";
try {
    $url = route('qr.download', ['token' => 'testtoken123']);
    echo "Route OK: $url\n";
} catch (\Throwable $e) {
    echo "Route ECHOUE: " . $e->getMessage() . "\n";
}

// 5. Tester injection footer sur un DOCX copie
echo "\n=== TEST 5: Test injectDocxFooterWithQr sur un vrai DOCX ===\n";
$tplWithDocx = \App\Models\DocumentTemplate::whereNotNull('storage_path')
    ->where('storage_path', 'like', '%.docx')
    ->first();

if ($tplWithDocx) {
    $srcPath = \Illuminate\Support\Facades\Storage::disk('public')->path($tplWithDocx->storage_path);
    if (file_exists($srcPath)) {
        $copyPath = sys_get_temp_dir() . '/test_footer_' . uniqid() . '.docx';
        copy($srcPath, $copyPath);

        // Tester si ZipArchive peut l'ouvrir
        $zip = new ZipArchive();
        $result = $zip->open($copyPath);
        if ($result === true) {
            $docXml = $zip->getFromName('word/document.xml');
            $hasFooterRef = strpos($docXml ?: '', 'w:footerReference') !== false;
            $hasSectPr = strpos($docXml ?: '', 'w:sectPr') !== false;
            echo "ZIP OK - hasSectPr=" . ($hasSectPr ? 'OUI' : 'NON') . " hasExistingFooterRef=" . ($hasFooterRef ? 'OUI' : 'NON') . "\n";
            $zip->close();
        } else {
            echo "ZIP ECHOUE code=$result\n";
        }
        @unlink($copyPath);
    } else {
        echo "Fichier DOCX template introuvable: $srcPath\n";
    }
} else {
    echo "Aucun template DOCX trouvé en base\n";
}

echo "\nDone.\n";
