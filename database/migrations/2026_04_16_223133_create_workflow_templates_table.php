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
        Schema::create('workflow_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('administration_id')->index();
            $table->string('name', 500);
            $table->text('description')->nullable();
            $table->json('validation_steps')->nullable();
            $table->json('signature_steps')->nullable();
            $table->json('notification_config')->nullable();
            $table->enum('status', ['active', 'archived'])->default('active');
            $table->uuid('created_by')->index();
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workflow_templates');
    }
};
