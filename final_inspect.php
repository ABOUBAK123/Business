<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Courrier;

$user = User::where('email', 'j.kouakou@parapheur.ci')->first();
$courrier = Courrier::where('numero', 'A-DISD-00002-2026')->first();

$results = [
    'user' => [
        'id' => $user->id,
        'email' => $user->email,
        'profile' => $user->profile
    ],
    'courrier' => $courrier->only(['id', 'type', 'statut', 'impute_par', 'impute_a', 'traite_par', 'traite_le', 'reponse_statut', 'workflow_participants', 'created_at']),
    'treated_tab_match' => false
];

// Logic observation: 'traite_par' is '019dc04f...' and 'workflow_participants' contains '019dc018...' and '019dc04f...'.
// The user 'j.kouakou' has ID '019dc036...', which is NOT in those fields.
// In CourrierController.php, 'traite' tab usually filters by ($q->where('traite_par', $user->id)->orWhereJsonContains('workflow_participants', $user->id)) AND status 'traite'.

echo json_encode($results, JSON_PRETTY_PRINT);
