<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\\Contracts\\Console\\Kernel')->bootstrap();

$rows = \App\Models\DocumentTemplate::orderBy('created_at', 'desc')->limit(8)->get();
foreach ($rows as $t) {
    $sp = (string)($t->storage_path ?? '');
    $exists = 'NO';
    if ($sp !== '') {
        if (str_starts_with($sp, 'images/')) {
            $exists = file_exists(public_path($sp)) ? 'YES' : 'NO';
        } else {
            $exists = file_exists(storage_path('app/public/' . $sp)) ? 'YES' : 'NO';
        }
    }
    echo "id={$t->id}\n";
    echo "name={$t->name}\n";
    echo "file_type={$t->file_type}\n";
    echo "storage_path=" . ($sp !== '' ? $sp : 'NULL') . "\n";
    echo "file_exists={$exists}\n";
    echo "created_at={$t->created_at}\n";
    echo "updated_at={$t->updated_at}\n";
    echo "-----\n";
}
