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
        Schema::create('workflows', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 500);
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'inactive', 'archived'])->default('active')->index();
            $table->json('docs_to_sign')->nullable();
            $table->json('attached_docs')->nullable();
            $table->json('uploaded_signature_files')->nullable();
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
        Schema::dropIfExists('workflows');
    }
};
