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

        if (!Schema::hasColumn('document_shares', 'applicant_phone')) {
            Schema::table('document_shares', function (Blueprint $table) {
                $table->string('applicant_phone', 50)->nullable()->after('applicant_email');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('document_shares', 'applicant_phone')) {
            Schema::table('document_shares', function (Blueprint $table) {
                $table->dropColumn('applicant_phone');
            });
        }
    }
};
