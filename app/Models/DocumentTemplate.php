<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class DocumentTemplate extends Model
{
    use HasUuids;

    protected $fillable = ['name', 'file_name', 'file_type', 'storage_path', 'content', 'administration_id', 'created_by', 'signature_zones'];

    public function variables() { return $this->hasMany(TemplateVariable::class, 'template_id'); }
    public function administration() { return $this->belongsTo(IssuingAdministration::class, 'administration_id'); }
}
