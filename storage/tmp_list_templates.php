<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$rows = App\Models\DocumentTemplate::orderBy('updated_at', 'desc')
    ->limit(40)
    ->get(['id', 'name', 'file_type', 'storage_path', 'updated_at']);

foreach ($rows as $r) {
    $count = App\Models\TemplateVariable::where('template_id', $r->id)->count();
    echo $r->id . '|' . $r->name . '|' . $r->file_type . '|vars=' . $count . '|' . $r->updated_at . PHP_EOL;
}
