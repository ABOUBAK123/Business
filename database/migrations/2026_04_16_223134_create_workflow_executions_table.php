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
        Schema::create('workflow_executions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('workflow_id');
            $table->foreign('workflow_id')->references('id')->on('workflows')->onDelete('cascade');
            $table->uuid('document_id')->nullable();
            $table->foreign('document_id')->references('id')->on('documents')->onDelete('set null');
            $table->integer('current_step')->default(1);
            $table->enum('status', ['in_progress', 'completed', 'rejected', 'paused', 'cancelled'])->default('in_progress')->index();
            $table->json('step_data')->nullable();
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('completed_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workflow_executions');
    }
};
