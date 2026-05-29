<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleItem extends Model
{
    protected $fillable = [
        'sale_id', 'article_id', 'designation', 'unit',
        'quantity', 'unit_price_ttc', 'discount_amount', 'total_ttc',
    ];

    protected $casts = [
        'unit_price_ttc' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_ttc' => 'decimal:2',
    ];

    public function sale(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function article(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Article::class);
    }
}
