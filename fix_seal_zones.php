<?php
$f = __DIR__ . '/resources/views/admin/index.blade.php';
$lines = file($f);

// ============================================================
// FIX A : Remplacer le bouton toolbar "Enregistrer" par "Sceller les zones"
// ============================================================
$saveBtn = null;
for ($i = 0; $i < count($lines); $i++) {
    if (strpos($lines[$i], 'onclick="tplOoSave()"') !== false && strpos($lines[$i], 'fa-save') !== false) {
        $saveBtn = $i;
        break;
    }
}
if ($saveBtn !== null) {
    $lines[$saveBtn] = '            <button type="button" onclick="tplOoSave()" id="tpl-oo-save-btn"' . "\n";
    echo "FIX A OK : bouton Enregistrer trouvé ligne " . ($saveBtn+1) . "\n";
    // Chercher la ligne du texte "Enregistrer" juste après
    for ($j = $saveBtn; $j <= $saveBtn + 4; $j++) {
        if (strpos($lines[$j], 'Enregistrer') !== false && strpos($lines[$j], 'fa-save') !== false) {
            $lines[$j] = '                <i class="fas fa-stamp text-xs"></i> Sceller les zones' . "\n";
            echo "FIX A2 OK : texte bouton changé ligne " . ($j+1) . "\n";
            break;
        }
    }
} else {
    echo "FIX A SKIP : bouton Enregistrer non trouvé\n";
}

// ============================================================
// FIX B : Réécrire tplOoCreateDraggableZone avec bouton "Sceller"
// ============================================================
// Trouver la ligne du innerHTML de la zone (contient "SIGNATURE " + n)
$innerHTMLStart = null;
$innerHTMLEnd   = null;
for ($i = 0; $i < count($lines); $i++) {
    if (strpos($lines[$i], "box.innerHTML = [") !== false) {
        // Vérifier qu'on est dans tplOoCreateDraggableZone
        for ($j = max(0, $i-60); $j < $i; $j++) {
            if (strpos($lines[$j], 'function tplOoCreateDraggableZone') !== false) {
                $innerHTMLStart = $i;
                break;
            }
        }
        if ($innerHTMLStart !== null) {
            // Trouver la fin du tableau ".join('')"
            for ($k = $innerHTMLStart; $k < min($innerHTMLStart+30, count($lines)); $k++) {
                if (strpos($lines[$k], ".join('')") !== false) {
                    $innerHTMLEnd = $k;
                    break;
                }
            }
            break;
        }
    }
}

if ($innerHTMLStart !== null && $innerHTMLEnd !== null) {
    echo "FIX B : innerHTML zone trouvé lignes " . ($innerHTMLStart+1) . " à " . ($innerHTMLEnd+1) . "\n";

    $newInnerHTML = <<<'JS'
        box.innerHTML = [
            '<div style="display:flex;flex-direction:column;align-items:center;gap:4px;pointer-events:none;width:100%;">',
            '  <div style="display:flex;align-items:center;gap:5px;">',
            '    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">',
            '      <path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"/>',
            '    </svg>',
            '    <span class="tpl-zone-label" style="font-size:12px;font-weight:700;letter-spacing:.5px;">SIGNATURE ' + n + '</span>',
            '  </div>',
            '  <span class="tpl-zone-hint" style="font-size:10px;opacity:0.7;">Glisser · Redimensionner</span>',
            '</div>',
            '<button class="tpl-zone-seal-btn" onclick="tplOoSealZone(' + idx + ',this)" title="Sceller cette zone" style="position:absolute;bottom:5px;left:50%;transform:translateX(-50%);background:#1d4ed8;border:none;color:#fff;font-size:10px;font-weight:700;padding:2px 10px;border-radius:12px;cursor:pointer;white-space:nowrap;">',
            '  <i class="fas fa-stamp"></i> Sceller',
            '</button>',
            '<div id="tpl-zone-resize-' + idx + '" style="position:absolute;bottom:4px;right:4px;width:14px;height:14px;background:#2563eb;border-radius:2px;cursor:se-resize;z-index:21;"></div>',
            '<button onclick="tplOoRemoveZone(' + idx + ')" style="position:absolute;top:3px;right:5px;background:none;border:none;cursor:pointer;color:#2563eb;font-size:15px;line-height:1;padding:0;" title="Supprimer">&#x2715;</button>'
        ].join('');

JS;

    array_splice($lines, $innerHTMLStart, $innerHTMLEnd - $innerHTMLStart + 1, [$newInnerHTML]);
    echo "FIX B OK : innerHTML zone remplacé avec bouton Sceller\n";
} else {
    echo "FIX B SKIP : innerHTML non trouvé (start=" . ($innerHTMLStart ?? 'null') . " end=" . ($innerHTMLEnd ?? 'null') . ")\n";
}

