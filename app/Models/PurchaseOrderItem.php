<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrderItem extends Model
{
    protected $table = 'purchase_order_items';

    protected $fillable = [
        'purchase_order_id', 'article_id', 'quantity_ordered', 'quantity_received', 'unit_cost', 'total_cost',
    ];

    public function article(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Article::class);
    }
}
