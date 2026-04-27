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
        Schema::create('documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title', 500);
            $table->text('description')->nullable();
            $table->string('file_path', 1000);
            $table->bigInteger('file_size')->default(0);
            $table->string('mime_type', 100)->nullable()->default('application/pdf');
            $table->enum('status', ['draft', 'active', 'signed', 'archived', 'pending_signature'])->default('draft')->index();
            $table->uuid('owner_id')->index();
            $table->foreign('owner_id')->references('id')->on('users')->onDelete('cascade');
            $table->uuid('created_by')->nullable();
            $table->uuid('issuing_administration_id')->nullable();
            $table->uuid('recipient_administration_id')->nullable()->index();
            $table->string('document_number', 120)->nullable();
            $table->string('sub_entity_code', 100)->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
