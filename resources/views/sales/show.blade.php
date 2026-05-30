@extends('layouts.app')
@section('title', 'Vente ' . $sale->invoice_number)
@section('page-title', 'Détail de la vente')

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <div class="lg:col-span-2 space-y-4">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <p class="text-xs text-gray-400">Numéro de facture</p>
                    <h2 class="text-xl font-bold font-mono text-blue-700">{{ $sale->invoice_number }}</h2>
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('sales.invoice', $sale) }}" target="_blank"
                       class="flex items-center gap-1 bg-blue-600 text-white px-3 py-1.5 rounded-lg text-sm hover:bg-blue-700">
                        <i class="fas fa-file-pdf"></i> Facture PDF
                    </a>
                    <a href="{{ route('sales.receipt', $sale) }}" target="_blank"
                       class="flex items-center gap-1 border border-gray-200 text-gray-600 px-3 py-1.5 rounded-lg text-sm hover:bg-gray-50">
                        <i class="fas fa-print"></i> Reçu
                    </a>
                </div>
            </div>

            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 text-sm">
                <div>
                    <p class="text-xs text-gray-400">Date</p>
                    <p class="font-medium">{{ $sale->created_at->format('d/m/Y H:i') }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400">Succursale</p>
                    <p class="font-medium">{{ $sale->branch?->name }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400">Caissier</p>
                    <p class="font-medium">{{ $sale->user?->name }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400">Client</p>
                    <p class="font-medium">{{ $sale->customer?->name ?? 'Anonyme' }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-100">
                <h3 class="text-sm font-semibold text-gray-700">Articles vendus</h3>
            </div>
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-100">
                    <tr>
                        <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500">Article</th>
                        <th class="px-4 py-2.5 text-right text-xs font-semibold text-gray-500">Prix unit.</th>
                        <th class="px-4 py-2.5 text-right text-xs font-semibold text-gray-500">Qté</th>
                        <th class="px-4 py-2.5 text-right text-xs font-semibold text-gray-500">Remise</th>
                        <th class="px-4 py-2.5 text-right text-xs font-semibold text-gray-500">Total TTC</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($sale->items as $item)
                    <tr>
                        <td class="px-4 py-3">
                            <p class="font-medium text-gray-800">{{ $item->designation }}</p>
                        </td>
                        <td class="px-4 py-3 text-right text-gray-700">{{ number_format($item->unit_price_ttc, 0, ',', ' ') }}</td>
                        <td class="px-4 py-3 text-right font-semibold">{{ $item->quantity }}</td>
                        <td class="px-4 py-3 text-right text-red-500">
                            {{ $item->discount_amount > 0 ? '-' . number_format($item->discount_amount, 0, ',', ' ') : '—' }}
                        </td>
                        <td class="px-4 py-3 text-right font-semibold text-gray-800">{{ number_format($item->total_ttc, 0, ',', ' ') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="space-y-4">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <h3 class="text-sm font-semibold text-gray-700 mb-3">Récapitulatif</h3>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between text-gray-600">
                    <span>Sous-total HT</span>
                    <span>{{ number_format($sale->subtotal_ht, 0, ',', ' ') }} FCFA</span>
                </div>
                <div class="flex justify-between text-gray-600">
                    <span>TVA (18%)</span>
                    <span>{{ number_format($sale->tax_amount, 0, ',', ' ') }} FCFA</span>
                </div>
                @if($sale->discount_amount > 0)
                <div class="flex justify-between text-red-500">
                    <span>Remise</span>
                    <span>-{{ number_format($sale->discount_amount, 0, ',', ' ') }} FCFA</span>
                </div>
                @endif
                <div class="flex justify-between font-bold text-lg text-gray-900 border-t border-gray-100 pt-2 mt-2">
                    <span>TOTAL TTC</span>
                    <span>{{ number_format($sale->total_ttc, 0, ',', ' ') }} FCFA</span>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <h3 class="text-sm font-semibold text-gray-700 mb-3">Paiement</h3>
            <div class="space-y-2 text-sm">
                @foreach($sale->payment_methods ?? [] as $pm)
                <div class="flex justify-between text-gray-600">
                    <span class="capitalize">{{ str_replace('_', ' ', $pm['method'] ?? '') }}</span>
                    <span class="font-medium">{{ number_format($pm['amount'] ?? 0, 0, ',', ' ') }} FCFA</span>
                </div>
                @endforeach
                @if($sale->change_given > 0)
                <div class="flex justify-between font-semibold text-green-600 border-t border-gray-100 pt-2">
                    <span>Monnaie rendue</span>
                    <span>{{ number_format($sale->change_given, 0, ',', ' ') }} FCFA</span>
                </div>
                @endif
            </div>
            <div class="mt-3 pt-3 border-t border-gray-100">
                @if($sale->payment_status === 'paid')
                    <span class="bg-green-100 text-green-700 text-xs px-2 py-1 rounded-full font-medium">Payé intégralement</span>
                @elseif($sale->payment_status === 'partial')
                    <span class="bg-yellow-100 text-yellow-700 text-xs px-2 py-1 rounded-full font-medium">Paiement partiel</span>
                @else
                    <span class="bg-red-100 text-red-700 text-xs px-2 py-1 rounded-full font-medium">Crédit client</span>
                @endif
            </div>
        </div>

        @if($sale->notes)
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <h3 class="text-sm font-semibold text-gray-700 mb-2">Notes</h3>
            <p class="text-sm text-gray-600">{{ $sale->notes }}</p>
        </div>
        @endif
    </div>
</div>
@endsection
