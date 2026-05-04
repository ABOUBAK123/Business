<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('administration_smtp_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('administration_id', 36);
            $table->string('administration_type', 20)->default('emitter'); // emitter | recipient
            $table->string('mail_host')->nullable();
            $table->unsignedSmallInteger('mail_port')->default(587);
            $table->string('mail_username')->nullable();
            $table->text('mail_password')->nullable();
            $table->string('mail_encryption', 10)->nullable()->default('tls');
            $table->string('mail_from_address')->nullable();
            $table->string('mail_from_name')->nullable();
            $table->timestamps();

            $table->unique(['administration_id', 'administration_type'], 'adm_smtp_admin_type_uq');
            $table->index('administration_id', 'adm_smtp_admin_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('administration_smtp_settings');
    }
};
