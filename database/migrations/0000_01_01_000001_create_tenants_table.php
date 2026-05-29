<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_plan_id')->nullable()->constrained('subscription_plans')->nullOnDelete();
            $table->string('shop_name');
            $table->string('slug')->unique();
            $table->string('tagline')->nullable();
            $table->text('description')->nullable();
            $table->string('logo')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->default('CI');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->string('rccm')->nullable();
            $table->string('ifu')->nullable();
            $table->string('currency', 10)->default('XOF');
            $table->decimal('tax_rate', 5, 2)->default(18.00);
            $table->string('invoice_prefix')->default('FAC');
            $table->string('receipt_message')->nullable();
            $table->string('theme_color', 7)->default('#1e40af');
            $table->json('business_hours')->nullable();
            $table->enum('status', ['trial', 'active', 'grace', 'suspended', 'expired'])->default('trial');
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('subscription_ends_at')->nullable();
            $table->unsignedBigInteger('owner_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
