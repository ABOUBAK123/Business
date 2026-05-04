<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('personnel_employees');
        Schema::create('personnel_employees', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->nullable()->index();
            $table->char('sub_entity_id', 36)->nullable()->index();
            $table->string('administration_type', 20)->default('emitter');
            $table->string('administration_id', 36)->nullable();
            $table->string('employee_number', 100)->nullable();
            $table->string('first_name', 150);
            $table->string('last_name', 150);
            $table->string('gender', 20)->nullable();
            $table->date('birth_date')->nullable();
            $table->string('birth_place', 150)->nullable();
            $table->string('marital_status', 50)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('secondary_phone', 50)->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('emergency_contact_name', 191)->nullable();
            $table->string('emergency_contact_phone', 50)->nullable();
            $table->string('job_title', 191)->nullable();
            $table->date('hire_date')->nullable();
            $table->string('employment_status', 50)->default('active');
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['administration_type', 'employee_number'], 'pers_emp_admin_num_uq');
            $table->index(['administration_type', 'administration_id'], 'pers_emp_admin_scope_idx');
            $table->index('employment_status', 'pers_emp_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personnel_employees');
    }
};
