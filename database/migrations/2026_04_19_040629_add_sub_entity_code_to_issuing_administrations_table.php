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
        Schema::table('issuing_administrations', function (Blueprint $table) {
            $table->string('sub_entity_code', 50)->nullable()->after('code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('issuing_administrations', function (Blueprint $table) {
            $table->dropColumn('sub_entity_code');
        });
    }
};
