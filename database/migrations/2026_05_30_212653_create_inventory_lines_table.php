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
        Schema::create('inventory_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('inventory_id')->index();
            $table->unsignedBigInteger('article_id')->index();
            $table->decimal('theoretical_qty', 10, 2)->default(0);
            $table->decimal('counted_qty', 10, 2)->nullable();
            $table->decimal('gap', 10, 2)->default(0); // counted - theoretical
            $table->timestamps();
            $table->unique(['inventory_id', 'article_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_lines');
    }
};
