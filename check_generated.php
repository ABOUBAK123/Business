<?php
$path = __DIR__ . '/public/storage/documents/AT TRAVAIL-20260426-050318.docx';
if (!file_exists($path)) {
    // Chercher le plus récent AT TRAVAIL
    $files = glob(__DIR__ . '/public/storage/documents/AT TRAVAIL*.docx');
    if (!$files) {
        $files = glob(__DIR__ . '/storage/app/public/documents/AT TRAVAIL*.docx');
    }
    if ($files) {
        usort($files, fn($a,$b) => filemtime($b) - filemtime($a));
        $path = $files[0];
    }
}

if (!file_exists($path)) {
    echo "Fichier introuvable!\n";
    // Lister tous les docx générés
    foreach (glob(__DIR__ . '/public/storage/documents/*.docx') as $f) {
        echo "  " . basename($f) . "\n";
    }
    die();
}

echo "Fichier: " . basename($path) . "\n";
echo "Taille: " . filesize($path) . " bytes\n\n";

$z = new ZipArchive();
if ($z->open($path) !== true) { echo "Impossible d'ouvrir le ZIP\n"; die(); }
$xml = $z->getFromName('word/document.xml');
$z->close();

preg_match_all('/\{\{[^}]+\}\}/', $xml, $m);
echo "Variables {{ }} restantes: " . count($m[0]) . "\n";
if ($m[0]) {
    echo "Encore présentes: " . implode(', ', array_unique($m[0])) . "\n";
} else {
    echo "AUCUNE variable - bien remplacées!\n";
}

// Chercher les valeurs TEST ou valeurs réelles
echo "\nMots-clés présents dans le XML:\n";
$keywords = ['NOM DU DEMANDEUR', 'MATRICULE', 'EMPLOI', 'FONCTION', 'SERVICE', 'date du jour'];
foreach ($keywords as $kw) {
    $count = substr_count($xml, $kw);
    echo "  '$kw': " . ($count > 0 ? "$count occurrences" : "absent") . "\n";
}
