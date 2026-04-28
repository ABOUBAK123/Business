<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('courriers')) {
            return;
        }

        Schema::table('courriers', function (Blueprint $table) {
            if (!Schema::hasColumn('courriers', 'delai_traitement')) {
                $table->date('delai_traitement')->nullable();
            }

            if (!Schema::hasColumn('courriers', 'reponse_nom')) {
                $table->string('reponse_nom', 255)->nullable();
            }

            if (!Schema::hasColumn('courriers', 'reponse_statut')) {
                $table->enum('reponse_statut', ['en_attente_validation', 'validee', 'rejetee'])
                    ->nullable()
                    ->index();
            }

            if (!Schema::hasColumn('courriers', 'traite_par')) {
                $table->uuid('traite_par')->nullable()->index();
            }

            if (!Schema::hasColumn('courriers', 'traite_le')) {
                $table->timestamp('traite_le')->nullable();
            }

            if (!Schema::hasColumn('courriers', 'workflow_participants')) {
                $table->json('workflow_participants')->nullable();
            }
        });

        Schema::table('courriers', function (Blueprint $table) {
            if (Schema::hasColumn('courriers', 'traite_par')) {
                try {
                    $table->foreign('traite_par')->references('id')->on('users')->onDelete('set null');
                } catch (\Throwable $e) {
                    // Ignore if the FK already exists or cannot be added on this environment.
                }
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('courriers')) {
            return;
        }

        Schema::table('courriers', function (Blueprint $table) {
            try {
                $table->dropForeign(['traite_par']);
            } catch (\Throwable $e) {
                // Ignore if FK does not exist.
            }

            if (Schema::hasColumn('courriers', 'workflow_participants')) {
                $table->dropColumn('workflow_participants');
            }
            if (Schema::hasColumn('courriers', 'traite_le')) {
                $table->dropColumn('traite_le');
            }
            if (Schema::hasColumn('courriers', 'traite_par')) {
                $table->dropColumn('traite_par');
            }
            if (Schema::hasColumn('courriers', 'reponse_statut')) {
                $table->dropColumn('reponse_statut');
            }
            if (Schema::hasColumn('courriers', 'reponse_nom')) {
                $table->dropColumn('reponse_nom');
            }
            if (Schema::hasColumn('courriers', 'delai_traitement')) {
                $table->dropColumn('delai_traitement');
            }
        });
    }
};
