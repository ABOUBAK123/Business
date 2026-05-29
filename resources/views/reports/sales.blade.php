@extends('layouts.app')
@section('title', 'Rapport ventes')
@section('page-title', 'Rapport des ventes')

@section('content')
<div class="flex items-center gap-4 mb-4 flex-wrap">
    <form class="flex gap-2 flex-wrap">
        <input type="date" name="date_from" value="{{ request('date_from', now()->startOfMonth()->toDateString()) }}"
               class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
        <input type="date" name="date_to" value="{{ request('date_to', now()->toDateString()) }}"
               class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
        <select name="branch_id" class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
            <option value="">Toutes succursales</option>
            @foreach($branches as $b)
                <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
            @endforeach
        </select>
        <button class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700">Appliquer</button>
    </form>
</div>

<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
        <p class="text-xs text-gray-500 uppercase font-medium mb-1">CA Total TTC</p>
        <p class="text-2xl font-bold text-gray-900">{{ number_format($summary['total_revenue'], 0, ',', ' ') }}</p>
        <p class="text-xs text-gray-400">FCFA</p>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
        <p class="text-xs text-gray-500 uppercase font-medium mb-1">Nb. transactions</p>
        <p class="text-2xl font-bold text-gray-900">{{ number_format($summary['transaction_count'], 0) }}</p>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
        <p class="text-xs text-gray-500 uppercase font-medium mb-1">Panier moyen</p>
        <p class="text-2xl font-bold text-gray-900">{{ number_format($summary['avg_basket'], 0, ',', ' ') }}</p>
        <p class="text-xs text-gray-400">FCFA</p>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
        <p class="text-xs text-gray-500 uppercase font-medium mb-1">TVA collectée</p>
        <p class="text-2xl font-bold text-gray-900">{{ number_format($summary['total_tax'], 0, ',', ' ') }}</p>
        <p class="text-xs text-gray-400">FCFA</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <h3 class="text-sm font-semibold text-gray-700 mb-4">Ventes par jour</h3>
        <div class="space-y-2">
            @foreach($salesByDay as $day)
            <div class="flex items-center gap-3">
                <span class="text-xs text-gray-500 w-20">{{ \Carbon\Carbon::parse($day->date)->format('d/m') }}</span>
                <div class="flex-1 bg-gray-100 rounded-full h-2">
                    <div class="bg-blue-500 h-2 rounded-full"
                         style="width: {{ $summary['max_day'] > 0 ? ($day->total / $summary['max_day'] * 100) : 0 }}%"></div>
                </div>
                <span class="text-xs font-medium text-gray-700 w-24 text-right">{{ number_format($day->total, 0, ',', ' ') }}</span>
            </div>
            @endforeach
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <h3 class="text-sm font-semibold text-gray-700 mb-4">Par mode de paiement</h3>
        <div class="space-y-3">
            @foreach($byPaymentMethod as $pm)
            <div class="flex items-center justify-between">
                <span class="text-sm text-gray-600 capitalize">{{ str_replace('_', ' ', $pm->method ?? 'Inconnu') }}</span>
                <div class="flex items-center gap-3">
                    <div class="w-24 bg-gray-100 rounded-full h-2">
                        <div class="bg-green-500 h-2 rounded-full"
                             style="width: {{ $summary['total_revenue'] > 0 ? ($pm->total / $summary['total_revenue'] * 100) : 0 }}%"></div>
                    </div>
                    <span class="text-sm font-medium text-gray-800 w-24 text-right">{{ number_format($pm->total, 0, ',', ' ') }}</span>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
        <h3 class="text-sm font-semibold text-gray-700">Détail des ventes</h3>
        <span class="text-xs text-gray-400">{{ $sales->total() }} enregistrements</span>
    </div>
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">N° Facture</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Date</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Succursale</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Caissier</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">HT</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">TVA</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">TTC</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @foreach($sales as $sale)
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-2.5 font-mono text-xs text-blue-700">{{ $sale->invoice_number }}</td>
                <td class="px-4 py-2.5 text-gray-500">{{ $sale->created_at->format('d/m/Y H:i') }}</td>
                <td class="px-4 py-2.5 text-gray-600">{{ $sale->branch?->name }}</td>
                <td class="px-4 py-2.5 text-gray-600">{{ $sale->user?->name }}</td>
                <td class="px-4 py-2.5 text-right">{{ number_format($sale->subtotal_ht, 0, ',', ' ') }}</td>
                <td class="px-4 py-2.5 text-right">{{ number_format($sale->tax_amount, 0, ',', ' ') }}</td>
                <td class="px-4 py-2.5 text-right font-semibold">{{ number_format($sale->total_ttc, 0, ',', ' ') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @if($sales->hasPages())
        <div class="px-4 py-3 border-t border-gray-100">{{ $sales->links() }}</div>
    @endif
</div>
@endsection
