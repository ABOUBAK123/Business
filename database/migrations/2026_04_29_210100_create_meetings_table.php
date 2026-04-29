<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meetings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title', 255);
            $table->enum('meeting_type', [
                'ordinary',
                'extraordinary',
                'management_committee',
                'project',
                'technical',
                'other',
            ])->default('ordinary')->index();
            $table->uuid('meeting_room_id')->index();
            $table->foreign('meeting_room_id')->references('id')->on('meeting_rooms')->onDelete('restrict');
            $table->dateTime('starts_at')->index();
            $table->dateTime('ends_at')->index();
            $table->unsignedInteger('estimated_duration_minutes')->nullable();
            $table->uuid('organizer_id')->index();
            $table->foreign('organizer_id')->references('id')->on('users')->onDelete('cascade');
            $table->uuid('minutes_writer_id')->index();
            $table->foreign('minutes_writer_id')->references('id')->on('users')->onDelete('restrict');
            $table->longText('agenda')->nullable();
            $table->json('attachments')->nullable();
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal')->index();
            $table->enum('confidentiality', ['public', 'internal', 'confidential'])->default('internal')->index();
            $table->enum('status', ['draft', 'planned', 'in_progress', 'closed', 'cancelled'])->default('planned')->index();
            $table->enum('recurrence_type', ['none', 'daily', 'weekly', 'monthly', 'yearly'])->default('none')->index();
            $table->date('recurrence_until')->nullable();
            $table->json('recurrence_exceptions')->nullable();
            $table->string('qr_token', 128)->unique();
            $table->dateTime('qr_valid_from')->nullable();
            $table->dateTime('qr_valid_until')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meetings');
    }
};
