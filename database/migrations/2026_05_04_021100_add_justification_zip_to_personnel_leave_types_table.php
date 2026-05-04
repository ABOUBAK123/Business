<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personnel_leave_types', function (Blueprint $table) {
            if (!Schema::hasColumn('personnel_leave_types', 'justification_zip_disk')) {
                $table->string('justification_zip_disk', 50)->nullable()->after('is_active');
            }
            if (!Schema::hasColumn('personnel_leave_types', 'justification_zip_path')) {
                $table->string('justification_zip_path')->nullable()->after('justification_zip_disk');
            }
            if (!Schema::hasColumn('personnel_leave_types', 'justification_zip_name')) {
                $table->string('justification_zip_name')->nullable()->after('justification_zip_path');
            }
            if (!Schema::hasColumn('personnel_leave_types', 'justification_zip_size')) {
                $table->unsignedBigInteger('justification_zip_size')->nullable()->after('justification_zip_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('personnel_leave_types', function (Blueprint $table) {
            $table->dropColumn([
                'justification_zip_disk',
                'justification_zip_path',
                'justification_zip_name',
                'justification_zip_size',
            ]);
        });
    }
};
