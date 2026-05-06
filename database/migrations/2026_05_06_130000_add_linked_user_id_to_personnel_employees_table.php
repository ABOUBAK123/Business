<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('personnel_employees')) {
            return;
        }

        Schema::table('personnel_employees', function (Blueprint $table) {
            if (!Schema::hasColumn('personnel_employees', 'linked_user_id')) {
                $table->uuid('linked_user_id')->nullable()->after('user_id')->index();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('personnel_employees')) {
            return;
        }

        Schema::table('personnel_employees', function (Blueprint $table) {
            if (Schema::hasColumn('personnel_employees', 'linked_user_id')) {
                $table->dropIndex(['linked_user_id']);
                $table->dropColumn('linked_user_id');
            }
        });
    }
};
