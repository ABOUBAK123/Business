<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personnel_employee_documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('employee_id')->constrained('personnel_employees')->cascadeOnDelete();
            $table->string('category', 100);
            $table->string('label', 191);
            $table->string('disk', 50)->default('local');
            $table->string('path', 1000);
            $table->string('original_name', 255);
            $table->string('mime_type', 150)->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'category'], 'pers_emp_doc_emp_cat_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personnel_employee_documents');
    }
};
