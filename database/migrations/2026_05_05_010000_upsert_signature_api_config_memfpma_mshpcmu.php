<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $memfpmaId = DB::table('issuing_administrations')
            ->where('code', 'MEMFPMA')
            ->value('id');

        $mshpcmuId = DB::table('issuing_administrations')
            ->where('code', 'MSHPCMU')
            ->value('id');

        $payload = [
            'administration_type' => 'emitter',
            'is_active' => 1,
            'endpoint' => 'https://uvci.artci-sign.ci',
            'sign_path' => '/v1/sign',
            'api_key' => 'act_38Xcy1gjrQ9jTUfozSvpWYMi.3aq7VsWt8GS5ySwBX3Zn4yxF4fS1B1ZACDfE2jzcZzFwixrjokeu6TzrDfq6ivJr',
            'consent_page_id' => 'cop_MFPnJ1A1qj9saiPvbA8stjB2',
            'signature_profile_id' => 'sip_GqGWkYmLrqvSddX6NsxVbEmx',
            'verify_ssl' => 1,
            'timeout_ms' => 30000,
            'updated_at' => now(),
        ];

        if ($memfpmaId) {
            DB::table('signature_provider_configs')->updateOrInsert(
                ['administration_id' => $memfpmaId],
                array_merge($payload, [
                    'tenant_id' => 'ten_GuzUzwi3HrL4ghzkWYEsk942',
                    'created_at' => now(),
                ])
            );
        }

        if ($mshpcmuId) {
            DB::table('signature_provider_configs')->updateOrInsert(
                ['administration_id' => $mshpcmuId],
                array_merge($payload, [
                    'tenant_id' => 'ten_GuzUzwi3HrL4ghzkWYEsk942',
                    'created_at' => now(),
                ])
            );
        }
    }

    public function down(): void
    {
        // Intentionally no-op.
    }
};
