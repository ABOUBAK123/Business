<?php
$f = __DIR__ . '/resources/views/admin/index.blade.php';
$c = file_get_contents($f);

echo "Occurrences 'Colonne droite': " . substr_count($c, 'Colonne droite') . "\n";
echo "Occurrences 'Balises dynamiques': " . substr_count($c, 'Balises dynamiques') . "\n";

// Count divs in lines 107-700
$lines = explode("\n", $c);
$section = implode("\n", array_slice($lines, 106, 594));
$open  = preg_match_all('/<div[ >]/', $section);
$close = preg_match_all('/<\/div>/', $section);
echo "div open: $open / close: $close / delta: " . ($open - $close) . "\n";
