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
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('recipient_id')->index();
            $table->foreign('recipient_id')->references('id')->on('users')->onDelete('cascade');
            $table->string('title', 255);
            $table->text('message');
            $table->enum('type', ['info', 'validation', 'signature', 'workflow', 'system'])->default('info');
            $table->uuid('workflow_id')->nullable();
            $table->uuid('execution_id')->nullable();
            $table->string('action_url', 512)->nullable();
            $table->boolean('is_read')->default(false)->index();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
