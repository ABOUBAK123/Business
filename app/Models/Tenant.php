<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tenant extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'subscription_plan_id', 'commissioner_id', 'shop_name', 'slug', 'tagline', 'description',
        'logo', 'address', 'city', 'country', 'phone', 'email', 'website',
        'rccm', 'ifu', 'currency', 'tax_rate', 'invoice_prefix', 'receipt_message',
        'theme_color', 'business_hours', 'status', 'trial_ends_at',
        'subscription_ends_at', 'owner_id',
    ];

    protected $casts = [
        'business_hours' => 'array',
        'trial_ends_at' => 'datetime',
        'subscription_ends_at' => 'datetime',
        'tax_rate' => 'decimal:2',
    ];

    public function plan(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    public function owner(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function users(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(User::class);
    }

    public function branches(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Branch::class);
    }

    public function mainBranch(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Branch::class)->where('is_main', true);
    }

    public function subscriptions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function activeSubscription(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Subscription::class)->where('status', 'active')->latest();
    }

    public function articles(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Article::class);
    }

    public function commissioner(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'commissioner_id');
    }

    public function commissions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Commission::class);
    }

    public function isActive(): bool
    {
        return in_array($this->status, ['active', 'trial', 'grace']);
    }

    public function isInTrial(): bool
    {
        return $this->status === 'trial' && $this->trial_ends_at?->isFuture();
    }

    public function canAddBranch(): bool
    {
        $max = $this->plan?->max_branches ?? 1;
        if ($max === -1) return true;
        return $this->branches()->count() < $max;
    }

    public function canAddArticle(): bool
    {
        $max = $this->plan?->max_articles ?? 100;
        if ($max === -1) return true;
        return $this->articles()->count() < $max;
    }

    public function canAddUser(): bool
    {
        $max = $this->plan?->max_users ?? 2;
        if ($max === -1) return true;
        return $this->users()->count() < $max;
    }
}
