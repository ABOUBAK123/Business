<?php
define('LARAVEL_START', microtime(true));
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$email = 'a.kamagate@modernisation.gouv.ci';
$u = App\Models\User::where('email', $email)->with('profile')->first();
if (!$u) {
    echo "Utilisateur introuvable: $email\n";
    echo "\n=== Liste des utilisateurs ===\n";
    $all = App\Models\User::select('id','email','full_name','role','status','profile_id')->get();
    echo json_encode($all, JSON_PRETTY_PRINT) . "\n";
    echo "\n=== Toutes les signature_provider_configs ===\n";
    $spc = DB::table('signature_provider_configs')->get();
    echo json_encode($spc, JSON_PRETTY_PRINT) . "\n";
    exit;
}
echo "=== Utilisateur ===\n";
echo "ID: {$u->id}\n";
echo "Nom: {$u->full_name}\n";
echo "Role: {$u->role}\n";
echo "Status: {$u->status}\n";
echo "profile_id: {$u->profile_id}\n";

if ($u->profile) {
    echo "administration_id: {$u->profile->administration_id}\n";
    echo "administration_type: {$u->profile->administration_type}\n";
    echo "profile name: {$u->profile->name}\n";
} else {
    echo "Pas de profil\n";
}

$assignments = DB::table('user_direction_assignments')->where('user_id', $u->id)->get();
echo "\n=== user_direction_assignments ===\n";
echo json_encode($assignments, JSON_PRETTY_PRINT) . "\n";

echo "\n=== Toutes les signature_provider_configs ===\n";
$spc = DB::table('signature_provider_configs')->get();
echo json_encode($spc, JSON_PRETTY_PRINT) . "\n";

// Simulate resolveSignatureConfig for this user
echo "\n=== Simulation resolveSignatureConfig ===\n";
$adminId = $u->profile?->administration_id ?? null;
echo "adminId depuis profile: " . ($adminId ?? 'null') . "\n";
if ($adminId) {
    $config = App\Models\SignatureProviderConfig::where('administration_id', $adminId)
        ->where('is_active', true)
        ->first();
    echo "Config trouvée: " . ($config ? json_encode($config->toArray(), JSON_PRETTY_PRINT) : 'Aucune') . "\n";
} else {
    echo "Pas d'administration_id -> config non trouvée\n";
}
