<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_shares', function (Blueprint $table) {
            if (!Schema::hasColumn('document_shares', 'applicant_rib')) {
                $table->string('applicant_rib', 100)->nullable()->after('applicant_phone');
            }
        });
    }

    public function down(): void
    {
        Schema::table('document_shares', function (Blueprint $table) {
            if (Schema::hasColumn('document_shares', 'applicant_rib')) {
                $table->dropColumn('applicant_rib');
            }
        });
    }
};