// ============================================================
// FIX C : Ajouter la fonction tplOoSealZone après tplOoRemoveZone
// ============================================================
$alreadyHasSeal = false;
foreach ($lines as $l) {
    if (strpos($l, 'function tplOoSealZone') !== false) { $alreadyHasSeal = true; break; }
}

if (!$alreadyHasSeal) {
    $insertAfter = null;
    for ($i = 0; $i < count($lines); $i++) {
        if (strpos($lines[$i], 'window.tplOoRemoveZone = tplOoRemoveZone;') !== false) {
            $insertAfter = $i;
            break;
        }
    }
    if ($insertAfter !== null) {
        $sealFn = <<<'JS'

    // ─── Sceller une zone de signature (verrouille visuellement la position) ──
    function tplOoSealZone(idx, btn) {
        var zone = _tplOoZones[idx];
        if (!zone || !zone.el) return;

        // Marquer la zone comme scellée
        zone.sealed = true;
        zone.el.style.border  = '2.5px solid #16a34a';
        zone.el.style.background = 'rgba(22,163,74,0.12)';
        zone.el.style.cursor  = 'default';

        // Changer les textes/couleurs
        var label = zone.el.querySelector('.tpl-zone-label');
        if (label) label.style.color = '#15803d';
        var hint = zone.el.querySelector('.tpl-zone-hint');
        if (hint) hint.textContent = 'Zone scellée';
        var resizeHandle = document.getElementById('tpl-zone-resize-' + idx);
        if (resizeHandle) resizeHandle.style.background = '#16a34a';

        // Remplacer le bouton Sceller par un badge vert
        var sealBtn = zone.el.querySelector('.tpl-zone-seal-btn');
        if (sealBtn) {
            sealBtn.innerHTML = '<i class="fas fa-check-circle"></i> Scellée';
            sealBtn.style.background = '#16a34a';
            sealBtn.style.cursor = 'default';
            sealBtn.onclick = null;
        }

        tplOoShowStatus('Zone "Signature ' + (idx + 1) + '" scellée ! Cliquez sur "Sceller les zones" pour enregistrer.', 4000);
    }
    window.tplOoSealZone = tplOoSealZone;

JS;
        array_splice($lines, $insertAfter + 1, 0, [$sealFn]);
        echo "FIX C OK : fonction tplOoSealZone ajoutée\n";
    } else {
        echo "FIX C SKIP : point d'insertion non trouvé\n";
    }
} else {
    echo "FIX C SKIP : tplOoSealZone déjà présent\n";
}

// ============================================================
// FIX D : Mettre à jour tplOoSave pour envoyer sealed=true sur toutes les zones
// ============================================================
// Trouver "body: JSON.stringify({ zones: _tplOoZones })"
$saveLine = null;
for ($i = 0; $i < count($lines); $i++) {
    if (strpos($lines[$i], 'body: JSON.stringify({ zones: _tplOoZones })') !== false) {
        $saveLine = $i;
        break;
    }
}
if ($saveLine !== null) {
    $lines[$saveLine] = '            body: JSON.stringify({ zones: _tplOoZones.map(function(z){ return { x: z.x, y: z.y, w: z.w, h: z.h, sealed: true, label: z.label || \'\' }; }) })' . "\n";
    echo "FIX D OK : JSON.stringify mis à jour ligne " . ($saveLine+1) . "\n";
} else {
    echo "FIX D SKIP : JSON.stringify non trouvé\n";
}

file_put_contents($f, implode('', $lines));
echo "\nFichier sauvegardé.\n";
