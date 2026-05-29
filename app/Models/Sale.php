<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class Sale extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'branch_id', 'user_id', 'customer_id', 'invoice_number',
        'type', 'subtotal_ht', 'tax_amount', 'discount_amount', 'total_ttc',
        'amount_paid', 'change_given', 'payment_status', 'payment_methods',
        'notes', 'is_synced', 'local_id',
    ];

    protected $casts = [
        'payment_methods' => 'array',
        'subtotal_ht' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_ttc' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'change_given' => 'decimal:2',
        'is_synced' => 'boolean',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::created(function (Sale $sale) {
            if (!$sale->invoice_number) {
                $prefix = $sale->tenant?->invoice_prefix ?? 'FAC';
                $sale->update(['invoice_number' => $prefix . '-' . str_pad($sale->id, 6, '0', STR_PAD_LEFT)]);
            }
            // Deduct stock
            foreach ($sale->items as $item) {
                $stock = ArticleBranchStock::firstOrCreate(
                    ['article_id' => $item->article_id, 'branch_id' => $sale->branch_id],
                    ['quantity' => 0]
                );
                $stockBefore = $stock->quantity;
                $stock->decrement('quantity', $item->quantity);
                StockMovement::create([
                    'tenant_id' => $sale->tenant_id,
                    'branch_id' => $sale->branch_id,
                    'article_id' => $item->article_id,
                    'user_id' => $sale->user_id,
                    'type' => 'out',
                    'quantity' => -$item->quantity,
                    'stock_before' => $stockBefore,
                    'stock_after' => $stockBefore - $item->quantity,
                    'reference' => (string) $sale->id,
                    'reference_type' => Sale::class,
                ]);
            }
        });
    }

    public function branch(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function customer(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function getBalanceDueAttribute(): float
    {
        return max(0, $this->total_ttc - $this->amount_paid);
    }
}
