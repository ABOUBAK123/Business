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
        Schema::create('signature_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('document_id');
            $table->foreign('document_id')->references('id')->on('documents')->onDelete('cascade');
            $table->uuid('requested_by')->index();
            $table->foreign('requested_by')->references('id')->on('users')->onDelete('cascade');
            $table->uuid('requested_to')->index();
            $table->foreign('requested_to')->references('id')->on('users')->onDelete('cascade');
            $table->text('message')->nullable();
            $table->enum('status', ['pending', 'signed', 'declined', 'expired'])->default('pending')->index();
            $table->timestamp('expiry_date')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('signature_requests');
    }
};
