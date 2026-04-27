<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('signature_provider_configs', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('(UUID())'));
            $table->uuid('administration_id')->nullable()->index()->comment('NULL = config globale');
            $table->string('administration_type')->default('emitter')->comment('emitter|recipient');
            $table->boolean('is_active')->default(false);
            $table->string('endpoint')->nullable()->comment('URL de base ex: https://uvci.artci-sign.ci');
            $table->string('sign_path')->default('/v1/sign');
            $table->text('api_key')->nullable()->comment('Bearer token');
            $table->string('consent_page_id')->nullable();
            $table->string('signature_profile_id')->nullable();
            $table->string('provider_owner_user_id')->nullable()->comment('Auto-découvert via /api/users/me si vide');
            $table->boolean('verify_ssl')->default(true);
            $table->integer('timeout_ms')->default(30000);
            $table->timestamps();

            $table->unique(['administration_id', 'administration_type'], 'uniq_sig_provider_admin');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('signature_provider_configs');
    }
};
