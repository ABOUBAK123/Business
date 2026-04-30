<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('meetings')) {
            return;
        }

        Schema::table('meetings', function (Blueprint $table) {
            if (!Schema::hasColumn('meetings', 'issuing_administration_id')) {
                $table->uuid('issuing_administration_id')->nullable()->after('minutes_writer_id')->index();
            }

            if (!Schema::hasColumn('meetings', 'sub_entity_code')) {
                $table->string('sub_entity_code', 64)->nullable()->after('issuing_administration_id')->index();
            }
        });

        // Rattacher les reunions existantes au scope actuel de leur organisateur
        // pour que le filtrage par entite fonctionne immediatement.
        $meetings = DB::table('meetings')->select(['id', 'organizer_id'])->get();
        foreach ($meetings as $meeting) {
            $assignment = DB::table('user_direction_assignments')
                ->where('user_id', $meeting->organizer_id)
                ->orderByDesc('created_at')
                ->first();

            if (!$assignment || empty($assignment->direction_scope_id)) {
                continue;
            }

            DB::table('meetings')
                ->where('id', $meeting->id)
                ->update([
                    'issuing_administration_id' => (string) $assignment->direction_scope_id,
                    'sub_entity_code' => ($assignment->sub_entity_code ?? null) !== null
                        ? strtoupper(trim((string) $assignment->sub_entity_code))
                        : null,
                ]);
        }

        // FK ajoutee apres backfill pour eviter les erreurs de migration partielle.
        Schema::table('meetings', function (Blueprint $table) {
            try {
                $table->foreign('issuing_administration_id')
                    ->references('id')
                    ->on('issuing_administrations')
                    ->nullOnDelete();
            } catch (\Throwable $e) {
                // Ignore si deja creee / non supportee dans l'environnement.
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('meetings')) {
            return;
        }

        Schema::table('meetings', function (Blueprint $table) {
            try {
                $table->dropForeign(['issuing_administration_id']);
            } catch (\Throwable $e) {
                // Ignore si la contrainte n'existe pas.
            }

            if (Schema::hasColumn('meetings', 'sub_entity_code')) {
                $table->dropColumn('sub_entity_code');
            }
            if (Schema::hasColumn('meetings', 'issuing_administration_id')) {
                $table->dropColumn('issuing_administration_id');
            }
        });
    }
};
