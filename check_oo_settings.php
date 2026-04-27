<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$keys = ['app_public_url', 'onlyoffice_server_url', 'onlyoffice_secret', 'onlyoffice_disable_cert'];
foreach ($keys as $k) {
    $v = \App\Models\AppSetting::where('key', $k)->value('value');
    if ($k === 'onlyoffice_secret') {
        echo $k . '=' . ($v ? '***SET***' : 'NULL') . PHP_EOL;
    } else {
        echo $k . '=' . ($v ?? 'NULL') . PHP_EOL;
    }
}
