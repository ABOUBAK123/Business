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
        Schema::create('routing_rules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('template_id')->nullable();
            $table->uuid('recipient_id')->nullable();
            $table->string('condition_field', 150)->nullable();
            $table->string('condition_operator', 50)->nullable();
            $table->string('condition_value', 500)->nullable();
            $table->integer('priority')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('routing_rules');
    }
};
