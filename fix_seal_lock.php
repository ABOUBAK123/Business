<?php
$f = __DIR__ . '/resources/views/admin/index.blade.php';
$lines = file($f);

$changes = 0;

for ($i = 0; $i < count($lines); $i++) {

    // FIX 1 : Ajouter if (box._sealed) return; dans le mousedown drag
    // Cherche la ligne du premier check du listener drag
    if (strpos($lines[$i], "if (e.target.id && e.target.id.indexOf('tpl-zone-resize-') === 0) return;") !== false) {
        // Vérifier que la ligne précédente contient le mousedown (pas déjà patchée)
        $prevContent = implode('', array_slice($lines, max(0,$i-5), 5));
        if (strpos($prevContent, 'box._sealed') === false && strpos($prevContent, 'mousedown') !== false) {
            array_splice($lines, $i, 0, ["            if (box._sealed) return; // zone scellée : drag bloqué\n"]);
            $changes++;
            $i++; // skip la ligne qu'on vient d'insérer
            echo "FIX 1 OK : if (box._sealed) ajouté avant le check resize (ligne " . ($i+1) . ")\n";
        }
    }

    // FIX 2 : Ajouter if (box._sealed) return; dans le mousedown resize
    // Cherche "e.preventDefault(); e.stopPropagation();" dans le resize handler
    if (strpos($lines[$i], 'e.preventDefault(); e.stopPropagation();') !== false) {
        $prevContent = implode('', array_slice($lines, max(0,$i-5), 5));
        if (strpos($prevContent, 'box._sealed') === false && strpos($prevContent, 'resizeHandle') !== false) {
            array_splice($lines, $i, 0, ["                if (box._sealed) return; // zone scellée : resize bloqué\n"]);
            $changes++;
            $i++;
            echo "FIX 2 OK : if (box._sealed) ajouté avant e.stopPropagation() (ligne " . ($i+1) . ")\n";
        }
    }
}

// FIX 3 : Réécrire tplOoSealZone pour setlter _sealed sur l'élément DOM
$sealStart = null; $sealEnd = null;
for ($i = 0; $i < count($lines); $i++) {
    if ($sealStart === null && strpos($lines[$i], 'function tplOoSealZone(idx, btn)') !== false) {
        $sealStart = $i;
    }
    if ($sealStart !== null && $sealEnd === null && strpos($lines[$i], 'window.tplOoSealZone = tplOoSealZone;') !== false) {
        $sealEnd = $i;
        break;
    }
}

if ($sealStart !== null && $sealEnd !== null) {
    echo "FIX 3 : tplOoSealZone trouvée lignes " . ($sealStart+1) . " à " . ($sealEnd+1) . "\n";
    $newSeal = <<<'JS'
    // ─── Sceller une zone : verrouille définitivement drag ET resize ──────────
    function tplOoSealZone(idx, btn) {
        var zone = _tplOoZones[idx];
        if (!zone || !zone.el) return;

        // FLAG sur l'élément DOM — lu par les listeners mousedown drag ET resize
        zone.el._sealed = true;
        zone.sealed = true;

        // Apparence : vert, curseur bloqué
        zone.el.style.border     = '2.5px solid #16a34a';
        zone.el.style.background = 'rgba(22,163,74,0.13)';
        zone.el.style.cursor     = 'not-allowed';

        // Cacher le handle de resize (inutile une fois scellé)
        var resizeHandle = document.getElementById('tpl-zone-resize-' + idx);
        if (resizeHandle) resizeHandle.style.display = 'none';

        // Mettre à jour les textes internes
        var label = zone.el.querySelector('.tpl-zone-label');
        if (label) label.style.color = '#15803d';
        var hint = zone.el.querySelector('.tpl-zone-hint');
        if (hint) hint.textContent = '\u26a0 Position verrouillée';

        // Remplacer le bouton "Sceller" par un badge vert statique
        var sealBtn = zone.el.querySelector('.tpl-zone-seal-btn');
        if (sealBtn) {
            var badge = document.createElement('span');
            badge.style.cssText = 'position:absolute;bottom:5px;left:50%;transform:translateX(-50%);background:#16a34a;color:#fff;font-size:10px;font-weight:700;padding:2px 10px;border-radius:12px;white-space:nowrap;';
            badge.innerHTML = '<i class="fas fa-lock"></i> Scellée';
            sealBtn.replaceWith(badge);
        }

        tplOoUpdateBadge();
        tplOoShowStatus('Zone "Signature ' + (idx + 1) + '" scellée et verrouillée ! Cliquez sur "Sceller les zones" pour enregistrer.', 5000);
    }
    window.tplOoSealZone = tplOoSealZone;

JS;
    array_splice($lines, $sealStart, $sealEnd - $sealStart + 1, [$newSeal]);
    $changes++;
    echo "FIX 3 OK : tplOoSealZone réécrite\n";
} else {
    echo "FIX 3 SKIP : tplOoSealZone non trouvée (start=" . ($sealStart ?? 'null') . ")\n";
}

file_put_contents($f, implode('', $lines));
echo "\nTotal: $changes modifications. Fichier sauvegardé.\n";
