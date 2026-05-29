<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionPlan extends Model
{
    protected $fillable = [
        'name', 'slug', 'description', 'monthly_price', 'annual_price',
        'max_branches', 'max_articles', 'max_users', 'max_transactions_per_month',
        'has_advanced_reports', 'has_api_access', 'has_priority_support',
        'trial_days', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'monthly_price' => 'decimal:2',
        'annual_price' => 'decimal:2',
        'has_advanced_reports' => 'boolean',
        'has_api_access' => 'boolean',
        'has_priority_support' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function tenants(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Tenant::class);
    }

    public function isUnlimited(string $field): bool
    {
        return $this->$field === -1;
    }

    public function getFormattedPriceAttribute(): string
    {
        return number_format($this->monthly_price, 0, ',', ' ') . ' FCFA/mois';
    }
}
