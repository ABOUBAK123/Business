<?php
require "vendor/autoload.php";
$app = require_once "bootstrap/app.php";
$app->make("Illuminate\Contracts\Console\Kernel")->bootstrap();
$t = App\Models\DocumentTemplate::find("019dc816-5d53-7043-bc74-aea1d8d27900");
if ($t) {
    echo "storage_path: " . ($t->storage_path ?: "[VIDE]") . PHP_EOL;
    echo "variables: " . $t->variables->count() . PHP_EOL;
} else {
    echo "Template not found" . PHP_EOL;
}

