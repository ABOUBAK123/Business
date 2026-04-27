<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$id = $argv[1] ?? '019dc923-8102-726f-81ba-456b0b9979bc';
$t = \App\Models\DocumentTemplate::find($id);

if (!$t) {
    echo "NOT_FOUND\n";
    exit(1);
}

$sp = (string)($t->storage_path ?? '');
$publicPath = $sp !== '' ? public_path($sp) : '';
$storagePath = $sp !== '' ? storage_path('app/public/' . $sp) : '';

echo "id={$t->id}\n";
echo "name={$t->name}\n";
echo "storage_path=" . ($sp !== '' ? $sp : 'NULL') . "\n";
echo "public_path_exists=" . (($publicPath && file_exists($publicPath)) ? 'YES' : 'NO') . "\n";
echo "storage_disk_exists=" . (($storagePath && file_exists($storagePath)) ? 'YES' : 'NO') . "\n";
echo "updated_at={$t->updated_at}\n";

echo "variables_count=" . $t->variables()->count() . "\n";
