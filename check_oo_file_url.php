<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$id = '019dc927-0835-72b8-8e0a-9645ab6da5ef';
$template = \App\Models\DocumentTemplate::find($id);
if (!$template) { echo "template_not_found\n"; exit(1); }

$appPublicUrl = (string)(\App\Models\AppSetting::where('key','app_public_url')->value('value') ?: '');
$expires = time() + 900;
$access  = hash_hmac('sha256', 'tplfile|' . $id . '|' . $expires, (string) config('app.key'));
$url = rtrim($appPublicUrl, '/') . '/api/oo-file/template/' . $id . '?expires=' . $expires . '&access=' . $access;

echo "template_storage_path=" . ($template->storage_path ?: 'NULL') . PHP_EOL;
echo "signed_url=" . $url . PHP_EOL;
