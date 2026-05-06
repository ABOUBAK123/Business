<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$email = 'a.yebouet@parapheur.ci';
$user = App\Models\User::where('email', $email)->first();
if (!$user) { echo "Utilisateur introuvable : $email\n"; exit; }

echo 'Nom      : ' . $user->name . PHP_EOL;
echo 'Email    : ' . $user->email . PHP_EOL;
echo 'Rôle sys : ' . $user->role . PHP_EOL;

if ($user->profile_id) {
    $profile = App\Models\AdministrationProfile::find($user->profile_id);
    echo 'Profil   : ' . ($profile->name ?? '—') . PHP_EOL;
    echo 'Admin ID : ' . ($profile->administration_id ?? '—') . PHP_EOL;
    $perms = is_array($profile->permissions) ? ($profile->permissions['menuPermissions'] ?? []) : [];
    echo 'Permissions (' . count($perms) . ') :' . PHP_EOL;
    foreach ($perms as $p) echo '  - ' . $p . PHP_EOL;
} else {
    echo 'Profil   : (aucun profil applicatif)' . PHP_EOL;
}

$dir = App\Models\UserDirectionAssignment::where('user_id', $user->id)->first();
echo 'Direction: ' . ($dir->sub_entity_code ?? '—') . PHP_EOL;
