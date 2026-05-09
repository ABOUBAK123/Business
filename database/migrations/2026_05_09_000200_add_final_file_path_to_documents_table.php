<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->string('final_file_path', 1000)->nullable()->after('signed_file_path');
        });

        DB::table('documents')
            ->whereNull('final_file_path')
            ->update([
                'final_file_path' => DB::raw('COALESCE(NULLIF(TRIM(signed_file_path), ""), NULLIF(TRIM(file_path), ""))'),
            ]);
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn('final_file_path');
        });
    }
};
