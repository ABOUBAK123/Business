<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reference')->nullable();
            $table->string('designation');
            $table->text('short_description')->nullable();
            $table->text('technical_description')->nullable();
            $table->string('unit')->default('pièce'); // pièce, mètre, kg, litre, boite, lot
            $table->decimal('purchase_price_ht', 10, 2)->default(0);
            $table->decimal('sale_price_ht', 10, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('sale_price_ttc', 10, 2)->default(0);
            $table->decimal('profit_margin', 5, 2)->default(0); // calculated
            $table->integer('stock_min')->default(0);
            $table->integer('stock_max')->default(0);
            $table->json('photos')->nullable(); // array of file paths
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['tenant_id', 'reference']);
            $table->index(['tenant_id', 'designation']);
        });

        // Stock per branch
        Schema::create('article_branch_stock', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->integer('quantity')->default(0);
            $table->decimal('sale_price_ttc', 10, 2)->nullable(); // branch-specific price
            $table->timestamps();
            $table->unique(['article_id', 'branch_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('article_branch_stock');
        Schema::dropIfExists('articles');
    }
};
