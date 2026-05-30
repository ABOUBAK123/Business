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
        Schema::create('cash_closings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('branch_id')->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->date('date');
            $table->decimal('opening_cash', 12, 2)->default(0);
            $table->decimal('closing_cash', 12, 2)->default(0);
            $table->decimal('theoretical_cash', 12, 2)->default(0);
            $table->decimal('cash_gap', 12, 2)->default(0);
            $table->decimal('total_sales', 12, 2)->default(0);
            $table->integer('sales_count')->default(0);
            $table->json('payment_summary')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'branch_id', 'date']);
            $table->index(['tenant_id', 'branch_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_closings');
    }
};
