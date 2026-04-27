<?php
/**
 * Réécriture de tplOoCreateDraggableZone + tplOoSealZone
 * Nouveau comportement :
 *  - Zone libre (drag + resize)
 *  - Clic en dehors → verrouillage automatique à la position courante
 *  - Clic sur la zone verrouillée → déverrouille (permet de repositionner)
 *  - Enregistrer → sauvegarde
 */

$f = __DIR__ . '/resources/views/admin/index.blade.php';
$lines = file($f);

// ── Trouver le début et la fin de tplOoCreateDraggableZone ──────────────────
$fnStart = null; $fnEnd = null;
$depth   = 0; $inFn = false;
for ($i = 0; $i < count($lines); $i++) {
    if ($fnStart === null && strpos($lines[$i], 'function tplOoCreateDraggableZone(idx)') !== false) {
        $fnStart = $i; $depth = 0; $inFn = true;
    }
    if ($inFn) {
        $depth += substr_count($lines[$i], '{') - substr_count($lines[$i], '}');
        if ($fnStart !== null && $depth === 0 && $i > $fnStart) {
            $fnEnd = $i; break;
        }
    }
}

// ── Trouver tplOoSealZone (jusqu'à window.tplOoSealZone = ...) ──────────────
$sealStart = null; $sealEnd = null;
for ($i = 0; $i < count($lines); $i++) {
    if ($sealStart === null && strpos($lines[$i], 'function tplOoSealZone(idx') !== false) {
        $sealStart = $i;
    }
    if ($sealStart !== null && $sealEnd === null && strpos($lines[$i], 'window.tplOoSealZone = tplOoSealZone;') !== false) {
        $sealEnd = $i; break;
    }
}

echo "tplOoCreateDraggableZone : lignes " . ($fnStart+1) . " → " . ($fnEnd+1) . "\n";
echo "tplOoSealZone            : lignes " . ($sealStart+1) . " → " . ($sealEnd+1) . "\n";

if ($fnStart === null || $fnEnd === null || $sealStart === null || $sealEnd === null) {
    die("Impossible de localiser les fonctions. Abandon.\n");
}

