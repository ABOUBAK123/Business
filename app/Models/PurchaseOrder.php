<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class PurchaseOrder extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'branch_id', 'supplier_id', 'user_id', 'order_number',
        'status', 'total_amount', 'notes', 'ordered_at', 'expected_at', 'received_at',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'ordered_at' => 'datetime',
        'expected_at' => 'datetime',
        'received_at' => 'datetime',
    ];

    public function supplier(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function branch(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function items(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }
}
