<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('document_shares')) {
            return;
        }

        if (!Schema::hasColumn('document_shares', 'tracking_number')) {
            Schema::table('document_shares', function (Blueprint $table) {
                $table->string('tracking_number', 60)->nullable()->after('applicant_email')->index();
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('document_shares')) {
            return;
        }

        if (Schema::hasColumn('document_shares', 'tracking_number')) {
            Schema::table('document_shares', function (Blueprint $table) {
                $table->dropColumn('tracking_number');
            });
        }
    }
};
