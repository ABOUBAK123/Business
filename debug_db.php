<?php
require __DIR__ . "/vendor/autoload.php";
$app = require_once __DIR__ . "/bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$columns = Schema::getColumnListing("users");
echo "Users columns: " . implode(", ", $columns) . "\n";

$tables = DB::select("SHOW TABLES");
echo "Tables: " . implode(", ", array_map(function($t) { return current((array)$t); }, $tables)) . "\n";