<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$email = 'l.kouassi@parapheur.ci';
$user = App\Models\User::where('email', $email)->first();

if (!$user) {
    echo "Utilisateur introuvable\n";
    exit;
}

$profile = $user->profile;
$profileName = (string) ($profile->name ?? '');
$profileUpper = function_exists('mb_strtoupper') ? mb_strtoupper(trim($profileName), 'UTF-8') : strtoupper(trim($profileName));

$rolesImputation = [
    'DIRECTEUR',
    'DIRECTEUR DE CABINET',
    'DIR CAB',
    'DIRECTEUR GÉNÉRAL',
    'DIRECTEUR GENERAL',
    'SOUS-DIRECTEUR',
    'SOUS DIRECTEUR',
];

$perms = [];
if ($profile && is_array($profile->permissions)) {
    $perms = $profile->permissions['menuPermissions'] ?? [];
}

$assignment = App\Models\UserDirectionAssignment::where('user_id', $user->id)->first();
$scopeAdmin = $profile->administration_id ?? null;
$scopeEntite = $assignment->sub_entity_code ?? null;

$visibleCount = App\Models\Courrier::query()
    ->where('type', 'arrive')
    ->where('statut', 'en_attente')
    ->where('administration_id', $scopeAdmin)
    ->whereRaw('UPPER(sub_entity_code) = ?', [strtoupper((string)$scopeEntite)])
    ->count();

echo "Email: {$user->email}\n";
echo "Nom: {$user->name}\n";
echo "Role systeme: {$user->role}\n";
echo "Profil: {$profileName}\n";
echo "Profil upper: {$profileUpper}\n";
echo "Administration ID: " . ($scopeAdmin ?: 'NULL') . "\n";
echo "Sub-entity: " . ($scopeEntite ?: 'NULL') . "\n";
echo "Has permission courrier.imputation: " . (in_array('courrier.imputation', $perms, true) || in_array('courrier', $perms, true) ? 'YES' : 'NO') . "\n";
echo "Est considere directeur par code: " . (in_array($profileUpper, $rolesImputation, true) ? 'YES' : 'NO') . "\n";
echo "Courriers visibles selon filtre imputation: {$visibleCount}\n";

echo "Permissions:\n";
foreach ($perms as $p) {
    echo " - {$p}\n";
}
