<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class TemplateVariable extends Model
{
    use HasUuids;

    protected $fillable = ['template_id', 'key', 'label', 'field_type', 'required', 'placeholder', 'default_value', 'options'];
    protected $casts = ['required' => 'boolean', 'options' => 'array'];

    public function template() { return $this->belongsTo(DocumentTemplate::class); }
}
