<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('signature_provider_configs', function (Blueprint $table) {
            $table->string('tenant_id', 120)->nullable()->after('provider_owner_user_id')
                ->comment('Identifiant du tenant sur la plateforme (ex: ten_Guj71mvWbKxFVg8mMnZE4CAv)');
            $table->string('consent_page_id_approval', 120)->nullable()->after('consent_page_id')
                ->comment('Consent page ID pour les étapes d\'approbation (stepType: approval)');
        });
    }

    public function down(): void
    {
        Schema::table('signature_provider_configs', function (Blueprint $table) {
            $table->dropColumn(['tenant_id', 'consent_page_id_approval']);
        });
    }
};
