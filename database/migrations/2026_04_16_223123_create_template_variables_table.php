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
        Schema::create('template_variables', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('template_id');
            $table->foreign('template_id')->references('id')->on('document_templates')->onDelete('cascade');
            $table->string('key', 150);
            $table->string('label', 255);
            $table->enum('field_type', ['text', 'date', 'number', 'select', 'textarea'])->default('text');
            $table->boolean('required')->default(false);
            $table->string('placeholder', 500)->nullable();
            $table->string('default_value', 500)->nullable();
            $table->json('options')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('template_variables');
    }
};
