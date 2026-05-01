<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('meetings')) {
            return;
        }

        Schema::table('meetings', function (Blueprint $table) {
            if (!Schema::hasColumn('meetings', 'validator_id')) {
                $table->uuid('validator_id')->nullable()->after('minutes_writer_id')->index();
                $table->foreign('validator_id')->references('id')->on('users')->nullOnDelete();
            }

            if (!Schema::hasColumn('meetings', 'validation_requested_at')) {
                $table->dateTime('validation_requested_at')->nullable()->after('review_comment');
            }

            if (!Schema::hasColumn('meetings', 'validated_by')) {
                $table->uuid('validated_by')->nullable()->after('validation_requested_at')->index();
                $table->foreign('validated_by')->references('id')->on('users')->nullOnDelete();
            }

            if (!Schema::hasColumn('meetings', 'validated_at')) {
                $table->dateTime('validated_at')->nullable()->after('validated_by');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('meetings')) {
            return;
        }

        Schema::table('meetings', function (Blueprint $table) {
            if (Schema::hasColumn('meetings', 'validator_id')) {
                $table->dropForeign(['validator_id']);
                $table->dropColumn('validator_id');
            }
            if (Schema::hasColumn('meetings', 'validated_by')) {
                $table->dropForeign(['validated_by']);
                $table->dropColumn('validated_by');
            }
            if (Schema::hasColumn('meetings', 'validation_requested_at')) {
                $table->dropColumn('validation_requested_at');
            }
            if (Schema::hasColumn('meetings', 'validated_at')) {
                $table->dropColumn('validated_at');
            }
        });
    }
};
