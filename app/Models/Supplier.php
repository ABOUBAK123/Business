<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class Supplier extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'name', 'contact_name', 'phone', 'email',
        'address', 'city', 'country', 'payment_terms', 'is_active', 'notes',
    ];

    public function articles(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Article::class);
    }

    public function purchaseOrders(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }
}
