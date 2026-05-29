<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qr_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code')->unique(); // the encoded data
            $table->string('image_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['tenant_id', 'article_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qr_codes');
    }
};
