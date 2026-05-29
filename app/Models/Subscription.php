<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    protected $fillable = [
        'tenant_id', 'subscription_plan_id', 'billing_cycle', 'amount_paid',
        'status', 'starts_at', 'ends_at', 'grace_ends_at', 'payment_method', 'payment_reference',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'grace_ends_at' => 'datetime',
        'amount_paid' => 'decimal:2',
    ];

    public function tenant(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function plan(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    public function payments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SubscriptionPayment::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && $this->ends_at->isFuture();
    }
}
