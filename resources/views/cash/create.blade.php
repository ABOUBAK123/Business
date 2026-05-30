@extends('layouts.app')
@section('title', 'Clôture de caisse')
@section('page-title', 'Nouvelle clôture de caisse')

@section('content')

@php
    $methodLabels = [
        'cash' => 'Espèces', 'mobile_money' => 'Mobile Money',
        'bank_transfer' => 'Virement', 'cheque' => 'Chèque', 'credit' => 'Crédit',
    ];
@endphp

{{-- Sélecteur date/boutique --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 mb-4">
    <form class="flex flex-wrap gap-3 items-end" method="GET" action="{{ route('cash.create') }}">
        <div>
            <label class="block text-xs text-gray-500 mb-1">Boutique</label>
            <select name="branch_id" class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                @foreach($branches as $b)
                <option value="{{ $b->id }}" {{ $b->id == $branchId ? 'selected' : '' }}>{{ $b->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs text-gray-500 mb-1">Date</label>
            <input type="date" name="date" value="{{ $date }}" max="{{ today()->toDateString() }}"
                   class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
        </div>
        <button class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700">
            <i class="fas fa-sync mr-1"></i> Charger les données
        </button>
    </form>
</div>

@if($existing)
<div class="bg-amber-50 border border-amber-200 text-amber-800 rounded-lg p-3 mb-4 text-sm flex items-center gap-2">
    <i class="fas fa-exclamation-triangle"></i>
    Une clôture existe déjà pour cette date et cette boutique. La soumettre à nouveau la mettra à jour.
</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

    {{-- Résumé des ventes du jour (calculé) --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <h3 class="text-sm font-semibold text-gray-700 mb-4 flex items-center gap-2">
            <i class="fas fa-chart-pie text-blue-500"></i> Résumé des ventes — {{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}
        </h3>

        <div class="space-y-3">
            <div class="flex justify-between text-sm">
                <span class="text-gray-500">Nombre de ventes</span>
                <span class="font-bold text-gray-800">{{ $salesSummary['sales_count'] }}</span>
            </div>
            <div class="flex justify-between text-sm border-b pb-3">
                <span class="text-gray-500">CA total TTC</span>
                <span class="font-bold text-blue-700 text-base">{{ number_format($salesSummary['total_sales'], 0, ',', ' ') }} FCFA</span>
            </div>

            @foreach($salesSummary['by_method'] as $method => $amount)
            <div class="flex justify-between text-sm">
                <span class="text-gray-600 flex items-center gap-2">
                    <i class="fas {{ $method === 'cash' ? 'fa-money-bill-wave' : ($method === 'mobile_money' ? 'fa-mobile-alt' : 'fa-university') }} text-gray-400 w-4"></i>
                    {{ $methodLabels[$method] ?? $method }}
                </span>
                <span class="font-semibold">{{ number_format($amount, 0, ',', ' ') }} FCFA</span>
            </div>
            @endforeach

            @if($salesSummary['sales_count'] === 0)
            <p class="text-center text-gray-400 text-sm py-4">Aucune vente ce jour</p>
            @endif
        </div>

        <div class="mt-4 bg-blue-50 rounded-lg p-3">
            <div class="flex justify-between text-sm">
                <span class="text-blue-700 font-medium">Espèces théoriques</span>
                <span class="font-bold text-blue-800 text-base">{{ number_format($salesSummary['theoretical_cash'], 0, ',', ' ') }} FCFA</span>
            </div>
            <p class="text-xs text-blue-500 mt-1">= fond de caisse ouverture + ventes espèces</p>
        </div>
    </div>

    {{-- Formulaire clôture --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <h3 class="text-sm font-semibold text-gray-700 mb-4 flex items-center gap-2">
            <i class="fas fa-lock text-gray-500"></i> Saisie de clôture
        </h3>

        <form method="POST" action="{{ route('cash.store') }}" id="closeForm">
            @csrf
            <input type="hidden" name="branch_id" value="{{ $branchId }}">
            <input type="hidden" name="date" value="{{ $date }}">

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fond de caisse (ouverture) *</label>
                    <input type="number" name="opening_cash" step="1" min="0"
                           value="{{ old('opening_cash', $existing?->opening_cash ?? 0) }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500"
                           oninput="calcEcart()" required>
                    <p class="text-xs text-gray-400 mt-1">Montant espèces en caisse en début de journée</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Espèces comptées (fermeture) *</label>
                    <input type="number" name="closing_cash" step="1" min="0"
                           value="{{ old('closing_cash', $existing?->closing_cash ?? '') }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500"
                           oninput="calcEcart()" required>
                    <p class="text-xs text-gray-400 mt-1">Montant espèces physiquement compté en caisse</p>
                </div>

                {{-- Écart calculé en temps réel --}}
                <div class="bg-gray-50 rounded-lg p-3">
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-gray-600">Espèces théoriques</span>
                        <span class="font-semibold" id="theoretical">{{ number_format($salesSummary['theoretical_cash'], 0, ',', ' ') }} FCFA</span>
                    </div>
                    <div class="flex justify-between text-sm font-bold">
                        <span>Écart de caisse</span>
                        <span id="ecart" class="text-gray-400">—</span>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                    <textarea name="notes" rows="3" placeholder="Observations, anomalies..."
                              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">{{ old('notes', $existing?->notes) }}</textarea>
                </div>

                <button type="submit"
                        class="w-full bg-blue-700 text-white py-2.5 rounded-lg font-semibold hover:bg-blue-800 flex items-center justify-center gap-2">
                    <i class="fas fa-lock"></i> Enregistrer la clôture
                </button>
            </div>
        </form>
    </div>
</div>

<script>
const theoretical = {{ $salesSummary['theoretical_cash'] }};

function calcEcart() {
    const opening = parseFloat(document.querySelector('[name=opening_cash]').value) || 0;
    const closing  = parseFloat(document.querySelector('[name=closing_cash]').value) || 0;
    const theo     = theoretical + opening;
    const ecart    = closing - theo;
    const el       = document.getElementById('ecart');
    document.getElementById('theoretical').textContent =
        new Intl.NumberFormat('fr-FR').format(theo) + ' FCFA';

    if (isNaN(ecart) || document.querySelector('[name=closing_cash]').value === '') {
        el.textContent = '—'; el.className = 'text-gray-400'; return;
    }
    el.textContent = (ecart >= 0 ? '+' : '') + new Intl.NumberFormat('fr-FR').format(ecart) + ' FCFA';
    el.className   = ecart === 0 ? 'text-green-600 font-bold' : (ecart > 0 ? 'text-blue-600 font-bold' : 'text-red-600 font-bold');
}
</script>
@endsection
