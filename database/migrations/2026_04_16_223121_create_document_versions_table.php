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

            // ✅ CHAR(36) pour matcher documents.id + même collation que le parent
            $table->string('document_id', 36)->collation('utf8mb3_unicode_ci');
            $table->unsignedInteger('version')->default(1);
            $table->string('file_path', 1000);

            $table->string('creator_id', 36)->nullable()->collation('utf8mb3_unicode_ci');
            $table->text('change_log')->nullable();
            $table->timestamp('created_at')->useCurrent();

            // ✅ Contraintes ajoutées après la définition des colonnes
            $table->foreign('document_id')->references('id')->on('documents')->onDelete('cascade');
            $table->foreign('creator_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_versions');
    }
};
