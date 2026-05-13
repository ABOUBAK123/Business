<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('document_shares')) {
            return;
        }
        Schema::table('document_shares', function (Blueprint $table) {
            if (!Schema::hasColumn('document_shares', 'reception_status')) {
                // null = non ouvert, 'recu' = téléchargé, 'transmis' = transmis à une entité
                $table->string('reception_status', 30)->nullable()->after('expires_at');
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('document_shares', 'reception_status')) {
            Schema::table('document_shares', function (Blueprint $table) {
                $table->dropColumn('reception_status');
            });
        }
    }
};
