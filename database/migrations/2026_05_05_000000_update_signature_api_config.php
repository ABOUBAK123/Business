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
        // Récupérer les IDs des administrations
        $memfpmaId = DB::table('issuing_administrations')
            ->where('code', 'MEMFPMA')
            ->value('id');

        $mshpcmuId = DB::table('issuing_administrations')
            ->where('code', 'MSHPCMU')
            ->value('id');

        $apiKey = 'act_38Xcy1gjrQ9jTUfozSvpWYMi.3aq7VsWt8GS5ySwBX3Zn4yxF4fS1B1ZACDfE2jzcZzFwixrjokeu6TzrDfq6ivJr';
        $consentPageId = 'cop_MFPnJ1A1qj9saiPvbA8stjB2';
        $signatureProfileId = 'sip_GqGWkYmLrqvSddX6NsxVbEmx';
        $endpoint = 'https://uvci.artci-sign.ci';  // SANS /api (important!)

        // Mettre à jour MEMFPMA
        if ($memfpmaId) {
            DB::table('signature_provider_configs')
                ->where('administration_id', $memfpmaId)
                ->update([
                    'endpoint' => $endpoint,
                    'api_key' => $apiKey,
                    'consent_page_id' => $consentPageId,
                    'signature_profile_id' => $signatureProfileId,
                    'updated_at' => now(),
                ]);
        }

        // Mettre à jour MSHPCMU (DIRECTEUR)
        if ($mshpcmuId) {
            DB::table('signature_provider_configs')
                ->where('administration_id', $mshpcmuId)
                ->update([
                    'endpoint' => $endpoint,
                    'api_key' => $apiKey,
                    'consent_page_id' => $consentPageId,
                    'signature_profile_id' => $signatureProfileId,
                    'updated_at' => now(),
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restaurer les anciennes valeurs si nécessaire (optionnel)
    }
};
