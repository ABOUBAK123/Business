<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class Category extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'parent_id', 'name', 'slug', 'icon', 'is_active', 'sort_order'];

    public function parent(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function articles(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Article::class);
    }
}
