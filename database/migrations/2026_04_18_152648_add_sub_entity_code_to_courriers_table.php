<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('courriers', function (Blueprint $table) {
            // Code de la sous-entité (direction) de l'utilisateur enregistreur
            // Utilisé pour la codification du numéro : A-DMOA-00001-2026
            $table->string('sub_entity_code', 50)->nullable()->after('administration_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('courriers', function (Blueprint $table) {
            $table->dropColumn('sub_entity_code');
        });
    }
};
