<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class DocumentUserPreference extends Model
{
    use HasUuids;

    protected $fillable = ['user_id', 'document_id', 'is_favorite', 'label_codes'];
    protected $casts = ['is_favorite' => 'boolean', 'label_codes' => 'array'];

    public function document() { return $this->belongsTo(Document::class); }
    public function user() { return $this->belongsTo(User::class); }
}
