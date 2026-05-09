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
        Schema::table('signatures', function (Blueprint $table) {
            // Ajouter la colonne qr_code_token si elle n'existe pas
            if (!Schema::hasColumn('signatures', 'qr_code_token')) {
                $table->string('qr_code_token', 64)->nullable()->unique()->after('is_valid');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('signatures', function (Blueprint $table) {
            if (Schema::hasColumn('signatures', 'qr_code_token')) {
                $table->dropColumn('qr_code_token');
            }
        });
    }
};
