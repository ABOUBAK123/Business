<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        try {
            Schema::table('administration_profiles', function (Blueprint $table) {
                $table->dropForeign(['administration_id']);
            });
        } catch (\Throwable $exception) {
            // La contrainte peut déjà avoir été supprimée sur certaines bases.
        }

        Schema::table('administration_profiles', function (Blueprint $table) {
            $table->uuid('administration_id')->nullable()->change();
        });

        if (!Schema::hasColumn('administration_profiles', 'administration_type')) {
            Schema::table('administration_profiles', function (Blueprint $table) {
                $table->string('administration_type', 20)->nullable()->after('administration_id');
            });
        }

        DB::table('administration_profiles')
            ->whereNull('administration_type')
            ->whereNotNull('administration_id')
            ->update(['administration_type' => 'emitter']);
    }

    public function down(): void
    {
        if (Schema::hasColumn('administration_profiles', 'administration_type')) {
            Schema::table('administration_profiles', function (Blueprint $table) {
                $table->dropColumn('administration_type');
            });
        }
    }
};
