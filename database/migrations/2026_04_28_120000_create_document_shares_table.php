<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('document_shares')) {
            return;
        }

        Schema::create('document_shares', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('document_id')->index();
            $table->uuid('shared_by')->index();

            $table->string('mode', 50)->default('internal');
            $table->string('recipient_name')->nullable();
            $table->string('recipient_email')->nullable()->index();
            $table->uuid('recipient_administration_id')->nullable()->index();

            $table->string('applicant_full_name')->nullable();
            $table->string('applicant_matricule')->nullable();
            $table->string('applicant_email')->nullable();

            $table->string('permission', 50)->default('lecture');
            $table->boolean('has_delay')->default(false);
            $table->unsignedInteger('delay_value')->nullable();
            $table->string('delay_unit', 10)->nullable();
            $table->timestamp('expires_at')->nullable()->index();

            $table->timestamps();

            $table->foreign('document_id')->references('id')->on('documents')->onDelete('cascade');
            $table->foreign('shared_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('recipient_administration_id')->references('id')->on('recipient_administrations')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_shares');
    }
};
