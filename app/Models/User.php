<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'tenant_id', 'branch_id', 'name', 'email', 'password',
        'phone', 'avatar', 'pin', 'is_super_admin', 'is_active', 'last_login_at',
    ];

    protected $hidden = ['password', 'remember_token', 'pin'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_super_admin' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function tenant(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function branch(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function sales(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function isSuperAdmin(): bool
    {
        return $this->is_super_admin;
    }

    public function isOwner(): bool
    {
        return $this->hasRole('proprietaire');
    }

    public function isCommissioner(): bool
    {
        return $this->hasRole('commissionnaire');
    }

    public function commissionedTenants(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Tenant::class, 'commissioner_id')->withoutGlobalScopes();
    }

    public function commissions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Commission::class, 'commissioner_id');
    }

    public function canAccessBranch(int $branchId): bool
    {
        if ($this->hasRole(['proprietaire', 'admin_boutique', 'comptable'])) {
            return $this->tenant->branches()->where('id', $branchId)->exists();
        }
        return $this->branch_id === $branchId;
    }
}
