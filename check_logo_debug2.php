<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== AppSetting keys with 'theme_' ===\n";
$keys = App\Models\AppSetting::where('key', 'like', 'theme_%')->get(['key','value']);
if ($keys->isEmpty()) {
    echo "Aucune clé theme_ dans AppSetting\n";
} else {
    foreach ($keys as $s) {
        echo $s->key . ' = ' . $s->value . "\n";
    }
}

echo "\n=== RecipientAdministrations ===\n";
$recs = App\Models\RecipientAdministration::get(['id','name','code','logo']);
foreach ($recs as $r) {
    echo 'id=' . $r->id . ' code=' . $r->code . ' logo=[' . $r->logo . "]\n";
}

echo "\n=== AdministrationProfiles with type=recipient ===\n";
$profs = App\Models\AdministrationProfile::where('administration_type', 'recipient')
    ->orWhere('administration_type', 'App\\Models\\RecipientAdministration')
    ->get(['id','administration_id','administration_type']);
foreach ($profs as $p) {
    echo 'profile_id=' . $p->id . ' admin_id=' . $p->administration_id . ' type=' . $p->administration_type . "\n";
}
