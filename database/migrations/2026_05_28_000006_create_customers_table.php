<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->string('nif')->nullable();
            $table->enum('type', ['individual', 'professional', 'wholesale'])->default('individual');
            $table->enum('classification', ['regular', 'vip', 'inactive'])->default('regular');
            $table->decimal('credit_limit', 10, 2)->default(0);
            $table->decimal('credit_balance', 10, 2)->default(0); // amount owed
            $table->timestamps();
            $table->softDeletes();
            $table->index(['tenant_id', 'phone']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
