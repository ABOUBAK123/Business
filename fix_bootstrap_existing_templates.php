<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$templates = \App\Models\DocumentTemplate::whereNull('storage_path')
    ->whereIn('file_type', ['docx', 'xlsx', 'pptx'])
    ->orderBy('created_at', 'desc')
    ->get();

echo "Templates without file: " . $templates->count() . PHP_EOL;

foreach ($templates as $t) {
    $ext = strtolower((string)($t->file_type ?: 'docx'));
    $blankMap = [
        'docx' => public_path('empty_template.docx'),
        'xlsx' => public_path('blank_xlsx.xlsx'),
        'pptx' => public_path('blank_pptx.pptx'),
    ];
    $src = $blankMap[$ext] ?? null;
    if (!$src || !file_exists($src)) {
        echo "SKIP {$t->id} missing blank source for {$ext}" . PHP_EOL;
        continue;
    }

    $destDir = public_path('images/templates');
    if (!is_dir($destDir)) {
        @mkdir($destDir, 0755, true);
    }
    $name = 'tpl_' . preg_replace('/[^a-zA-Z0-9_\-]/', '', (string)$t->id) . '_' . time() . '.' . $ext;
    $dest = $destDir . DIRECTORY_SEPARATOR . $name;
    if (!@copy($src, $dest)) {
        echo "FAIL copy {$t->id}" . PHP_EOL;
        continue;
    }

    if ($ext === 'docx' && class_exists('ZipArchive')) {
        $zip = new ZipArchive();
        if ($zip->open($dest) === true) {
            $xml = $zip->getFromName('word/document.xml');
            if ($xml !== false) {
                $hint = htmlspecialchars('NOUVEAU TEMPLATE : saisissez votre contenu ici puis enregistrez (Ctrl+S).', ENT_XML1, 'UTF-8');
                $hint2 = htmlspecialchars('Exemple de variable: {{ NOM DU DEMANDEUR }}', ENT_XML1, 'UTF-8');
                $insert = '<w:p><w:r><w:rPr><w:b/></w:rPr><w:t xml:space="preserve">' . $hint . '</w:t></w:r></w:p>'
                    . '<w:p><w:r><w:t xml:space="preserve">' . $hint2 . '</w:t></w:r></w:p>';
                $newXml = preg_replace('/<w:body>/i', '<w:body>' . $insert, $xml, 1);
                if (is_string($newXml) && $newXml !== $xml) {
                    $zip->addFromString('word/document.xml', $newXml);
                }
            }
            $zip->close();
        }
    }

    $t->storage_path = 'images/templates/' . $name;
    $t->save();

    echo "OK {$t->id} => {$t->storage_path}" . PHP_EOL;
}
