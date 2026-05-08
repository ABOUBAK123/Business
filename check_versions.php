<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

try {
    $doc = App\Models\Document::whereNotNull('qr_token')->latest()->first();
    if ($doc) {
        echo "Document ID: {$doc->id}\n";
        echo "File Path: {$doc->file_path}\n";
        echo "QR Token: {$doc->qr_token}\n";
        $versions = $doc->versions()->get();
        echo "Versions Count: {$versions->count()}\n";
        foreach ($versions as $v) {
            echo "  v{$v->version}: {$v->file_path}\n";
        }
        exit(0);
    }
} catch (Exception $e) {
    echo "Error: {$e->getMessage()}\n";
    exit(1);
}
