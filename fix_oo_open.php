<?php
$f = __DIR__ . '/resources/views/admin/index.blade.php';
$lines = file($f);

// Trouver les lignes de tplOpenOnlyOffice()
$start = null; $end = null;
for ($i = 0; $i < count($lines); $i++) {
    if ($start === null && strpos($lines[$i], '// ─── Ouvrir l\'éditeur OnlyOffice (template sélectionné depuis la liste) ──') !== false) {
        $start = $i;
    }
    if ($start !== null && $end === null && strpos($lines[$i], 'window.tplOpenOnlyOffice = tplOpenOnlyOffice;') !== false) {
        $end = $i;
        break;
    }
}

if ($start === null || $end === null) {
    // Try alternative comment
    for ($i = 0; $i < count($lines); $i++) {
        if ($start === null && strpos($lines[$i], 'function tplOpenOnlyOffice()') !== false) {
            // find comment before
            $start = max(0, $i - 1);
        }
        if ($start !== null && $end === null && strpos($lines[$i], 'window.tplOpenOnlyOffice = tplOpenOnlyOffice;') !== false) {
            $end = $i;
            break;
        }
    }
}

if ($start === null || $end === null) {
    echo "ERREUR : bloc tplOpenOnlyOffice non trouvé. start=$start end=$end\n";
    exit(1);
}

echo "Bloc trouvé : lignes " . ($start+1) . " à " . ($end+1) . "\n";

$newBlock = <<<'JS'
    // ─── Ouvrir l'éditeur OnlyOffice directement ─────────────────────────────
    function tplOpenOnlyOffice() {
        if (!_ooUrl) {
            alert("URL OnlyOffice non configurée. Rendez-vous dans l'onglet OnlyOffice.");
            return;
        }
        // Ouvre le modal OO directement — l'utilisateur voit les boutons
        // Créer un modèle / Importer un fichier / Ajouter zone de signature
        openModal('modal-tpl-oo');

        // Si un template est déjà sélectionné, le charger automatiquement
        var tplId = window._ooCurrentTemplateId || null;
        if (tplId && _appPublicUrl) {
            tplOoShowStatus('Chargement en cours\u2026', 0);
            var csrf = document.querySelector('meta[name="csrf-token"]');
            fetch(_adminTplBase + '/' + tplId + '/oo-config', {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf ? csrf.content : '' }
            })
            .then(function(r) { return r.json(); })
            .then(function(cfg) {
                if (cfg.error) { tplOoShowStatus('Erreur : ' + cfg.error, 5000); return; }
                tplOoLoadEditor(cfg);
            })
            .catch(function(err) { tplOoShowStatus('Erreur réseau : ' + err.message, 5000); });
        }
    }
    window.tplOpenOnlyOffice = tplOpenOnlyOffice;

JS;

array_splice($lines, $start, $end - $start + 1, [$newBlock]);
file_put_contents($f, implode('', $lines));
echo "OK : tplOpenOnlyOffice réécrite (lignes " . ($start+1) . " à " . ($end+1) . ")\n";
