<?php
$f = __DIR__ . '/resources/views/admin/index.blade.php';
$lines = file($f);

// Trouver la DEUXIÈME occurrence de modal-tpl-share et modal-tpl-oo
// (les duplicates — la première est correcte)

$ooCount = 0;
$shareCount = 0;
$secondShareStart = null;
$secondOoStart    = null;
$secondOoEnd      = null;

for ($i = 0; $i < count($lines); $i++) {
    if (strpos($lines[$i], 'id="modal-tpl-share"') !== false && strpos($lines[$i], 'adm-modal') !== false) {
        $shareCount++;
        if ($shareCount === 2) $secondShareStart = $i;
    }
    if (strpos($lines[$i], 'id="modal-tpl-oo"') !== false && strpos($lines[$i], 'adm-modal') !== false) {
        $ooCount++;
        if ($ooCount === 2) $secondOoStart = $i;
    }
}

echo "modal-tpl-share occurrences: $shareCount, 2ème à ligne " . ($secondShareStart !== null ? $secondShareStart+1 : 'non trouvée') . "\n";
echo "modal-tpl-oo occurrences: $ooCount, 2ème à ligne " . ($secondOoStart !== null ? $secondOoStart+1 : 'non trouvée') . "\n";

if ($secondShareStart === null || $secondOoStart === null) {
    echo "Rien à supprimer ou déjà corrigé.\n";
    exit(0);
}

// La seconde copie va de $secondShareStart jusqu'à la fin du second modal-tpl-oo
// On doit trouver la fermeture du second modal-tpl-oo
// Le modal-tpl-oo se ferme avec </div>\n</div> au niveau 0 — on utilise un compteur de balises

// Trouver la fin du second modal-tpl-oo
$depth = 0;
$secondOoEnd = null;
for ($i = $secondOoStart; $i < count($lines); $i++) {
    $opens  = substr_count($lines[$i], '<div');
    $closes = substr_count($lines[$i], '</div>');
    $depth += $opens - $closes;
    if ($depth <= 0 && $i > $secondOoStart) {
        $secondOoEnd = $i;
        break;
    }
}

if ($secondOoEnd === null) {
    // Fallback: find next @push or end of section after modal
    echo "Fin du second modal-tpl-oo non trouvée. Utilisation du fallback.\n";
    $secondOoEnd = $secondOoStart + 300;
}

echo "Plage à supprimer: lignes " . ($secondShareStart+1) . " à " . ($secondOoEnd+1) . " (" . ($secondOoEnd - $secondShareStart + 1) . " lignes)\n";

// Afficher les premières et dernières lignes pour vérification
echo "\nDébut du bloc à supprimer:\n";
for ($i = $secondShareStart; $i <= min($secondShareStart+3, $secondOoEnd); $i++) {
    echo "L" . ($i+1) . ": " . $lines[$i];
}
echo "\nFin du bloc à supprimer:\n";
for ($i = max($secondOoEnd-3, $secondShareStart); $i <= $secondOoEnd; $i++) {
    echo "L" . ($i+1) . ": " . $lines[$i];
}

// Supprimer le bloc
array_splice($lines, $secondShareStart, $secondOoEnd - $secondShareStart + 1);
file_put_contents($f, implode('', $lines));
echo "\nOK : " . ($secondOoEnd - $secondShareStart + 1) . " lignes supprimées (doublons modaux)\n";
