<?php
/**
 * Diagnostic : entités filles de DMOA dans sub_entities
 * Exécuter en prod : php check_dmoa_children.php
 */
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== ENTITÉ DMOA ===\n";
$dmoa = DB::table('sub_entities')->whereRaw('UPPER(code) = ?', ['DMOA'])->first();
if (!$dmoa) {
    echo "DMOA introuvable dans sub_entities !\n";
} else {
    echo "id         : {$dmoa->id}\n";
    echo "code       : {$dmoa->code}\n";
    echo "name       : {$dmoa->name}\n";
    echo "scope_type : {$dmoa->scope_type}\n";
    echo "scope_id   : {$dmoa->scope_id}\n";
    echo "parent_code: {$dmoa->parent_code}\n";
    echo "is_active  : {$dmoa->is_active}\n";
}

echo "\n=== ENTITÉS AVEC parent_code = 'DMOA' (toutes variantes de casse) ===\n";
$children = DB::table('sub_entities')
    ->whereRaw('UPPER(parent_code) = ?', ['DMOA'])
    ->get();

if ($children->isEmpty()) {
    echo "Aucune entité fille trouvée pour parent_code DMOA !\n";
} else {
    foreach ($children as $c) {
        echo "code={$c->code} | name={$c->name} | scope_type={$c->scope_type} | scope_id={$c->scope_id} | is_active={$c->is_active}\n";
    }
}

echo "\n=== SCOPE_ID de DMOA vs filles ===\n";
if ($dmoa) {
    $mismatch = false;
    foreach ($children as $c) {
        if ($c->scope_id !== $dmoa->scope_id) {
            echo "MISMATCH scope_id : {$c->code} a scope_id={$c->scope_id} vs DMOA scope_id={$dmoa->scope_id}\n";
            $mismatch = true;
        }
        if ($c->scope_type !== $dmoa->scope_type) {
            echo "MISMATCH scope_type : {$c->code} a scope_type={$c->scope_type} vs DMOA scope_type={$dmoa->scope_type}\n";
            $mismatch = true;
        }
    }
    if (!$mismatch) {
        echo "Tous les scope_id et scope_type correspondent.\n";
    }
}

echo "\n=== USER_DIRECTION_ASSIGNMENTS avec sub_entity_code DMOA ===\n";
$assignments = DB::table('user_direction_assignments')
    ->whereRaw('UPPER(sub_entity_code) = ?', ['DMOA'])
    ->get(['user_id', 'sub_entity_code']);

if ($assignments->isEmpty()) {
    echo "Aucun utilisateur assigné à DMOA !\n";
} else {
    foreach ($assignments as $a) {
        $user = DB::table('users')->where('id', $a->user_id)->first(['email']);
        $profile = DB::table('users')
            ->join('administration_profiles', 'users.profile_id', '=', 'administration_profiles.id')
            ->where('users.id', $a->user_id)
            ->first(['administration_profiles.name as profile_name', 'administration_profiles.administration_id']);
        echo "user={$user?->email} | sub_entity_code={$a->sub_entity_code} | profil={$profile?->profile_name} | admin_id={$profile?->administration_id}\n";
    }
}
