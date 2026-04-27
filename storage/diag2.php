<?php
define("LARAVEL_START", microtime(true));
require __DIR__ . "/../vendor/autoload.php";
$app = require __DIR__ . "/../bootstrap/app.php";
$app->make("Illuminate\Contracts\Console\Kernel")->bootstrap();
$tpls = App\Models\DocumentTemplate::all(["id","name","storage_path"]);
foreach ($tpls as $t) {
    echo $t->id . " | " . $t->name . " | " . ($t->storage_path ?: "NULL") . "\n";
}
