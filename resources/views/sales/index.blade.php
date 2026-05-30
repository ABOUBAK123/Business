@extends('layouts.app')
@section('title', 'Historique ventes')
@section('page-title', 'Historique des ventes')

@section('content')
<div class="flex items-center justify-between mb-4 flex-wrap gap-2">
    <form class="flex gap-2 flex-wrap">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="N° facture..."
               class="border border-gray-200 rounded-lg px-3 py-2 text-sm w-40 focus:ring-2 focus:ring-blue-500">
        <input type="date" name="date_from" value="{{ request('date_from', now()->startOfMonth()->toDateString()) }}"
               class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
        <input type="date" name="date_to" value="{{ request('date_to', now()->toDateString()) }}"
               class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
        <select name="branch_id" class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
            <option value="">Toutes succursales</option>
            @foreach($branches as $b)
                <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected':'' }}>{{ $b->name }}</option>
            @endforeach
        </select>
        <button class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700">Filtrer</button>
    </form>
    <a href="{{ route('sales.create') }}" class="flex items-center gap-2 bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700">
        <i class="fas fa-plus"></i> Nouvelle vente
    </a>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">N° Facture</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Date</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Succursale</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Vendeur</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Client</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Total TTC</th>
                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Statut</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @forelse($sales as $sale)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-mono text-xs text-blue-700 font-semibold">{{ $sale->invoice_number }}</td>
                    <td class="px-4 py-3 text-gray-500">{{ $sale->created_at->format('d/m/Y H:i') }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $sale->branch?->name }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $sale->user?->name }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $sale->customer?->name ?? '—' }}</td>
                    <td class="px-4 py-3 text-right font-semibold text-gray-800">{{ number_format($sale->total_ttc, 0, ',', ' ') }}</td>
                    <td class="px-4 py-3 text-center">
                        @if($sale->payment_status === 'paid')
                            <span class="bg-green-100 text-green-700 text-xs px-2 py-0.5 rounded-full">Payé</span>
                        @elseif($sale->payment_status === 'partial')
                            <span class="bg-yellow-100 text-yellow-700 text-xs px-2 py-0.5 rounded-full">Partiel</span>
                        @else
                            <span class="bg-red-100 text-red-700 text-xs px-2 py-0.5 rounded-full">Crédit</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center justify-end gap-2">
                            <a href="{{ route('sales.show', $sale) }}" class="text-gray-400 hover:text-blue-600" title="Détail">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('sales.invoice', $sale) }}" target="_blank" class="text-gray-400 hover:text-blue-700" title="Facture PDF">
                                <i class="fas fa-file-pdf"></i>
                            </a>
                            <a href="{{ route('sales.receipt', $sale) }}" class="text-gray-400 hover:text-green-600" title="Reçu thermique">
                                <i class="fas fa-receipt"></i>
                            </a>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="px-4 py-12 text-center text-gray-400">
                    <i class="fas fa-receipt text-3xl mb-2 block"></i>Aucune vente trouvée
                </td></tr>
            @endforelse
        </tbody>
        @if($totalCount > 0)
        <tfoot class="bg-blue-50 border-t-2 border-blue-200">
            <tr>
                <td colspan="5" class="px-4 py-3 text-sm font-bold text-blue-800">
                    Total — {{ number_format($totalCount, 0, ',', ' ') }} vente{{ $totalCount > 1 ? 's' : '' }}
                </td>
                <td class="px-4 py-3 text-right text-base font-bold text-blue-900">
                    {{ number_format($totalAmount, 0, ',', ' ') }} FCFA
                </td>
                <td colspan="2"></td>
            </tr>
        </tfoot>
        @endif
    </table>
    @if($sales->hasPages())
        <div class="px-4 py-3 border-t border-gray-100">{{ $sales->links() }}</div>
    @endif
</div>
@endsection
