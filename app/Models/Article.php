<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BelongsToTenant;

class Article extends Model
{
    use SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'category_id', 'supplier_id', 'reference', 'designation', 'marque',
        'short_description', 'technical_description', 'unit',
        'purchase_price_ht', 'sale_price_ht', 'tax_rate', 'sale_price_ttc',
        'profit_margin', 'stock_min', 'stock_max', 'photos', 'is_active',
    ];

    protected $casts = [
        'photos' => 'array',
        'purchase_price_ht' => 'decimal:2',
        'sale_price_ht' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'sale_price_ttc' => 'decimal:2',
        'profit_margin' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::saving(function (Article $article) {
            if ($article->sale_price_ht > 0 && $article->tax_rate >= 0) {
                $article->sale_price_ttc = $article->sale_price_ht * (1 + $article->tax_rate / 100);
            }
            if ($article->purchase_price_ht > 0 && $article->sale_price_ht > 0) {
                $article->profit_margin = (($article->sale_price_ht - $article->purchase_price_ht) / $article->purchase_price_ht) * 100;
            }
        });

        static::created(function (Article $article) {
            $article->generateQrCode();
        });
    }

    public function category(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function supplier(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function tenant(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function qrCodes(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(QrCode::class);
    }

    public function mainQrCode(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(QrCode::class)->whereNull('branch_id')->where('is_active', true);
    }

    public function branchStocks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ArticleBranchStock::class);
    }

    public function stockMovements(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function getStockForBranch(int $branchId): int
    {
        return $this->branchStocks()->where('branch_id', $branchId)->value('quantity') ?? 0;
    }

    public function generateQrCode(): QrCode
    {
        $data = json_encode([
            'id' => $this->id,
            'ref' => $this->reference,
            'name' => $this->designation,
            'price' => $this->sale_price_ttc,
            'tenant' => $this->tenant_id,
        ]);

        return QrCode::updateOrCreate(
            ['article_id' => $this->id, 'branch_id' => null],
            ['tenant_id' => $this->tenant_id, 'code' => $data, 'is_active' => true]
        );
    }
}
