<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_number',
        'user_id',
        'customer_name',
        'total_amount',
        'sold_at',
        'status',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'sold_at' => 'datetime',
    ];

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
