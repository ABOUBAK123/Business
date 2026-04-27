<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Signature extends Model
{
    use HasUuids;
    public $timestamps = false;

    protected $fillable = ['document_id', 'signer_id', 'signature', 'certificate', 'signed_at', 'reason', 'location', 'is_valid', 'status', 'signature_algorithm'];
    protected $casts = ['is_valid' => 'boolean', 'signed_at' => 'datetime', 'created_at' => 'datetime'];
    protected $hidden = ['signature'];

    public function document() { return $this->belongsTo(Document::class); }
    public function signer() { return $this->belongsTo(User::class, 'signer_id'); }
}
