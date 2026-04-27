<?php
$f = __DIR__ . '/resources/views/admin/index.blade.php';
$lines = file($f);

// Trouver la ligne de succès dans tplOoSubmitCreate
// "Modele cree avec succes !"
$targetLine = null;
for ($i = 0; $i < count($lines); $i++) {
    if (strpos($lines[$i], "Modele cree avec succes !") !== false && strpos($lines[$i], 'tplOoShowStatus') !== false) {
        $targetLine = $i;
        break;
    }
}

if ($targetLine === null) {
    echo "ERREUR : ligne cible non trouvée\n";
    exit(1);
}

echo "Ligne cible: " . ($targetLine + 1) . "\n";
echo "Contenu: " . $lines[$targetLine];

// Remplacer cette ligne par : showStatus + loadEditor + injectInList
$newLines = [
    "            tplOoClosCreatePanel();\n",
    "            tplOoShowStatus('Modele cree avec succes ! Chargement de l\\'editeur...', 0);\n",
    "            tplOoInjectInList(data);\n",
    "            if (nameEl) nameEl.value = '';\n",
    "            if (fileEl) fileEl.value = '';\n",
    "            if (typeEl) typeEl.value = '';\n",
    "            // Charger l'editeur OO pour le nouveau template\n",
    "            if (data.id && _ooUrl) {\n",
    "                var csrfMeta = document.querySelector('meta[name=\"csrf-token\"]');\n",
    "                fetch(_adminTplBase + '/' + data.id + '/oo-config', {\n",
    "                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfMeta ? csrfMeta.content : '' }\n",
    "                })\n",
    "                .then(function(r) { return r.json(); })\n",
    "                .then(function(cfg) {\n",
    "                    if (cfg.error) { tplOoShowStatus('Modele cree. Erreur chargement editeur: ' + cfg.error, 6000); return; }\n",
    "                    tplOoLoadEditor(cfg);\n",
    "                })\n",
    "                .catch(function(err) { tplOoShowStatus('Modele cree. Erreur reseau: ' + err.message, 5000); });\n",
    "            }\n",
];

// Trouver les 4 lignes à remplacer (tplOoClosCreatePanel + tplOoShowStatus + tplOoInjectInList + les 3 reset)
// Ligne targetLine = tplOoShowStatus
// Ligne targetLine-1 = tplOoClosCreatePanel
// Lignes targetLine+1, +2, +3 = if (nameEl)...
$startReplacement = $targetLine - 1;
// Find where the block ends (after the 3 value resets)
$endReplacement = $targetLine + 4; // tplOoClosCreatePanel through typeEl.value = ''

// Verify
echo "\nBefore:\n";
for ($i = $startReplacement; $i <= $endReplacement; $i++) {
    echo "L" . ($i+1) . ": " . $lines[$i];
}

array_splice($lines, $startReplacement, $endReplacement - $startReplacement + 1, $newLines);
file_put_contents($f, implode('', $lines));
echo "\nOK : tplOoSubmitCreate patché pour charger l'éditeur après création\n";
