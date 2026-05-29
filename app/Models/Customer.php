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

    public function hasAvailableCredit(float $amount): bool
    {
        return ($this->credit_limit - $this->credit_balance) >= $amount;
    }
}
