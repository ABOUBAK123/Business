<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\DocumentTemplate;
use App\Models\TemplateVariable;

$tpl = DocumentTemplate::where('name', 'like', '%test 2 attestation%')->latest()->first();
if (!$tpl) {
    echo "NO_TEMPLATE\n";
    exit(0);
}

echo "ID={$tpl->id}\n";
echo "NAME={$tpl->name}\n";
echo "TYPE={$tpl->file_type}\n";
echo "STORAGE={$tpl->storage_path}\n";
echo "UPDATED_AT={$tpl->updated_at}\n";

$path = (string) ($tpl->storage_path ?? '');
$abs = '';
if ($path !== '') {
    if (str_starts_with($path, 'images/')) {
        $abs = public_path($path);
    } else {
        $abs = Illuminate\Support\Facades\Storage::disk('public')->path($path);
    }
}
echo "ABS={$abs}\n";
echo "EXISTS=" . (($abs && file_exists($abs)) ? '1' : '0') . "\n";

$vars = TemplateVariable::where('template_id', $tpl->id)->get(['key', 'label']);
echo "DB_VARS_COUNT=" . $vars->count() . "\n";
foreach ($vars as $v) {
    echo "DB_VAR={$v->key}|{$v->label}\n";
}

if ($abs && file_exists($abs) && class_exists('ZipArchive')) {
    $zip = new ZipArchive();
    if ($zip->open($abs) === true) {
        $found = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (!preg_match('/\\.xml$/i', $name)) {
                continue;
            }
            if (preg_match('#\\[Content_Types\\]|_rels/#', $name)) {
                continue;
            }
            $xml = $zip->getFromIndex($i);
            if ($xml === false) {
                continue;
            }
            $text = html_entity_decode(strip_tags((string) $xml), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            preg_match_all('/(?:\\{\\s*\\{)\\s*([^{}]+?)\\s*(?:\\}\\s*\\})/u', $text, $m1);
            preg_match_all('/\\[([^\\[\\]]+?)\\]/u', $text, $m2);
            foreach (array_merge($m1[1], $m2[1]) as $orig) {
                $orig = trim($orig);
                if ($orig === '') {
                    continue;
                }
                $found[$orig] = true;
            }
        }
        $zip->close();
        echo "RAW_FILE_VARS_COUNT=" . count($found) . "\n";
        foreach (array_keys($found) as $raw) {
            echo "RAW_FILE_VAR={$raw}\n";
        }
    }
}
