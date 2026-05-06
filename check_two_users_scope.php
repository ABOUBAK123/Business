<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$emails = [
    'l.kouassi@parapheur.ci',
    'a.yebouet@parapheur.ci',
];

foreach ($emails as $email) {
    $user = App\Models\User::where('email', $email)->first();

    echo "==============================\n";
    echo "Email: {$email}\n";

    if (!$user) {
        echo "Utilisateur: introuvable\n";
        continue;
    }

    $profile = $user->profile;
    $adminId = $profile->administration_id ?? null;
    $assignment = App\Models\UserDirectionAssignment::where('user_id', $user->id)->first();
    $subEntity = $assignment->sub_entity_code ?? null;

    echo "Nom: {$user->name}\n";
    echo "Administration ID: " . ($adminId ?: 'NULL') . "\n";
    echo "Sub-entity code: " . ($subEntity ?: 'NULL') . "\n";
}
