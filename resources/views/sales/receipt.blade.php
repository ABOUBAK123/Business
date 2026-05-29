<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Reçu {{ $sale->invoice_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Courier New', monospace; font-size: 12px; max-width: 300px; margin: 0 auto; padding: 10px; }
        .center { text-align: center; }
        .bold { font-weight: bold; }
        .large { font-size: 16px; }
        .divider { border-top: 1px dashed #000; margin: 6px 0; }
        .row { display: flex; justify-content: space-between; margin: 2px 0; }
        .total-row { font-weight: bold; font-size: 14px; border-top: 2px solid #000; padding-top: 4px; margin-top: 4px; }
        .footer { text-align: center; margin-top: 10px; font-size: 10px; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    <div class="center bold large">{{ $sale->branch?->tenant?->shop_name ?? 'QUINCAILLERIE' }}</div>
    @if($sale->branch?->tenant?->address)
        <div class="center">{{ $sale->branch->tenant->address }}, {{ $sale->branch->tenant->city }}</div>
    @endif
    @if($sale->branch?->tenant?->phone)
        <div class="center">Tél: {{ $sale->branch->tenant->phone }}</div>
    @endif

    <div class="divider"></div>

    <div class="center bold">{{ $sale->type === 'proforma' ? 'FACTURE PROFORMA' : 'REÇU DE VENTE' }}</div>
    <div class="row"><span>N° :</span><span class="bold">{{ $sale->invoice_number }}</span></div>
    <div class="row"><span>Date :</span><span>{{ $sale->created_at->format('d/m/Y H:i') }}</span></div>
    <div class="row"><span>Caissier :</span><span>{{ $sale->user?->name }}</span></div>
    @if($sale->customer)
        <div class="row"><span>Client :</span><span>{{ $sale->customer->name }}</span></div>
    @endif

    <div class="divider"></div>

    @foreach($sale->items as $item)
        <div>{{ Str::limit($item->designation, 22) }}</div>
        <div class="row">
            <span>{{ $item->quantity }} x {{ number_format($item->unit_price_ttc, 0) }}</span>
            <span>{{ number_format($item->total_ttc, 0) }}</span>
        </div>
    @endforeach

    <div class="divider"></div>

    <div class="row"><span>Sous-total HT</span><span>{{ number_format($sale->subtotal_ht, 0) }}</span></div>
    <div class="row"><span>TVA</span><span>{{ number_format($sale->tax_amount, 0) }}</span></div>
    @if($sale->discount_amount > 0)
        <div class="row"><span>Remise</span><span>-{{ number_format($sale->discount_amount, 0) }}</span></div>
    @endif
    <div class="total-row row"><span>TOTAL TTC</span><span>{{ number_format($sale->total_ttc, 0, ',', ' ') }} FCFA</span></div>

    <div class="divider"></div>

    @foreach($sale->payment_methods ?? [] as $pm)
        <div class="row">
            <span>{{ ucfirst($pm['method'] ?? '') }}</span>
            <span>{{ number_format($pm['amount'] ?? 0, 0) }}</span>
        </div>
    @endforeach
    @if($sale->change_given > 0)
        <div class="row bold"><span>MONNAIE</span><span>{{ number_format($sale->change_given, 0) }} FCFA</span></div>
    @endif

    <div class="footer">
        <div class="divider"></div>
        <div>{{ $sale->branch?->tenant?->receipt_message ?? 'Merci pour votre achat !' }}</div>
        <div>Bonne journée !</div>
    </div>

    <div class="no-print" style="margin-top: 20px; text-align: center;">
        <button onclick="window.print()" style="background:#1e40af;color:white;padding:8px 20px;border:none;border-radius:6px;cursor:pointer;margin-right:8px;">
            🖨 Imprimer
        </button>
        <a href="{{ route('sales.create') }}" style="background:#059669;color:white;padding:8px 20px;border:none;border-radius:6px;cursor:pointer;text-decoration:none;">
            + Nouvelle vente
        </a>
    </div>
</body>
</html>
