<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clamav_scan_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('file_name', 500);
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('mime_type', 150)->nullable();
            $table->enum('result', ['clean', 'infected', 'error']);
            $table->string('threat', 500)->nullable();
            $table->string('context', 100)->nullable();
            $table->string('scanned_by', 36)->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('scanner_output')->nullable();
            $table->timestamp('scanned_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clamav_scan_logs');
    }
};
