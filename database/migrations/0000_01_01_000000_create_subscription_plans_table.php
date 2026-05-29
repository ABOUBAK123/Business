<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Découverte, Starter, Pro, Entreprise
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('monthly_price', 10, 2)->default(0);
            $table->decimal('annual_price', 10, 2)->default(0);
            $table->integer('max_branches')->default(1); // -1 = unlimited
            $table->integer('max_articles')->default(100); // -1 = unlimited
            $table->integer('max_users')->default(2);     // -1 = unlimited
            $table->integer('max_transactions_per_month')->default(200); // -1 = unlimited
            $table->boolean('has_advanced_reports')->default(false);
            $table->boolean('has_api_access')->default(false);
            $table->boolean('has_priority_support')->default(false);
            $table->integer('trial_days')->default(0);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_plans');
    }
};
