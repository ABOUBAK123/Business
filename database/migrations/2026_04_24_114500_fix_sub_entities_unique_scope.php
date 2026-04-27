<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        try {
            DB::statement('ALTER TABLE `sub_entities` DROP INDEX `sub_entities_code_unique`');
        } catch (\Throwable $e) {
            // Index absent: nothing to drop.
        }

        DB::statement(
            'ALTER TABLE `sub_entities` ADD UNIQUE `sub_entities_scope_type_scope_id_code_unique` (`scope_type`(50), `scope_id`(50), `code`(50))'
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            DB::statement('ALTER TABLE `sub_entities` DROP INDEX `sub_entities_scope_type_scope_id_code_unique`');
        } catch (\Throwable $e) {
            // Index absent: nothing to drop.
        }

        Schema::table('sub_entities', function (Blueprint $table) {
            $table->unique('code', 'sub_entities_code_unique');
        });
    }
};
