<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Courrier;
use Illuminate\Support\Facades\Schema;

$table = 'courriers';
$columns = Schema::getColumnListing($table);

$user = User::where('email', 'j.kouakou@parapheur.ci')->first();
$courrier = null;

// Search for the ID A-DISD-00002-2026 in possible columns
$searchId = 'A-DISD-00002-2026';
$possibleCols = ['id', 'reference', 'numero', 'uuid', 'code'];
foreach ($possibleCols as $col) {
    if (in_array($col, $columns)) {
        try {
            $courrier = Courrier::where($col, $searchId)->first();
            if ($courrier) break;
        } catch (\Exception $e) {}
    }
}

$userData = $user ? [
    'id' => $user->id,
    'email' => $user->email,
    'profile' => $user->profile ?? $user->role ?? 'N/A'
] : 'User not found';

$courrierData = $courrier ? $courrier->only(['id', 'type', 'statut', 'impute_par', 'impute_a', 'traite_par', 'traite_le', 'reponse_statut', 'workflow_participants', 'created_at']) : 'Courrier not found';

echo "DATA_START\n";
echo json_encode([
    'user' => $userData,
    'courrier' => $courrierData,
    'columns' => $columns
], JSON_PRETTY_PRINT);
echo "\nDATA_END\n";
