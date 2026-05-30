<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class CashClosing extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'branch_id', 'user_id', 'date',
        'opening_cash', 'closing_cash', 'theoretical_cash', 'cash_gap',
        'total_sales', 'sales_count', 'payment_summary', 'notes',
    ];

    protected $casts = [
        'date'             => 'date',
        'opening_cash'     => 'decimal:2',
        'closing_cash'     => 'decimal:2',
        'theoretical_cash' => 'decimal:2',
        'cash_gap'         => 'decimal:2',
        'total_sales'      => 'decimal:2',
        'payment_summary'  => 'array',
    ];

    public function branch(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
