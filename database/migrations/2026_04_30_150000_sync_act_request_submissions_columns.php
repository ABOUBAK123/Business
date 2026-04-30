<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('act_request_submissions')) {
            return;
        }

        Schema::table('act_request_submissions', function (Blueprint $table) {
            if (!Schema::hasColumn('act_request_submissions', 'recipient_administration_id')) {
                $table->uuid('recipient_administration_id')->nullable()->after('direction_code');
            }

            if (!Schema::hasColumn('act_request_submissions', 'motif')) {
                $table->text('motif')->nullable()->after('recipient_administration_id');
            }

            if (!Schema::hasColumn('act_request_submissions', 'applicant_payload')) {
                $table->json('applicant_payload')->nullable()->after('applicant_phone');
            }

            if (!Schema::hasColumn('act_request_submissions', 'attachments')) {
                $table->json('attachments')->nullable()->after('applicant_payload');
            }
        });
    }

    public function down(): void
    {
        // No-op: migration de rattrapage pour environnements déjà divergents.
    }
};
