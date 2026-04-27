<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class IssuingAdministration extends Model
{
    use HasUuids;

    protected $fillable = ['name', 'code', 'sub_entity_code', 'is_active', 'document_number_prefix', 'document_number_padding', 'document_number_sequence', 'logo', 'metadata'];
    protected $casts = ['is_active' => 'boolean', 'metadata' => 'array'];

    public function profiles() { return $this->hasMany(AdministrationProfile::class, 'administration_id'); }
    public function adminUsers() { return $this->hasMany(AdministrationUser::class, 'administration_id'); }
    public function templates() { return $this->hasMany(DocumentTemplate::class, 'administration_id'); }
}
