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
        Schema::table('administration_profiles', function (Blueprint $table) {
            $table->string('description', 500)->nullable()->after('name');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->uuid('profile_id')->nullable()->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('administration_profiles', function (Blueprint $table) {
            $table->dropColumn('description');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('profile_id');
        });
    }
};
