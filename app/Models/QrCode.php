<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class QrCode extends Model
{
    use HasUuids;
    public $timestamps = false;

    protected $fillable = ['document_id', 'data', 'metadata', 'verification_code', 'status', 'scan_count', 'created_by', 'expires_at'];
    protected $casts = ['metadata' => 'array', 'scan_count' => 'integer', 'expires_at' => 'datetime', 'created_at' => 'datetime'];

    public function document() { return $this->belongsTo(Document::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
}
