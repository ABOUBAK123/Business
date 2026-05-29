<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained();
            $table->foreignId('user_id')->constrained(); // cashier
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('invoice_number')->nullable();
            $table->enum('type', ['sale', 'proforma', 'credit_note'])->default('sale');
            $table->decimal('subtotal_ht', 10, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('total_ttc', 10, 2)->default(0);
            $table->decimal('amount_paid', 10, 2)->default(0);
            $table->decimal('change_given', 10, 2)->default(0);
            $table->enum('payment_status', ['paid', 'partial', 'credit', 'unpaid'])->default('paid');
            $table->json('payment_methods')->nullable(); // [{method, amount, reference}]
            $table->text('notes')->nullable();
            $table->boolean('is_synced')->default(true); // for offline sync
            $table->string('local_id')->nullable(); // offline sale local id
            $table->timestamps();
            $table->index(['tenant_id', 'branch_id']);
            $table->index(['tenant_id', 'created_at']);
        });

        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('article_id')->constrained();
            $table->string('designation');
            $table->string('unit');
            $table->integer('quantity');
            $table->decimal('unit_price_ttc', 10, 2);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('total_ttc', 10, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_items');
        Schema::dropIfExists('sales');
    }
};
