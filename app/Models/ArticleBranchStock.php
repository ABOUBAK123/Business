<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArticleBranchStock extends Model
{
    protected $table = 'article_branch_stock';

    protected $fillable = ['article_id', 'branch_id', 'quantity', 'sale_price_ttc'];

    public function article(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    public function branch(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function isLow(): bool
    {
        return $this->quantity <= ($this->article?->stock_min ?? 0);
    }
}
