<?php
$path = 'C:/Users/hp/Downloads/[DOC-20260501-165053-418A07] Attestation de presence au poste bon test — 01-05-2026 16_50 (1).docx';

// Try alternate name patterns if needed
if (!file_exists($path)) {
    $dir = 'C:/Users/hp/Downloads/';
    $files = glob($dir . '*Attestation*presence*.docx');
    if ($files) $path = $files[0];
}

echo "File: $path\n";
echo "Exists: " . (file_exists($path) ? 'yes' : 'no') . "\n";

$zip = new ZipArchive();
$res = $zip->open($path);
if ($res !== true) { die("ZipArchive error: $res\n"); }

$xml = $zip->getFromName('word/document.xml');
$zip->close();

// Extract all text runs
preg_match_all('/<w:t[^>]*>([^<]*)<\/w:t>/', $xml, $m);
$texts = array_filter($m[1], fn($t) => trim($t) !== '');
echo "\n=== TEXT RUNS ===\n";
foreach ($texts as $t) {
    echo "  " . htmlspecialchars_decode($t) . "\n";
}

// Look for unreplaced variables
preg_match_all('/\{\{[^}]+\}\}/', $xml, $vars);
echo "\n=== UNREPLACED VARS in XML ===\n";
print_r(array_unique($vars[0]));

// Also check fragmented across runs
echo "\n=== RAW XML (first 5000 chars) ===\n";
echo substr($xml, 0, 5000);
