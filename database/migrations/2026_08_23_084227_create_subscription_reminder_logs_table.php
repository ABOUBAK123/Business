<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_reminder_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('days_before');
            $table->date('subscription_ends_at');
            $table->timestamp('sent_at');
            $table->timestamps();

            $table->unique(['tenant_id', 'days_before', 'subscription_ends_at'], 'sub_reminder_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_reminder_logs');
    }
};
