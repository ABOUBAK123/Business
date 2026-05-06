<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$templates = App\Models\DocumentTemplate::orderBy('created_at', 'desc')->take(12)->get();
foreach ($templates as $t) {
    $path = (string) $t->storage_path;
    $abs = '';
    if ($path) {
        $abs = str_starts_with($path, 'images/')
            ? public_path($path)
            : Illuminate\Support\Facades\Storage::disk('public')->path($path);
    }
    echo 'ID=' . $t->id
        . '|name=' . $t->name
        . '|type=' . $t->file_type
        . '|storage=' . $path
        . '|exists=' . (($abs && file_exists($abs)) ? '1' : '0')
        . '|vars_db=' . $t->variables()->count()
        . PHP_EOL;
}
