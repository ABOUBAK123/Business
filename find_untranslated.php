<?php
/**
 * Script pour identifier les textes français non traduits dans les Blade files
 *
 * Usage: php find_untranslated.php
 * Cela scannera tous les fichiers .blade.php et affichera les textes français
 */

$viewsPath = __DIR__ . '/resources/views';
$untranslatedTexts = [];
$filesScanned = 0;

// Fonction pour scanner les fichiers Blade
function scanBladeFiles($dir, &$untranslatedTexts, &$filesScanned)
{
    $files = scandir($dir);

    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;

        $filePath = $dir . '/' . $file;

        if (is_dir($filePath)) {
            scanBladeFiles($filePath, $untranslatedTexts, $filesScanned);
        } elseif (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
            $filesScanned++;
            $content = file_get_contents($filePath);

            // Chercher les patterns de texte français (heuristique simple)
            // Cherche les textes entre > et < qui ne sont pas des traductions
            if (preg_match_all('/>([À-ÿa-zA-Z\s,!?()&é-ù-]{10,})</i', $content, $matches)) {
                foreach ($matches[1] as $text) {
                    // Filtrer les faux positifs
                    if (!preg_match('/^__(/', $text) && !preg_match('/{{/', $text)) {
                        if (!isset($untranslatedTexts[$text])) {
                            $untranslatedTexts[$text] = [];
                        }
                        $untranslatedTexts[$text][] = str_replace($viewsPath . '/', '', $filePath);
                    }
                }
            }
        }
    }
}

echo "🔍 Scanning Blade files for untranslated French text...\n";
echo "================================================\n\n";

scanBladeFiles($viewsPath, $untranslatedTexts, $filesScanned);

echo "📊 Résultats:\n";
echo "- Files scanned: $filesScanned\n";
echo "- Potential untranslated texts: " . count($untranslatedTexts) . "\n\n";

if (count($untranslatedTexts) > 0) {
    echo "📝 Textes potentiellement non traduits:\n";
    echo "================================================\n";
    foreach ($untranslatedTexts as $text => $files) {
        echo "\nTexte: \"$text\"\n";
        echo "Fichiers: " . implode(', ', array_unique($files)) . "\n";
    }
} else {
    echo "✅ Aucun texte français non traduit détecté!\n";
}

echo "\n================================================\n";
echo "✨ Scan completed!\n";
