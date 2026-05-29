<?php

namespace App\Traits;

use App\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Builder;

trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope());

        static::creating(function ($model) {
            if (!$model->tenant_id && app()->bound('currentTenant')) {
                $tenant = app('currentTenant');
                if ($tenant) {
                    $model->tenant_id = $tenant->id;
                }
            }
        });
    }

    public function scopeForTenant(Builder $query, int $tenantId): Builder
    {
        return $query->where($this->qualifyColumn('tenant_id'), $tenantId);
    }
}
