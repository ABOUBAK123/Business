<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionReminderLog extends Model
{
    protected $fillable = ['tenant_id', 'days_before', 'subscription_ends_at', 'sent_at'];

    protected $casts = [
        'subscription_ends_at' => 'date',
        'sent_at' => 'datetime',
    ];

    public function tenant(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
