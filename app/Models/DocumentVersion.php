<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class DocumentVersion extends Model
{
    use HasUuids;
    public $timestamps = false;

    protected $fillable = ['document_id', 'version', 'file_path', 'creator_id', 'change_log'];
    protected $casts = ['created_at' => 'datetime'];

    public function document() { return $this->belongsTo(Document::class); }
    public function creator() { return $this->belongsTo(User::class, 'creator_id'); }
}
