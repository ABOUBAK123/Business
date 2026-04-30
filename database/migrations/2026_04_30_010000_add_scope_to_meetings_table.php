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

        // FK ajoutee apres backfill. On utilise SQL brut dans un vrai try/catch,
        // car l'exception SQL est declenchee APRES la closure Schema::table().
        if (
            Schema::hasTable('issuing_administrations')
            && Schema::hasColumn('issuing_administrations', 'id')
            && Schema::hasColumn('meetings', 'issuing_administration_id')
            && !$this->foreignKeyExists('meetings', 'meetings_issuing_administration_id_foreign')
        ) {
            try {
                DB::statement(
                    'ALTER TABLE `meetings` '
                    . 'ADD CONSTRAINT `meetings_issuing_administration_id_foreign` '
                    . 'FOREIGN KEY (`issuing_administration_id`) '
                    . 'REFERENCES `issuing_administrations`(`id`) '
                    . 'ON DELETE SET NULL'
                );
            } catch (\Throwable $e) {
                // Ne pas bloquer la migration si FK impossible (schema legacy/collation).
            }
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('meetings')) {
            return;
        }

        if ($this->foreignKeyExists('meetings', 'meetings_issuing_administration_id_foreign')) {
            try {
                DB::statement('ALTER TABLE `meetings` DROP FOREIGN KEY `meetings_issuing_administration_id_foreign`');
            } catch (\Throwable $e) {
                // Ignore si la contrainte est deja absente ou non supprimable.
            }
        }

        Schema::table('meetings', function (Blueprint $table) {
            // Drop FK ci-dessous via SQL brut si elle existe.

            if (Schema::hasColumn('meetings', 'sub_entity_code')) {
                $table->dropColumn('sub_entity_code');
            }
            if (Schema::hasColumn('meetings', 'issuing_administration_id')) {
                $table->dropColumn('issuing_administration_id');
            }
        });
    }

    private function foreignKeyExists(string $tableName, string $constraintName): bool
    {
        $database = DB::getDatabaseName();

        $exists = DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', $database)
            ->where('TABLE_NAME', $tableName)
            ->where('CONSTRAINT_NAME', $constraintName)
            ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
            ->exists();

        return (bool) $exists;
    }
};
