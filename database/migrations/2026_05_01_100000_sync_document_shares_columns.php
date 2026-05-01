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

        Schema::table('document_shares', function (Blueprint $table) {
            if (!Schema::hasColumn('document_shares', 'tracking_number')) {
                $table->string('tracking_number', 60)->nullable()->after('applicant_email');
            }
            if (!Schema::hasColumn('document_shares', 'applicant_matricule')) {
                $table->string('applicant_matricule')->nullable()->after('applicant_full_name');
            }
            if (!Schema::hasColumn('document_shares', 'applicant_phone')) {
                $table->string('applicant_phone', 50)->nullable()->after('applicant_email');
            }
            if (!Schema::hasColumn('document_shares', 'permission')) {
                $table->string('permission', 50)->default('lecture');
            }
            if (!Schema::hasColumn('document_shares', 'has_delay')) {
                $table->boolean('has_delay')->default(false);
            }
            if (!Schema::hasColumn('document_shares', 'delay_value')) {
                $table->unsignedInteger('delay_value')->nullable();
            }
            if (!Schema::hasColumn('document_shares', 'delay_unit')) {
                $table->string('delay_unit', 10)->nullable();
            }
            if (!Schema::hasColumn('document_shares', 'expires_at')) {
                $table->timestamp('expires_at')->nullable()->index();
            }
        });
    }

    public function down(): void
    {
        // Ne pas supprimer ces colonnes en rollback pour éviter la perte de données
    }
};
