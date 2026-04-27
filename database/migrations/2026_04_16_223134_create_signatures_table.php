<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('signatures', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('document_id');
            $table->foreign('document_id')->references('id')->on('documents')->onDelete('cascade');
            $table->uuid('signer_id')->index();
            $table->foreign('signer_id')->references('id')->on('users')->onDelete('cascade');
            $table->longText('signature');
            $table->text('certificate')->nullable();
            $table->timestamp('signed_at')->useCurrent();
            $table->string('reason', 500)->nullable();
            $table->string('location', 500)->nullable();
            $table->boolean('is_valid')->default(true);
            $table->enum('status', ['valid', 'revoked', 'expired'])->default('valid')->index();
            $table->string('signature_algorithm', 100)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('signatures');
    }
};
