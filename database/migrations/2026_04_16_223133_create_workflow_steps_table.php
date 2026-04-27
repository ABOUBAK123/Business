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
        Schema::create('workflow_steps', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('workflow_id');
            $table->foreign('workflow_id')->references('id')->on('workflows')->onDelete('cascade');
            $table->integer('order');
            $table->string('name', 255)->nullable();
            $table->enum('type', ['review', 'sign', 'approve', 'reject', 'notify']);
            $table->uuid('assignee_id')->nullable()->index();
            $table->foreign('assignee_id')->references('id')->on('users')->onDelete('set null');
            $table->text('description')->nullable();
            $table->boolean('requires_signature')->default(false);
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['workflow_id', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workflow_steps');
    }
};
