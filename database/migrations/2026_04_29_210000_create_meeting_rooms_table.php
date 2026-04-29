<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meeting_rooms', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 255);
            $table->unsignedInteger('capacity');
            $table->string('location', 255);
            $table->json('equipments')->nullable();
            $table->text('description')->nullable();
            $table->string('photo_path', 1000)->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active')->index();
            $table->enum('maintenance_status', ['operational', 'maintenance', 'out_of_service'])->default('operational')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meeting_rooms');
    }
};