// ── Nouvelle tplOoCreateDraggableZone ────────────────────────────────────────
$newCreate = <<<'JS'
    // ─── Créer une boîte de zone glissable ───────────────────────────────────
    function tplOoCreateDraggableZone(idx) {
        var container = document.getElementById('oo-iframe-container');
        if (!container) return;

        var box = document.createElement('div');
        var n = idx + 1;
        var initLeft = 10 + (idx % 3) * 5;
        var initTop  = 15 + (idx % 2) * 5;
        var initW    = 22;
        var initH    = 18;

        box.id = 'tpl-zone-box-' + idx;
        box.style.cssText = [
            'position:absolute',
            'left:' + initLeft + '%',
            'top:' + initTop + '%',
            'width:' + initW + '%',
            'height:' + initH + '%',
            'border:2.5px dashed #2563eb',
            'background:rgba(37,99,235,0.10)',
            'border-radius:6px',
            'z-index:20',
            'cursor:move',
            'user-select:none',
            'box-sizing:border-box',
            'display:flex',
            'flex-direction:column',
            'align-items:center',
            'justify-content:center',
            'gap:6px'
        ].join(';');

        box.innerHTML = [
            '<div style="display:flex;flex-direction:column;align-items:center;gap:4px;pointer-events:none;width:100%;">',
            '  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">',
            '    <path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"/>',
            '  </svg>',
            '  <span class="tpl-zone-label" style="font-size:12px;font-weight:700;letter-spacing:.5px;color:#1d4ed8;">SIGNATURE ' + n + '</span>',
            '  <span class="tpl-zone-hint" style="font-size:10px;color:#3b82f6;">Cliquez en dehors pour fixer</span>',
            '</div>',
            '<div id="tpl-zone-resize-' + idx + '" style="position:absolute;bottom:4px;right:4px;width:14px;height:14px;background:#2563eb;border-radius:2px;cursor:se-resize;z-index:21;"></div>',
            '<button onclick="tplOoRemoveZone(' + idx + ')" style="position:absolute;top:3px;right:5px;background:none;border:none;cursor:pointer;color:#2563eb;font-size:15px;line-height:1;padding:0;" title="Supprimer">&#x2715;</button>'
        ].join('');

        container.appendChild(box);
        _tplOoZones[idx] = { x: initLeft, y: initTop, w: initW, h: initH, el: box, sealed: false };

        // ── Drag ──────────────────────────────────────────────────────────────
        box.addEventListener('mousedown', function(e) {
            if (box._sealed) return;
            if (e.target.id && e.target.id.indexOf('tpl-zone-resize-') === 0) return;
            if (e.target.tagName === 'BUTTON') return;
            e.preventDefault(); e.stopPropagation();
            var rect = container.getBoundingClientRect();
            var startX = e.clientX, startY = e.clientY;
            var origLeft = parseFloat(box.style.left);
            var origTop  = parseFloat(box.style.top);
            var moved = false;

            function onMove(ev) {
                moved = true;
                var dx = ((ev.clientX - startX) / rect.width) * 100;
                var dy = ((ev.clientY - startY) / rect.height) * 100;
                box.style.left = Math.max(0, Math.min(100 - parseFloat(box.style.width),  origLeft + dx)) + '%';
                box.style.top  = Math.max(0, Math.min(100 - parseFloat(box.style.height), origTop  + dy)) + '%';
                _tplOoZones[idx].x = parseFloat(box.style.left);
                _tplOoZones[idx].y = parseFloat(box.style.top);
            }
            function onUp() {
                document.removeEventListener('mousemove', onMove);
                document.removeEventListener('mouseup',  onUp);
            }
            document.addEventListener('mousemove', onMove);
            document.addEventListener('mouseup',  onUp);
        });

        // ── Resize ────────────────────────────────────────────────────────────
        var resizeHandle = document.getElementById('tpl-zone-resize-' + idx);
        if (resizeHandle) {
            resizeHandle.addEventListener('mousedown', function(e) {
                if (box._sealed) return;
                e.preventDefault(); e.stopPropagation();
                var rect = container.getBoundingClientRect();
                var startX = e.clientX, startY = e.clientY;
                var origW = parseFloat(box.style.width);
                var origH = parseFloat(box.style.height);

                function onMove(ev) {
                    var newW = Math.max(8, Math.min(80, origW + ((ev.clientX - startX) / rect.width)  * 100));
                    var newH = Math.max(6, Math.min(80, origH + ((ev.clientY - startY) / rect.height) * 100));
                    box.style.width  = newW + '%';
                    box.style.height = newH + '%';
                    _tplOoZones[idx].w = newW;
                    _tplOoZones[idx].h = newH;
                }
                function onUp() {
                    document.removeEventListener('mousemove', onMove);
                    document.removeEventListener('mouseup',  onUp);
                }
                document.addEventListener('mousemove', onMove);
                document.addEventListener('mouseup',  onUp);
            });
        }

        // ── Clic en dehors → verrouille automatiquement la zone ───────────────
        function onDocMousedown(e) {
            if (box._sealed) return;
            if (box.contains(e.target)) return; // clic dans la zone → ignorer
            tplOoSealZone(idx);
        }
        document.addEventListener('mousedown', onDocMousedown);
        box._unsealListener = function() { document.removeEventListener('mousedown', onDocMousedown); };
    }

JS;

