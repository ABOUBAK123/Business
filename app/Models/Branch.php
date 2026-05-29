<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class Branch extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'name', 'address', 'city', 'phone', 'email',
        'manager_id', 'is_main', 'is_active', 'use_shared_stock',
    ];

    protected $casts = [
        'is_main' => 'boolean',
        'is_active' => 'boolean',
        'use_shared_stock' => 'boolean',
    ];

    public function tenant(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function manager(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function users(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(User::class);
    }

    public function sales(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function stockMovements(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function getTodaySalesAttribute(): float
    {
        return $this->sales()->whereDate('created_at', today())->sum('total_ttc');
    }
}
