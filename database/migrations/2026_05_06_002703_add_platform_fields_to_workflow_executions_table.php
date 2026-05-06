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
        Schema::table('workflow_executions', function (Blueprint $table) {
            $table->string('platform_workflow_id')->nullable()->after('document_id')->index();
            $table->string('platform_status')->nullable()->after('platform_workflow_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workflow_executions', function (Blueprint $table) {
            $table->dropIndex(['platform_workflow_id']);
            $table->dropColumn(['platform_workflow_id', 'platform_status']);
        });
    }
};