// ── Nouvelle tplOoSealZone ────────────────────────────────────────────────────
$newSeal = <<<'JS'
    // ─── Sceller : verrouille drag + resize, appliqué automatiquement au clic hors zone
    function tplOoSealZone(idx) {
        var zone = _tplOoZones[idx];
        if (!zone || !zone.el || zone.el._sealed) return;

        // Verrouillage physique via flag DOM
        zone.el._sealed = true;
        zone.sealed      = true;

        // Style verrouillé : bordure verte pleine, curseur bloqué
        zone.el.style.border     = '2.5px solid #16a34a';
        zone.el.style.background = 'rgba(22,163,74,0.13)';
        zone.el.style.cursor     = 'default';

        // Cacher le handle de resize
        var rh = document.getElementById('tpl-zone-resize-' + idx);
        if (rh) rh.style.display = 'none';

        // Mise à jour visuelle : label + hint verts
        var label = zone.el.querySelector('.tpl-zone-label');
        if (label) { label.style.color = '#15803d'; }
        var hint = zone.el.querySelector('.tpl-zone-hint');
        if (hint) {
            hint.textContent = '\uD83D\uDD12 Position fixée — cliquez pour repositionner';
            hint.style.color = '#15803d';
        }

        // Permettre de cliquer sur la zone pour la débloquer et repositionner
        zone.el.addEventListener('click', function onReopenClick(e) {
            if (e.target.tagName === 'BUTTON') return; // bouton supprimer → ignorer
            zone.el.removeEventListener('click', onReopenClick);
            tplOoUnsealZone(idx);
        }, { once: true });

        tplOoShowStatus('Zone "Signature ' + (idx + 1) + '" fixée. Cliquez dessus pour repositionner, ou "Enregistrer les zones".', 5000);
    }
    window.tplOoSealZone = tplOoSealZone;

    // ─── Débloquer une zone pour la repositionner ─────────────────────────────
    function tplOoUnsealZone(idx) {
        var zone = _tplOoZones[idx];
        if (!zone || !zone.el) return;

        // Remettre en mode libre
        zone.el._sealed = false;
        zone.sealed      = false;

        // Style libre : bordure bleue pointillée
        zone.el.style.border     = '2.5px dashed #2563eb';
        zone.el.style.background = 'rgba(37,99,235,0.10)';
        zone.el.style.cursor     = 'move';

        // Réafficher le handle resize
        var rh = document.getElementById('tpl-zone-resize-' + idx);
        if (rh) rh.style.display = '';

        // Remettre les textes bleus
        var label = zone.el.querySelector('.tpl-zone-label');
        if (label) label.style.color = '#1d4ed8';
        var hint = zone.el.querySelector('.tpl-zone-hint');
        if (hint) { hint.textContent = 'Cliquez en dehors pour fixer'; hint.style.color = '#3b82f6'; }

        // Ré-enregistrer le listener "clic en dehors"
        function onDocMousedown(e) {
            if (zone.el._sealed) return;
            if (zone.el.contains(e.target)) return;
            tplOoSealZone(idx);
        }
        document.addEventListener('mousedown', onDocMousedown);
        zone.el._unsealListener = function() { document.removeEventListener('mousedown', onDocMousedown); };

        tplOoShowStatus('Zone "Signature ' + (idx + 1) + '" déverrouillée. Repositionnez-la puis cliquez en dehors.', 4000);
    }
    window.tplOoUnsealZone = tplOoUnsealZone;

JS;

// ── Appliquer les remplacements ───────────────────────────────────────────────
// 1. Remplacer tplOoCreateDraggableZone
array_splice($lines, $fnStart, $fnEnd - $fnStart + 1, [$newCreate]);
echo "tplOoCreateDraggableZone réécrite.\n";

// Recalculer les indices après le premier splice
$offset = count(explode("\n", $newCreate)) - 1 - ($fnEnd - $fnStart + 1);
$sealStart2 = $sealStart + $offset;
$sealEnd2   = $sealEnd   + $offset;

// Chercher à nouveau (plus fiable après splice)
$sealStart2 = null; $sealEnd2 = null;
for ($i = 0; $i < count($lines); $i++) {
    if ($sealStart2 === null && strpos($lines[$i], 'function tplOoSealZone(idx') !== false) {
        $sealStart2 = $i;
    }
    if ($sealStart2 !== null && $sealEnd2 === null && strpos($lines[$i], 'window.tplOoSealZone = tplOoSealZone;') !== false) {
        $sealEnd2 = $i; break;
    }
}

if ($sealStart2 !== null && $sealEnd2 !== null) {
    // Supprimer aussi la ligne de commentaire doublon juste avant si elle existe
    $insertAt = $sealStart2;
    if ($insertAt > 0 && strpos($lines[$insertAt - 1], 'Sceller une zone de signature') !== false) {
        $insertAt--;
    }
    array_splice($lines, $insertAt, $sealEnd2 - $insertAt + 1, [$newSeal]);
    echo "tplOoSealZone réécrite (lignes " . ($sealStart2+1) . " → " . ($sealEnd2+1) . ").\n";
} else {
    echo "WARN : tplOoSealZone non trouvée après le premier splice.\n";
}

file_put_contents($f, implode('', $lines));
echo "\nFichier sauvegardé.\n";
