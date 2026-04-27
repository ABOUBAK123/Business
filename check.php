<?php
define('LARAVEL_START', microtime(true));
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$user = \App\Models\User::where('email','admin@e-parapheur.local')->first();
if (!$user) { echo "No admin user!\n"; exit; }
echo "Admin ID: " . $user->id . "\n";

$docs = \App\Models\Document::where('owner_id', $user->id)->get();
echo "Documents count: " . $docs->count() . "\n";
foreach ($docs as $d) {
    echo " - [{$d->status}] {$d->title} (owner: {$d->owner_id})\n";
}
