<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained();
            $table->foreignId('article_id')->constrained();
            $table->foreignId('user_id')->constrained();
            $table->enum('type', ['in', 'out', 'transfer_in', 'transfer_out', 'adjustment', 'inventory']);
            $table->integer('quantity'); // positive for in, negative for out
            $table->integer('stock_before');
            $table->integer('stock_after');
            $table->decimal('unit_cost', 10, 2)->nullable();
            $table->string('reference')->nullable(); // sale_id, transfer_id, etc.
            $table->string('reference_type')->nullable(); // App\Models\Sale, etc.
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'branch_id', 'article_id']);
        });

        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('from_branch_id')->constrained('branches');
            $table->foreignId('to_branch_id')->constrained('branches');
            $table->foreignId('requested_by')->constrained('users');
            $table->foreignId('confirmed_by')->nullable()->constrained('users');
            $table->string('transfer_number');
            $table->enum('status', ['pending', 'in_transit', 'received', 'cancelled'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamps();
        });

        Schema::create('stock_transfer_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_transfer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('article_id')->constrained();
            $table->integer('quantity_requested');
            $table->integer('quantity_received')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_transfer_items');
        Schema::dropIfExists('stock_transfers');
        Schema::dropIfExists('stock_movements');
    }
};
