<?php
// Script pour patcher tplOoClose dans le blade
$filePath = __DIR__ . '/../resources/views/admin/index.blade.php';
$content = file_get_contents($filePath);

// === Patch 1 : tplOoClose — appeler requestSave() avant destroyEditor() ===
$old = <<<'EOL'
    // ─── Bouton : Fermer ──────────────────────────────────────────────────────
        function tplOoClose() {
        var tplId = window._ooCurrentTemplateId;
        tplOoHideOverlay();
        if (window._ooEditorInstance) {
            try { window._ooEditorInstance.destroyEditor(); } catch(e) {}
            window._ooEditorInstance = null;
        }
        var placeholder = document.getElementById('oo-editor-placeholder');
        if (placeholder) placeholder.innerHTML = '';
        var oldScript = document.getElementById('oo-api-script');
        if (oldScript) oldScript.remove();
        closeModal('modal-tpl-oo');

        // Auto-détecter les variables après fermeture (avec délai pour laisser OO envoyer le callback)
        if (tplId) {
            setTimeout(function() {
                var csrf = document.querySelector('meta[name="csrf-token"]');
                fetch(_adminTplBase + '/' + tplId + '/detect-vars', {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf ? csrf.content : '' }
                })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.success && data.count > 0) {
                        var label = document.getElementById('detect-label-' + tplId);
                        if (label) label.textContent = data.count + ' var(s)';
                        // Afficher notification discrète
                        var nb = document.createElement('div');
                        nb.style.cssText = 'position:fixed;bottom:24px;right:24px;background:#16a34a;color:#fff;padding:10px 18px;border-radius:8px;font-size:13px;z-index:9999;box-shadow:0 4px 12px rgba(0,0,0,.2)';
                        nb.textContent = '✓ ' + data.count + ' variable(s) détectée(s) dans le template.';
                        document.body.appendChild(nb);
                        setTimeout(function() { nb.remove(); }, 5000);
                    }
                })
                .catch(function() {});
            }, 3000); // attendre 3s que OO envoie son callback et que le serveur sauvegarde le fichier
        }
    }
EOL;

$new = <<<'EOL'
    // ─── Bouton : Fermer ──────────────────────────────────────────────────────
        function tplOoClose() {
        var tplId = window._ooCurrentTemplateId;

        // Étape 1 : demander la sauvegarde AVANT de fermer
        if (window._ooEditorInstance) {
            try { window._ooEditorInstance.requestSave(); } catch(e) {}
        }

        // Étape 2 : attendre 3s que OO envoie le callback status=2 et sauvegarde le fichier
        setTimeout(function() {
            tplOoHideOverlay();
            if (window._ooEditorInstance) {
                try { window._ooEditorInstance.destroyEditor(); } catch(e) {}
                window._ooEditorInstance = null;
            }
            var placeholder = document.getElementById('oo-editor-placeholder');
            if (placeholder) placeholder.innerHTML = '';
            var oldScript = document.getElementById('oo-api-script');
            if (oldScript) oldScript.remove();
            closeModal('modal-tpl-oo');

            // Étape 3 : détecter les variables après fermeture
            if (tplId) {
                setTimeout(function() {
                    var csrf = document.querySelector('meta[name="csrf-token"]');
                    fetch(_adminTplBase + '/' + tplId + '/detect-vars', {
                        method: 'POST',
                        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf ? csrf.content : '' }
                    })
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        if (data.success && data.count > 0) {
                            var label = document.getElementById('detect-label-' + tplId);
                            if (label) label.textContent = data.count + ' var(s)';
                            var nb = document.createElement('div');
                            nb.style.cssText = 'position:fixed;bottom:24px;right:24px;background:#16a34a;color:#fff;padding:10px 18px;border-radius:8px;font-size:13px;z-index:9999;box-shadow:0 4px 12px rgba(0,0,0,.2)';
                            nb.textContent = '✓ ' + data.count + ' variable(s) détectée(s) dans le template.';
                            document.body.appendChild(nb);
                            setTimeout(function() { nb.remove(); }, 5000);
                        }
                    })
                    .catch(function() {});
                }, 2000);
            }
        }, 3000);
    }
EOL;

if (strpos($content, $old) !== false) {
    $content = str_replace($old, $new, $content);
    echo "Patch 1 (tplOoClose) : OK\n";
} else {
    // Chercher par lignes clés pour diagnostic
    $line = 'try { window._ooEditorInstance.destroyEditor(); } catch(e) {}';
    $pos = strpos($content, $line);
    echo "Patch 1 : ECHEC - ancien texte non trouvé (destroyEditor à pos=" . $pos . ")\n";
    // Essayer avec une recherche moins stricte
    $pos2 = strpos($content, 'tplOoHideOverlay()');
    echo "tplOoHideOverlay à pos=" . $pos2 . "\n";
}

// === Patch 2 : message de status avec [variable] au lieu de {{variable}} ===
$old2a = "return '{' + '{' + v.key + '}' + '}';";
$new2a = "return '[' + v.key + ']';";
if (strpos($content, $old2a) !== false) {
    $content = str_replace($old2a, $new2a, $content);
    echo "Patch 2a (message syntaxe vars) : OK\n";
} else {
    echo "Patch 2a : non trouvé\n";
}
$old2b = "Vérifiez la syntaxe ' + '{' + '{variable}' + '}.";
$new2b = "Vérifiez la syntaxe [variable].";
if (strpos($content, $old2b) !== false) {
    $content = str_replace($old2b, $new2b, $content);
    echo "Patch 2b (message syntaxe) : OK\n";
} else {
    echo "Patch 2b : non trouvé\n";
}

file_put_contents($filePath, $content);
echo "Fichier sauvegardé.\n";
