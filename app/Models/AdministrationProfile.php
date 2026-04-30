<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class AdministrationProfile extends Model
{
    use HasUuids;

    protected $fillable = ['administration_id', 'administration_type', 'name', 'description', 'permissions'];
    protected $casts = ['permissions' => 'array'];

    public function administration() { return $this->belongsTo(IssuingAdministration::class, 'administration_id'); }
    public function emitterAdministration() { return $this->belongsTo(IssuingAdministration::class, 'administration_id'); }
    public function recipientAdministration() { return $this->belongsTo(RecipientAdministration::class, 'administration_id'); }
    public function users() { return $this->hasMany(AdministrationUser::class, 'profile_id'); }

    public function getEffectiveAdministrationTypeAttribute(): ?string
    {
        if (!$this->administration_id) {
            return null;
        }

        return $this->administration_type === 'recipient' ? 'recipient' : 'emitter';
    }

    public function getResolvedAdministrationAttribute(): IssuingAdministration|RecipientAdministration|null
    {
        if (!$this->administration_id) {
            return null;
        }

        if ($this->effective_administration_type === 'recipient') {
            if ($this->relationLoaded('recipientAdministration')) {
                return $this->getRelation('recipientAdministration');
            }

            return $this->recipientAdministration()->first();
        }

        if ($this->relationLoaded('emitterAdministration')) {
            return $this->getRelation('emitterAdministration');
        }

        return $this->emitterAdministration()->first();
    }

    public function getAdministrationLabelAttribute(): string
    {
        return $this->resolved_administration?->name ?? '—';
    }

    public function getAdministrationTypeLabelAttribute(): string
    {
        return match ($this->effective_administration_type) {
            'recipient' => 'Destinataire',
            'emitter' => 'Émettrice',
            default => 'Globale',
        };
    }
}
