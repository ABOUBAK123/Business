<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference',
        'name',
        'unit',
        'price',
        'stock',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'stock' => 'decimal:3',
        'is_active' => 'boolean',
    ];

    public function saleItems()
    {
        return $this->hasMany(SaleItem::class);
    }
}
