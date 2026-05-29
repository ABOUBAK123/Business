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
        Schema::table('tenants', function (Blueprint $table) {
            $table->unsignedBigInteger('commissioner_id')->nullable()->after('owner_id');
        });

        Schema::create('commissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('commissioner_id');
            $table->unsignedBigInteger('tenant_id');
            $table->decimal('base_amount', 12, 2);
            $table->decimal('rate', 5, 2)->default(3.00);
            $table->decimal('amount', 12, 2);
            $table->enum('status', ['pending', 'paid'])->default('pending');
            $table->string('period', 7)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('commissioner_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commissions');
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('commissioner_id');
        });
    }
};
