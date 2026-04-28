<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_versions', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Keep UUID columns consistent with parent tables to avoid FK mismatches.
            $table->uuid('document_id');
            $table->unsignedInteger('version')->default(1);
            $table->string('file_path', 1000);

            $table->uuid('creator_id')->nullable();
            $table->text('change_log')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('document_id')->references('id')->on('documents')->onDelete('cascade');
            $table->foreign('creator_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_versions');
    }
};
