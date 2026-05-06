<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$email = 'a.yebouet@parapheur.ci';
$user  = App\Models\User::where('email', $email)->first();
if (!$user) { echo "Utilisateur introuvable\n"; exit; }

$adminId = $user->profile->administration_id ?? null;
echo "=== DIRECTEUR ===\n";
echo "Email    : {$user->email}\n";
echo "Admin ID : {$adminId}\n";

$dir = App\Models\UserDirectionAssignment::where('user_id', $user->id)->first();
$directorCode = $dir ? strtoupper(trim((string) $dir->sub_entity_code)) : null;
echo "sub_entity_code (user_direction_assignments) : " . ($directorCode ?? 'NULL') . "\n\n";

echo "=== COURRIERS arrive+en_attente de cette administration ===\n";
$rows = DB::table('courriers')
    ->where('type', 'arrive')
    ->where('statut', 'en_attente')
    ->when($adminId, fn($q) => $q->where('administration_id', $adminId))
    ->select('numero', 'sub_entity_code', 'enregistre_par', 'created_at')
    ->orderBy('created_at', 'desc')
    ->get();

echo "Total : " . count($rows) . "\n";
foreach ($rows as $r) {
    echo "  [{$r->numero}] sub_entity_code='" . ($r->sub_entity_code ?? 'NULL') . "'  enregistre_par={$r->enregistre_par}\n";
}

echo "\n=== FILTRE APPLIQUÉ (simulation) ===\n";
echo "Director sub_entity_code = '{$directorCode}'\n";
$visible = DB::table('courriers')
    ->where('type', 'arrive')
    ->where('statut', 'en_attente')
    ->when($adminId, fn($q) => $q->where('administration_id', $adminId))
    ->when($directorCode, fn($q) => $q->where(function ($q) use ($directorCode) {
        $q->whereRaw('UPPER(sub_entity_code) = ?', [$directorCode])
          ->orWhereNull('sub_entity_code')
          ->orWhere('sub_entity_code', 'GEN');
    }))
    ->count();
echo "Courriers visibles avec filtre : {$visible}\n";

echo "\n=== TOUS LES sub_entity_codes DISTINCTS dans courriers ===\n";
$codes = DB::table('courriers')
    ->where('administration_id', $adminId)
    ->select(DB::raw('UPPER(sub_entity_code) as code, COUNT(*) as nb'))
    ->groupByRaw('UPPER(sub_entity_code)')
    ->get();
foreach ($codes as $c) {
    echo "  '{$c->code}' => {$c->nb} courrier(s)\n";

echo "\n=== SUB_ENTITIES de cette administration ===\n";
$entities = DB::table('sub_entities')
    ->where('scope_id', $adminId)
    ->select('code', 'name', 'parent_code', 'is_active')
    ->get();
foreach ($entities as $e) {
    $actif = $e->is_active ? 'actif' : 'inactif';
    echo "  code='" . ($e->code ?? 'NULL') . "' | parent_code='" . ($e->parent_code ?? 'NULL') . "' | name={$e->name} | {$actif}\n";
}

echo "\n=== ENFANTS DIRECTS DE DGTSP (imputationChildEntities) ===\n";
$children = DB::table('sub_entities')
    ->where('scope_id', $adminId)
    ->where('is_active', true)
    ->whereRaw('UPPER(parent_code) = ?', [$directorCode ?? 'DGTSP'])
    ->pluck('code');
echo "Enfants : " . ($children->isEmpty() ? '(aucun)' : $children->implode(', ')) . "\n";

echo "\n=== RÉSULTAT AVEC NOUVEAU FILTRE (directeur + enfants + GEN/NULL) ===\n";

echo "\n=== UTILISATEUR QUI A ENREGISTRÉ LES COURRIERS CAB MIN ===\n";
$registrantId = '019d9e20-bfd8-7149-8260-e3b3be5d8c06';
$registrant = App\Models\User::find($registrantId);
if ($registrant) {
    echo "Nom   : {$registrant->name}\n";
    echo "Email : {$registrant->email}\n";
    $rDir = App\Models\UserDirectionAssignment::where('user_id', $registrantId)->first();
    echo "sub_entity_code (direction assignment) : " . ($rDir->sub_entity_code ?? 'NULL') . "\n";
    $rProfile = $registrant->profile;
    echo "Profil applicatif : " . ($rProfile->name ?? 'aucun') . "\n";
} else {
    echo "Utilisateur introuvable\n";
}
$allCodes = array_unique(array_filter(array_merge(
    $directorCode ? [$directorCode] : [],
    $children->map(fn($c) => strtoupper(trim((string) $c)))->filter()->all()
)));
echo "Codes inclus dans le filtre : [" . implode(', ', $allCodes) . "]\n";
$newCount = DB::table('courriers')
    ->where('type', 'arrive')
    ->where('statut', 'en_attente')
    ->where('administration_id', $adminId)
    ->where(function ($q) use ($allCodes) {
        $q->whereNull('sub_entity_code')
          ->orWhere('sub_entity_code', 'GEN')
          ->orWhereRaw('UPPER(sub_entity_code) IN (' . implode(',', array_fill(0, max(count($allCodes), 1), '?')) . ')',
              count($allCodes) ? $allCodes : ['__NONE__']);
    })
    ->count();
echo "Courriers visibles avec nouveau filtre : {$newCount}\n";
}
