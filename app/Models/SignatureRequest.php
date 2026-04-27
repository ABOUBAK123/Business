<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class SignatureRequest extends Model
{
    use HasUuids;
    public $timestamps = false;

    protected $fillable = [
        'document_id', 'requested_by', 'requested_to', 'message', 'status',
        'expiry_date', 'responded_at',
        'zone_page', 'zone_x', 'zone_y', 'zone_width', 'zone_height', 'zone_label',
    ];
    protected $casts = [
        'expiry_date'  => 'datetime',
        'responded_at' => 'datetime',
        'created_at'   => 'datetime',
        'zone_x'       => 'float',
        'zone_y'       => 'float',
        'zone_width'   => 'float',
        'zone_height'  => 'float',
        'zone_page'    => 'integer',
    ];

    public function document() { return $this->belongsTo(Document::class); }
    public function requester() { return $this->belongsTo(User::class, 'requested_by'); }
    public function recipient() { return $this->belongsTo(User::class, 'requested_to'); }
}
