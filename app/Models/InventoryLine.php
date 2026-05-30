<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryLine extends Model
{
    protected $fillable = [
        'inventory_id', 'article_id', 'theoretical_qty', 'counted_qty', 'gap',
    ];

    protected $casts = [
        'theoretical_qty' => 'decimal:2',
        'counted_qty'     => 'decimal:2',
        'gap'             => 'decimal:2',
    ];

    public function article(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Article::class);
    }
}
