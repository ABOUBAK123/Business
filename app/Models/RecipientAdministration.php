<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class RecipientAdministration extends Model
{
    use HasUuids;

    protected $fillable = ['name', 'code', 'channel', 'api_endpoint', 'email_address', 'logo', 'metadata', 'is_active'];
    protected $casts = ['is_active' => 'boolean', 'metadata' => 'array'];

    public function routingRules() { return $this->hasMany(RoutingRule::class, 'recipient_id'); }
}
