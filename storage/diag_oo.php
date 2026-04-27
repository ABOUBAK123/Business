<?php
define('LARAVEL_START', microtime(true));
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\AppSetting;
use App\Models\DocumentTemplate;

$appPublicUrl = AppSetting::where('key', 'app_public_url')->value('value');
echo "app_public_url: " . $appPublicUrl . "\n";

$tpls = DocumentTemplate::all(['id','name','storage_path','file_type']);
foreach ($tpls as $tpl) {
    echo "\n--- " . $tpl->name . " ---\n";
    echo "storage_path: " . ($tpl->storage_path ?: 'NULL') . "\n";
    if (!$tpl->storage_path) { echo "=> VIERGE (pas de fichier)\n"; continue; }
    if (str_starts_with($tpl->storage_path, 'images/')) {
        $abs = public_path($tpl->storage_path);
        $docUrl = rtrim($appPublicUrl, '/') . '/' . $tpl->storage_path;
    } else {
        $abs = storage_path('app/public/' . $tpl->storage_path);
        $docUrl = rtrim($appPublicUrl, '/') . '/storage/' . $tpl->storage_path;
    }
    echo "abs_path: " . $abs . "\n";
    echo "file_exists: " . (file_exists($abs) ? 'OUI (' . filesize($abs) . ' bytes)' : 'NON') . "\n";
    echo "doc_url: " . $docUrl . "\n";
    // Tester si l'URL est accessible depuis ce serveur
    $ctx = stream_context_create(['http' => ['timeout' => 5, 'ignore_errors' => true]]);
    $headers = @get_headers($docUrl, true, $ctx);
    echo "http_status: " . ($headers ? $headers[0] : 'INACCESSIBLE') . "\n";
}
