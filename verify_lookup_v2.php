<?php
require __DIR__ . "/vendor/autoload.php";
$app = require_once __DIR__ . "/bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$profiles = ["DIRECTEUR", "DIRECTEUR DE CABINET", "DIR CAB", "DIRECTEUR GÉNÉRAL", "DIRECTEUR GENERAL", "SOUS-DIRECTEUR", "SOUS DIRECTEUR"];

$result = DB::table("user_direction_assignments")
    ->join("users", "user_direction_assignments.user_id", "=", "users.id")
    ->whereIn("users.profile", $profiles)
    ->select("user_direction_assignments.sub_entity_code", "users.id", "users.email", "users.profile as profile_name")
    ->first();

if ($result) {
    echo "Sub-entity Code: " . $result->sub_entity_code . "\n";
    echo "User ID: " . $result->id . "\n";
    echo "Email: " . $result->email . "\n";
    echo "Profile: " . $result->profile_name . "\n";
} else {
    echo "No matching data found.\n";
}