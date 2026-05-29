<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionPayment extends Model
{
    protected $table = 'subscription_payments';

    protected $fillable = [
        'tenant_id', 'subscription_id', 'amount', 'currency', 'method',
        'provider', 'reference', 'status', 'metadata', 'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'metadata' => 'array',
        'paid_at' => 'datetime',
    ];

    public function subscription(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }
}
