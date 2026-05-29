<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Commission extends Model
{
    protected $fillable = [
        'commissioner_id', 'tenant_id', 'base_amount',
        'rate', 'amount', 'status', 'period', 'paid_at', 'notes',
    ];

    protected $casts = [
        'base_amount' => 'decimal:2',
        'rate'        => 'decimal:2',
        'amount'      => 'decimal:2',
        'paid_at'     => 'datetime',
    ];

    public function commissioner(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'commissioner_id');
    }

    public function tenant(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public static function generate(Tenant $tenant, string $period): self
    {
        $base   = $tenant->plan?->monthly_price ?? 0;
        $rate   = 3.00;
        $amount = round($base * $rate / 100, 2);

        return self::firstOrCreate(
            ['tenant_id' => $tenant->id, 'period' => $period],
            [
                'commissioner_id' => $tenant->commissioner_id,
                'base_amount'     => $base,
                'rate'            => $rate,
                'amount'          => $amount,
                'status'          => 'pending',
            ]
        );
    }
}
