<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_plan_id')->constrained();
            $table->enum('billing_cycle', ['monthly', 'annual', 'trial'])->default('monthly');
            $table->decimal('amount_paid', 10, 2)->default(0);
            $table->enum('status', ['active', 'grace', 'expired', 'cancelled'])->default('active');
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->timestamp('grace_ends_at')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('payment_reference')->nullable();
            $table->timestamps();
        });

        Schema::create('subscription_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 10)->default('XOF');
            $table->enum('method', ['mobile_money', 'card', 'bank_transfer', 'cash'])->default('mobile_money');
            $table->string('provider')->nullable(); // Orange Money, MTN, Wave, etc.
            $table->string('reference')->nullable();
            $table->enum('status', ['pending', 'success', 'failed', 'refunded'])->default('pending');
            $table->json('metadata')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_payments');
        Schema::dropIfExists('subscriptions');
    }
};
