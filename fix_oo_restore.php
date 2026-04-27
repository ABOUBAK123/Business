<?php
$f = __DIR__ . '/resources/views/admin/index.blade.php';
$lines = file($f);

// === FIX 1 : Restaurer @json($selectedTplId) dans tplOpenOnlyOffice ===
// Trouver "var tplId = window._ooCurrentTemplateId || null;"
$tplIdLine = null;
for ($i = 0; $i < count($lines); $i++) {
    if (strpos($lines[$i], 'var tplId = window._ooCurrentTemplateId || null;') !== false) {
        // Vérifier qu'on est dans tplOpenOnlyOffice (chercher la fonction proche)
        for ($j = max(0, $i-15); $j < $i; $j++) {
            if (strpos($lines[$j], 'function tplOpenOnlyOffice') !== false) {
                $tplIdLine = $i;
                break;
            }
        }
        if ($tplIdLine !== null) break;
    }
}

if ($tplIdLine !== null) {
    $oldLine = $lines[$tplIdLine];
    $lines[$tplIdLine] = "        var tplId = @json(\$selectedTplId ?? null) || window._ooCurrentTemplateId || null;\n";
    echo "FIX 1 OK : ligne " . ($tplIdLine+1) . " corrigée\n";
    echo "  Avant : " . trim($oldLine) . "\n";
    echo "  Après : " . trim($lines[$tplIdLine]) . "\n";
} else {
    echo "FIX 1 SKIP : ligne tplId non trouvée dans tplOpenOnlyOffice\n";
}

// === FIX 2 : Corriger tplOoSubmitUpload pour utiliser le bon input file ===
// "var fileInput = form.querySelector('input[type=\"file\"]');"
$fileInputLine = null;
for ($i = 0; $i < count($lines); $i++) {
    if (strpos($lines[$i], "form.querySelector('input[type=\"file\"]')") !== false) {
        // Vérifier qu'on est dans tplOoSubmitUpload
        for ($j = max(0, $i-20); $j < $i; $j++) {
            if (strpos($lines[$j], 'function tplOoSubmitUpload') !== false) {
                $fileInputLine = $i;
                break;
            }
        }
        if ($fileInputLine !== null) break;
    }
}

if ($fileInputLine !== null) {
    $lines[$fileInputLine] = "        var fileInput = document.getElementById('tpl-oo-file-input');\n";
    echo "FIX 2 OK : ligne " . ($fileInputLine+1) . " - fileInput corrigé\n";
} else {
    echo "FIX 2 SKIP : fileInput non trouvé\n";
}

// Trouver "var formData = new FormData(form);" dans tplOoSubmitUpload
// et ajouter formData.append('file', ...) juste après
$formDataLine = null;
for ($i = 0; $i < count($lines); $i++) {
    if (strpos($lines[$i], 'var formData = new FormData(form)') !== false) {
        for ($j = max(0, $i-40); $j < $i; $j++) {
            if (strpos($lines[$j], 'function tplOoSubmitUpload') !== false) {
                $formDataLine = $i;
                break;
            }
        }
        if ($formDataLine !== null) break;
    }
}

if ($formDataLine !== null) {
    // Remplacer la ligne et ajouter l'append
    $lines[$formDataLine] = "        var formData = new FormData(form);\n" .
                            "        formData.append('file', fileInput.files[0]);\n" .
                            "        var upName = document.getElementById('tpl-oo-up-name');\n" .
                            "        if (upName && upName.value.trim()) formData.set('name', upName.value.trim());\n";
    echo "FIX 2b OK : ligne " . ($formDataLine+1) . " - formData.append('file') ajouté\n";
} else {
    echo "FIX 2b SKIP : formData non trouvé\n";
}

// === FIX 3 : Ajouter les fonctions manquantes après tplOoCloseUploadPanel ===
// Trouver "window.tplOoOpenUpload = tplOoOpenUpload;"
$insertAfter = null;
for ($i = 0; $i < count($lines); $i++) {
    if (strpos($lines[$i], 'window.tplOoOpenUpload = tplOoOpenUpload;') !== false) {
        $insertAfter = $i;
        break;
    }
}

// Vérifier que les fonctions n'existent pas déjà
$alreadyExists = false;
foreach ($lines as $l) {
    if (strpos($l, 'function tplOoHandleFileSelect') !== false) { $alreadyExists = true; break; }
}

if ($insertAfter !== null && !$alreadyExists) {
    $newFunctions = <<<'JS'

    // ─── Stocker le fichier sélectionné (utilisé par tplOoSubmitUpload) ───────
    window._ooSelectedFile = null;

    function tplOoHandleFileSelect(input) {
        if (!input.files || !input.files.length) return;
        window._ooSelectedFile = input.files[0];
        var info = document.getElementById('tpl-oo-upload-info');
        var fname = document.getElementById('tpl-oo-upload-fname');
        var fsize = document.getElementById('tpl-oo-upload-fsize');
        if (fname) fname.textContent = input.files[0].name;
        if (fsize) {
            var sz = input.files[0].size;
            fsize.textContent = sz < 1024*1024 ? (Math.round(sz/1024)) + ' Ko' : (Math.round(sz/1024/1024*10)/10) + ' Mo';
        }
        if (info) info.classList.remove('hidden');
        // Préremplir le champ nom si vide
        var nameInput = document.getElementById('tpl-oo-up-name');
        if (nameInput && !nameInput.value.trim()) {
            nameInput.value = input.files[0].name.replace(/\.[^/.]+$/, '');
        }
    }
    window.tplOoHandleFileSelect = tplOoHandleFileSelect;

    function tplOoHandleFileDrop(event) {
        var files = event.dataTransfer.files;
        if (!files || !files.length) return;
        var fakeInput = document.getElementById('tpl-oo-file-input');
        // Créer un DataTransfer pour affecter les fichiers à l'input
        try {
            var dt = new DataTransfer();
            dt.items.add(files[0]);
            fakeInput.files = dt.files;
        } catch(e) {}
        tplOoHandleFileSelect({ files: files });
    }
    window.tplOoHandleFileDrop = tplOoHandleFileDrop;

    function tplOoClearUpload() {
        window._ooSelectedFile = null;
        var fileInput = document.getElementById('tpl-oo-file-input');
        if (fileInput) fileInput.value = '';
        var info = document.getElementById('tpl-oo-upload-info');
        if (info) info.classList.add('hidden');
    }
    window.tplOoClearUpload = tplOoClearUpload;

    function tplOoCloseUploadPanel() {
        var panel = document.getElementById('tpl-oo-upload-panel');
        if (panel) panel.classList.add('hidden');
        tplOoClearUpload();
    }
    window.tplOoCloseUploadPanel = tplOoCloseUploadPanel;

JS;
    array_splice($lines, $insertAfter + 1, 0, [$newFunctions]);
    echo "FIX 3 OK : fonctions tplOoHandleFileSelect/Drop/ClearUpload/CloseUploadPanel ajoutées après ligne " . ($insertAfter+1) . "\n";
} elseif ($alreadyExists) {
    echo "FIX 3 SKIP : fonctions déjà présentes\n";
} else {
    echo "FIX 3 SKIP : point d'insertion non trouvé\n";
}

file_put_contents($f, implode('', $lines));
echo "\nFichier sauvegardé.\n";
