<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('name');
            $table->string('slug');
            $table->string('icon')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->index(['tenant_id', 'parent_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
