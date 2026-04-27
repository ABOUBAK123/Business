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
        Schema::create('administration_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('administration_id');
            $table->foreign('administration_id')->references('id')->on('issuing_administrations')->onDelete('cascade');
            $table->string('name', 150);
            $table->json('permissions')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('administration_profiles');
    }
};
