<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$profileIds = [
    '019d9dec-0300-7300-9117-77dd96226c03', // SOUS DIRECTEUR (1)
    '019dc035-61cd-731b-b0d0-93e1238eb900', // SOUS DIRECTEUR (2)
];

foreach ($profileIds as $profileId) {
$p = \App\Models\AdministrationProfile::find($profileId);
if (!$p) { echo "Profile $profileId not found\n"; continue; }

$perms = $p->permissions ?? [];
$menu = $perms['menuPermissions'] ?? [];

$toAdd = ['personnel','personnel.dashboard','personnel.employees','personnel.agent-space','personnel.leave','personnel.training','personnel.career'];
$menu = array_values(array_unique(array_merge($menu, $toAdd)));
$perms['menuPermissions'] = $menu;
$p->permissions = $perms;
$p->save();

echo "OK - menuPermissions de [{$p->name}] mis à jour :\n";
echo implode(', ', $menu) . "\n\n";
}
