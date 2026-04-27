<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('act_request_submissions')) {
            return;
        }

        Schema::create('act_request_submissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('requested_act_id');
            $table->uuid('emitter_administration_id');
            $table->string('direction_code')->nullable();
            $table->string('requested_document_name');
            $table->string('applicant_full_name');
            $table->string('applicant_email')->nullable();
            $table->string('applicant_phone')->nullable();
            $table->json('applicant_payload')->nullable();
            $table->json('attachments')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();

            $table->foreign('requested_act_id')->references('id')->on('requested_acts')->cascadeOnDelete();
            $table->foreign('emitter_administration_id')->references('id')->on('issuing_administrations')->cascadeOnDelete();
            $table->index(['emitter_administration_id', 'status']);
            $table->index('direction_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('act_request_submissions');
    }
};
