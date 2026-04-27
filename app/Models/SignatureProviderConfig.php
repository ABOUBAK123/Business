<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SignatureProviderConfig extends Model
{
    use HasUuids;

    protected $table = 'signature_provider_configs';

    protected $fillable = [
        'administration_id',
        'administration_type',
        'is_active',
        'endpoint',
        'sign_path',
        'api_key',
        'tenant_id',
        'consent_page_id',
        'consent_page_id_approval',
        'signature_profile_id',
        'provider_owner_user_id',
        'verify_ssl',
        'timeout_ms',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'verify_ssl' => 'boolean',
        'timeout_ms' => 'integer',
    ];

    public function issuingAdministration(): BelongsTo
    {
        return $this->belongsTo(IssuingAdministration::class, 'administration_id');
    }
}
