<?php
// Diagnostic : permissions personnel pour l'utilisateur connecté
// Appeler en CLI : php artisan tinker --execute="require 'check_personnel_perm.php';"
// OU ouvrir http://localhost/e-administration_laravel/check_personnel_perm.php (désactiver après)

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\AdministrationProfile;
use App\Models\User;

// Lister tous les profils et leurs menuPermissions
$profiles = AdministrationProfile::all(['id', 'name', 'permissions']);
echo "=== PROFILS APPLICATIFS ===\n";
foreach ($profiles as $p) {
    $menu = $p->permissions['menuPermissions'] ?? [];
    $hasPersonnel = array_filter($menu, fn($k) => $k === 'personnel' || str_starts_with($k, 'personnel.'));
    echo "\n[{$p->name}] (id: {$p->id})\n";
    echo "  menuPermissions: " . implode(', ', $menu) . "\n";
    echo "  personnel perms: " . (empty($hasPersonnel) ? 'AUCUNE' : implode(', ', $hasPersonnel)) . "\n";
}

// Lister les users avec profil
echo "\n\n=== UTILISATEURS AVEC PROFIL ===\n";
$users = User::whereNotNull('profile_id')->get(['id', 'name', 'email', 'role', 'profile_id']);
foreach ($users as $u) {
    $profile = AdministrationProfile::find($u->profile_id);
    $menu = $profile?->permissions['menuPermissions'] ?? [];
    $hasPersonnel = array_filter($menu, fn($k) => $k === 'personnel' || str_starts_with($k, 'personnel.'));
    echo "\n  {$u->name} ({$u->email}) role={$u->role}\n";
    echo "  profile: " . ($profile?->name ?? 'introuvable') . "\n";
    echo "  personnel perms: " . (empty($hasPersonnel) ? 'AUCUNE ← PROBLÈME ICI' : implode(', ', $hasPersonnel)) . "\n";
}
