<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$email = 'a.yebouet@parapheur.ci';
$user = App\Models\User::where('email', $email)->first();
if (!$user) {
    echo "Utilisateur introuvable\n";
    exit;
}

$profile = $user->profile;
$adminId = $profile->administration_id ?? null;
$profileName = (string) ($profile->name ?? '');
$profileUpper = function_exists('mb_strtoupper') ? mb_strtoupper(trim($profileName), 'UTF-8') : strtoupper(trim($profileName));

$assignment = App\Models\UserDirectionAssignment::where('user_id', $user->id)->first();
$subEntityCode = strtoupper(trim((string) ($assignment->sub_entity_code ?? '')));
$subEntityCode = $subEntityCode !== '' ? $subEntityCode : null;

$perms = [];
if ($profile && is_array($profile->permissions)) {
    $perms = $profile->permissions['menuPermissions'] ?? [];
}

$rolesImputation = [
    'DIRECTEUR',
    'DIRECTEUR DE CABINET',
    'DIR CAB',
    'DIRECTEUR GÉNÉRAL',
    'DIRECTEUR GENERAL',
    'SOUS-DIRECTEUR',
    'SOUS DIRECTEUR',
];

$visible = 0;
if ($adminId && $subEntityCode) {
    $visible = DB::table('courriers')
        ->where('type', 'arrive')
        ->where('statut', 'en_attente')
        ->where('administration_id', $adminId)
        ->whereRaw('UPPER(sub_entity_code) = ?', [$subEntityCode])
        ->count();
}

$allPendingByCode = DB::table('courriers')
    ->where('type', 'arrive')
    ->where('statut', 'en_attente')
    ->when($adminId, fn($q) => $q->where('administration_id', $adminId))
    ->select(DB::raw('COALESCE(UPPER(sub_entity_code), "NULL") as code'), DB::raw('COUNT(*) as nb'))
    ->groupByRaw('COALESCE(UPPER(sub_entity_code), "NULL")')
    ->orderBy('code')
    ->get();

echo "Email: {$email}\n";
echo "Nom: {$user->name}\n";
echo "Role systeme: {$user->role}\n";
echo "Profil: {$profileName}\n";
echo "Profil upper: {$profileUpper}\n";
echo "Administration ID: " . ($adminId ?: 'NULL') . "\n";
echo "Sub-entity code: " . ($subEntityCode ?: 'NULL') . "\n";
echo "Permission courrier.imputation: " . ((in_array('courrier.imputation', $perms, true) || in_array('courrier', $perms, true)) ? 'YES' : 'NO') . "\n";
echo "Profil reconnu directeur: " . (in_array($profileUpper, $rolesImputation, true) ? 'YES' : 'NO') . "\n";
echo "Courriers visibles (filtre actuel): {$visible}\n";

echo "\nEn attente par sub_entity_code (meme administration):\n";
foreach ($allPendingByCode as $r) {
    echo " - {$r->code}: {$r->nb}\n";
}
