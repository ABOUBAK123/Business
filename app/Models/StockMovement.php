<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class StockMovement extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'branch_id', 'article_id', 'user_id', 'type',
        'quantity', 'stock_before', 'stock_after', 'unit_cost',
        'reference', 'reference_type', 'notes',
    ];

    public function article(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    public function branch(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
