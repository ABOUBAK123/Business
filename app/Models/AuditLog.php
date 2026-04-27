<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasUuids;
    public $timestamps = false;

    protected $fillable = ['user_id', 'action', 'entity_type', 'entity_id', 'changes', 'ip_address', 'user_agent', 'description'];
    protected $casts = ['changes' => 'array', 'created_at' => 'datetime'];

    public function user() { return $this->belongsTo(User::class); }
}
