<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facture {{ $sale->invoice_number }}</title>
    <style>
        /* ── Général ──────────────────────────────────── */
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', Arial, sans-serif; font-size: 13px;
               color: #1f2937; background: #f3f4f6; }

        /* ── Boutons écran ────────────────────────────── */
        .screen-bar {
            background: #1e3a8a; color: white; padding: 12px 24px;
            display: flex; gap: 12px; align-items: center;
        }
        .screen-bar h1 { flex: 1; font-size: 15px; }
        .btn { padding: 8px 18px; border-radius: 8px; font-size: 13px;
               font-weight: 600; cursor: pointer; border: none; text-decoration: none;
               display: inline-flex; align-items: center; gap: 6px; }
        .btn-white  { background: white; color: #1e3a8a; }
        .btn-close  { background: rgba(255,255,255,.15); color: white; }

        /* ── Page A4 ──────────────────────────────────── */
        .page-wrapper { padding: 24px; display: flex; justify-content: center; }
        .invoice {
            background: white; width: 210mm; min-height: 297mm;
            padding: 16mm 18mm; box-shadow: 0 4px 24px rgba(0,0,0,.12);
        }

        /* ── En-tête ──────────────────────────────────── */
        .header { display: flex; justify-content: space-between; margin-bottom: 10mm; }
        .company-name { font-size: 22px; font-weight: 800; color: #1e3a8a; }
        .company-info { font-size: 11px; color: #6b7280; line-height: 1.6; margin-top: 4px; }
        .invoice-title { text-align: right; }
        .invoice-title h2 { font-size: 28px; font-weight: 800; color: #1e3a8a;
                            letter-spacing: 2px; text-transform: uppercase; }
        .invoice-number { font-size: 14px; font-weight: 700; color: #374151; margin-top: 4px; }
        .invoice-date { font-size: 11px; color: #6b7280; margin-top: 2px; }

        /* ── Bande bleue ──────────────────────────────── */
        .band { background: #1e3a8a; color: white; height: 3px; margin-bottom: 8mm; }

        /* ── Infos client + facturation ───────────────── */
        .meta { display: flex; justify-content: space-between; margin-bottom: 8mm; }
        .meta-box { background: #f8fafc; border: 1px solid #e5e7eb;
                    border-radius: 8px; padding: 5mm; width: 48%; }
        .meta-box h4 { font-size: 10px; text-transform: uppercase; letter-spacing: 1px;
                       color: #6b7280; margin-bottom: 4px; }
        .meta-box p { font-size: 12px; line-height: 1.6; color: #374151; }
        .meta-box .name { font-weight: 700; font-size: 14px; color: #1f2937; }

        /* ── Tableau articles ─────────────────────────── */
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 6mm; }
        .items-table thead tr { background: #1e3a8a; color: white; }
        .items-table th { padding: 8px 10px; text-align: left; font-size: 11px;
                          font-weight: 600; letter-spacing: 0.5px; }
        .items-table th:last-child, .items-table td:last-child { text-align: right; }
        .items-table th:nth-child(2), .items-table td:nth-child(2) { text-align: right; }
        .items-table th:nth-child(3), .items-table td:nth-child(3) { text-align: right; }
        .items-table tbody tr:nth-child(even) { background: #f9fafb; }
        .items-table tbody tr:hover { background: #eff6ff; }
        .items-table td { padding: 7px 10px; font-size: 12px; border-bottom: 1px solid #f3f4f6; }
        .items-table .ref { font-size: 10px; color: #9ca3af; font-family: monospace; }

        /* ── Totaux ───────────────────────────────────── */
        .totals { display: flex; justify-content: flex-end; margin-bottom: 8mm; }
        .totals-box { width: 220px; }
        .total-row { display: flex; justify-content: space-between;
                     padding: 4px 0; font-size: 12px; color: #6b7280; }
        .total-row.main { font-size: 15px; font-weight: 800; color: #1e3a8a;
                          border-top: 2px solid #1e3a8a; padding-top: 8px; margin-top: 4px; }
        .total-row span:last-child { font-weight: 600; color: #374151; }
        .total-row.main span:last-child { color: #1e3a8a; }

        /* ── Modes de paiement ────────────────────────── */
        .payment-section { margin-bottom: 8mm; }
        .payment-section h4 { font-size: 10px; text-transform: uppercase;
                              letter-spacing: 1px; color: #6b7280; margin-bottom: 4px; }
        .payment-tags { display: flex; gap: 8px; flex-wrap: wrap; }
        .payment-tag { background: #eff6ff; color: #1e3a8a; border: 1px solid #bfdbfe;
                       border-radius: 20px; padding: 3px 12px; font-size: 11px; font-weight: 600; }
        .payment-tag.credit { background: #fef3c7; color: #92400e; border-color: #fcd34d; }

        /* ── Notes ────────────────────────────────────── */
        .notes-section { background: #f8fafc; border-left: 3px solid #1e3a8a;
                         padding: 8px 12px; margin-bottom: 8mm; font-size: 11px; color: #6b7280; }

        /* ── Pied de page ─────────────────────────────── */
        .footer { border-top: 1px solid #e5e7eb; padding-top: 4mm; margin-top: auto;
                  display: flex; justify-content: space-between; font-size: 10px; color: #9ca3af; }

        /* ── Impression ───────────────────────────────── */
        @media print {
            body { background: white; }
            .screen-bar { display: none; }
            .page-wrapper { padding: 0; }
            .invoice { box-shadow: none; width: 100%; min-height: auto; }
            @page { size: A4; margin: 0; }
        }
    </style>
</head>
<body>

{{-- Barre boutons (masquée à l'impression) --}}
<div class="screen-bar">
    <h1>Facture — {{ $sale->invoice_number }}</h1>
    <button class="btn btn-white" onclick="window.print()">
        🖨️ Imprimer / Télécharger PDF
    </button>
    <a href="{{ route('sales.show', $sale) }}" class="btn btn-close">✕ Fermer</a>
</div>

<div class="page-wrapper">
<div class="invoice">

    {{-- En-tête --}}
    <div class="header">
        <div>
            <div class="company-name">{{ $tenant->shop_name ?? 'VOTRE BOUTIQUE' }}</div>
            <div class="company-info">
                @if($tenant->address){{ $tenant->address }}<br>@endif
                @if($tenant->city){{ $tenant->city }}@endif
                @if($tenant->phone)<br>Tél : {{ $tenant->phone }}@endif
                @if($tenant->email)<br>Email : {{ $tenant->email }}@endif
                @if($tenant->nif)<br>NIF : {{ $tenant->nif }}@endif
            </div>
        </div>
        <div class="invoice-title">
            <h2>Facture</h2>
            <div class="invoice-number">N° {{ $sale->invoice_number }}</div>
            <div class="invoice-date">
                Date : {{ $sale->created_at->format('d/m/Y') }}<br>
                Heure : {{ $sale->created_at->format('H:i') }}<br>
                Boutique : {{ $sale->branch?->name }}
            </div>
        </div>
    </div>

    <div class="band"></div>

    {{-- Client & vendeur --}}
    <div class="meta">
        <div class="meta-box">
            <h4>Facturé à</h4>
            @if($sale->customer)
                <p class="name">{{ $sale->customer->name }}</p>
                @if($sale->customer->phone)<p>Tél : {{ $sale->customer->phone }}</p>@endif
                @if($sale->customer->address)<p>{{ $sale->customer->address }}</p>@endif
                @if($sale->customer->nif)<p>NIF : {{ $sale->customer->nif }}</p>@endif
            @else
                <p class="name">Client anonyme</p>
            @endif
        </div>
        <div class="meta-box">
            <h4>Informations</h4>
            <p>Vendeur : <strong>{{ $sale->user?->name }}</strong></p>
            <p>Statut :
                @if($sale->payment_status === 'paid')
                    <strong style="color:#16a34a">✓ Payé</strong>
                @elseif($sale->payment_status === 'partial')
                    <strong style="color:#d97706">⚠ Paiement partiel</strong>
                @else
                    <strong style="color:#dc2626">✗ Crédit</strong>
                @endif
            </p>
            @if($sale->amount_paid > 0)
            <p>Montant reçu : <strong>{{ number_format($sale->amount_paid, 0, ',', ' ') }} FCFA</strong></p>
            @endif
            @if($sale->change_given > 0)
            <p>Monnaie rendue : <strong>{{ number_format($sale->change_given, 0, ',', ' ') }} FCFA</strong></p>
            @endif
        </div>
    </div>

    {{-- Tableau articles --}}
    <table class="items-table">
        <thead>
            <tr>
                <th style="width:40%">Désignation</th>
                <th style="width:8%">Qté</th>
                <th style="width:10%">Unité</th>
                <th style="width:16%">Prix HT</th>
                <th style="width:8%">TVA</th>
                <th style="width:18%">Total TTC</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sale->items as $item)
            <tr>
                <td>
                    <div style="font-weight:600">{{ $item->designation }}</div>
                    @if($item->article?->reference)
                    <div class="ref">Réf. {{ $item->article->reference }}</div>
                    @endif
                </td>
                <td style="text-align:right">{{ number_format($item->quantity, 0) }}</td>
                <td style="text-align:right">{{ $item->unit ?? $item->article?->unit }}</td>
                <td style="text-align:right">
                    {{ number_format($item->unit_price_ttc / (1 + ($item->article?->tax_rate ?? 0) / 100), 0, ',', ' ') }}
                </td>
                <td style="text-align:right">{{ $item->article?->tax_rate ?? 0 }}%</td>
                <td style="text-align:right;font-weight:600">
                    {{ number_format($item->total_ttc, 0, ',', ' ') }} FCFA
                    @if($item->discount_amount > 0)
                    <div style="font-size:10px;color:#dc2626;font-weight:400">
                        -{{ number_format($item->discount_amount, 0, ',', ' ') }} remise
                    </div>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Totaux --}}
    <div class="totals">
        <div class="totals-box">
            <div class="total-row">
                <span>Sous-total HT</span>
                <span>{{ number_format($sale->subtotal_ht, 0, ',', ' ') }} FCFA</span>
            </div>
            @if($sale->discount_amount > 0)
            <div class="total-row" style="color:#dc2626">
                <span>Remises</span>
                <span>-{{ number_format($sale->discount_amount, 0, ',', ' ') }} FCFA</span>
            </div>
            @endif
            <div class="total-row">
                <span>TVA</span>
                <span>{{ number_format($sale->tax_amount, 0, ',', ' ') }} FCFA</span>
            </div>
            <div class="total-row main">
                <span>TOTAL TTC</span>
                <span>{{ number_format($sale->total_ttc, 0, ',', ' ') }} FCFA</span>
            </div>
        </div>
    </div>

    {{-- Modes paiement --}}
    @php $methods = $sale->payment_methods ?? []; @endphp
    @if(!empty($methods))
    <div class="payment-section">
        <h4>Mode(s) de paiement</h4>
        <div class="payment-tags">
            @foreach($methods as $pm)
            @php
                $methodLabel = match($pm['method'] ?? '') {
                    'cash' => '💵 Espèces', 'mobile_money' => '📱 Mobile Money',
                    'bank_transfer' => '🏦 Virement', 'cheque' => '📝 Chèque',
                    'credit' => '📋 Crédit', default => $pm['method'] ?? ''
                };
            @endphp
            <span class="payment-tag {{ ($pm['method'] ?? '') === 'credit' ? 'credit' : '' }}">
                {{ $methodLabel }}
                @if(isset($pm['amount']))
                &mdash; {{ number_format($pm['amount'], 0, ',', ' ') }} FCFA
                @endif
            </span>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Notes --}}
    @if($sale->notes)
    <div class="notes-section">
        <strong>Notes :</strong> {{ $sale->notes }}
    </div>
    @endif

    {{-- Pied de page --}}
    <div class="footer">
        <span>Facture générée le {{ now()->format('d/m/Y à H:i') }}</span>
        <span style="text-align:center">Merci de votre confiance !</span>
        <span>{{ $tenant->shop_name ?? '' }}</span>
    </div>

</div>
</div>
</body>
</html>
