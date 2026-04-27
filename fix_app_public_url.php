<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$key = 'app_public_url';
$old = \App\Models\AppSetting::where('key', $key)->value('value');
$new = 'https://optimize-grateful-favor-burke.trycloudflare.com';

\App\Models\AppSetting::updateOrCreate(
    ['key' => $key],
    ['value' => $new, 'description' => 'URL publique de l\'application (OnlyOffice callback/docUrl)']
);

echo "old={$old}\n";
echo "new={$new}\n";
