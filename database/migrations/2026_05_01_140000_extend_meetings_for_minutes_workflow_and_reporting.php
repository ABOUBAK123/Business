<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('meetings')) {
            Schema::table('meetings', function (Blueprint $table) {
                if (!Schema::hasColumn('meetings', 'processing_deadline')) {
                    $table->dateTime('processing_deadline')->nullable()->after('ends_at');
                }
                if (!Schema::hasColumn('meetings', 'minutes_template')) {
                    $table->longText('minutes_template')->nullable()->after('agenda');
                }
                if (!Schema::hasColumn('meetings', 'minutes_content')) {
                    $table->longText('minutes_content')->nullable()->after('minutes_template');
                }
                if (!Schema::hasColumn('meetings', 'workflow_status')) {
                    $table->enum('workflow_status', ['draft', 'in_validation', 'validated', 'published'])->default('draft')->after('status');
                }
                if (!Schema::hasColumn('meetings', 'review_requested')) {
                    $table->boolean('review_requested')->default(false)->after('workflow_status');
                }
                if (!Schema::hasColumn('meetings', 'review_comment')) {
                    $table->text('review_comment')->nullable()->after('review_requested');
                }
                if (!Schema::hasColumn('meetings', 'writer_signature_path')) {
                    $table->string('writer_signature_path', 1000)->nullable()->after('review_comment');
                }
                if (!Schema::hasColumn('meetings', 'writer_signed_at')) {
                    $table->dateTime('writer_signed_at')->nullable()->after('writer_signature_path');
                }
                if (!Schema::hasColumn('meetings', 'published_at')) {
                    $table->dateTime('published_at')->nullable()->after('writer_signed_at');
                }
                if (!Schema::hasColumn('meetings', 'diffusion_email_subject')) {
                    $table->string('diffusion_email_subject', 255)->nullable()->after('published_at');
                }
                if (!Schema::hasColumn('meetings', 'diffusion_email_body')) {
                    $table->longText('diffusion_email_body')->nullable()->after('diffusion_email_subject');
                }
                if (!Schema::hasColumn('meetings', 'diffusion_ack_required')) {
                    $table->boolean('diffusion_ack_required')->default(false)->after('diffusion_email_body');
                }
            });
        }

        if (!Schema::hasTable('meeting_minutes_versions')) {
            Schema::create('meeting_minutes_versions', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('meeting_id')->index();
                $table->foreign('meeting_id')->references('id')->on('meetings')->onDelete('cascade');
                $table->unsignedInteger('version_no')->default(1);
                $table->longText('content')->nullable();
                $table->uuid('created_by')->nullable()->index();
                $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
                $table->string('note', 255)->nullable();
                $table->string('workflow_status', 40)->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('meeting_minutes_versions')) {
            Schema::dropIfExists('meeting_minutes_versions');
        }

        if (Schema::hasTable('meetings')) {
            Schema::table('meetings', function (Blueprint $table) {
                $columns = [
                    'processing_deadline',
                    'minutes_template',
                    'minutes_content',
                    'workflow_status',
                    'review_requested',
                    'review_comment',
                    'writer_signature_path',
                    'writer_signed_at',
                    'published_at',
                    'diffusion_email_subject',
                    'diffusion_email_body',
                    'diffusion_ack_required',
                ];

                foreach ($columns as $column) {
                    if (Schema::hasColumn('meetings', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
