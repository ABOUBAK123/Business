<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BelongsToTenant;

class Customer extends Model
{
    use SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'name', 'phone', 'email', 'address', 'nif',
        'type', 'classification', 'credit_limit', 'credit_balance',
    ];

    protected $casts = [
        'credit_limit' => 'decimal:2',
        'credit_balance' => 'decimal:2',
    ];

    public function sales(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function payments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CustomerPayment::class);
    }

    public function getAvailableCreditAttribute(): float
    {
        return max(0, (float) $this->credit_limit - (float) $this->credit_balance);
    }

    public function hasAvailableCredit(float $amount): bool
    {
        return ($this->credit_limit - $this->credit_balance) >= $amount;
    }
}
