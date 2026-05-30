@extends('layouts.app')
@section('title', 'Rapport financier')
@section('page-title', 'Rapport financier')

@section('content')

{{-- Filtres --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-4">
    <form class="flex flex-wrap gap-3 items-end">
        <div>
            <label class="block text-xs text-gray-500 mb-1">Du</label>
            <input type="date" name="date_from" value="{{ $from }}"
                   class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
        </div>
        <div>
            <label class="block text-xs text-gray-500 mb-1">Au</label>
            <input type="date" name="date_to" value="{{ $to }}"
                   class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
        </div>
        @if($branches->count() > 1)
        <div>
            <label class="block text-xs text-gray-500 mb-1">Succursale</label>
            <select name="branch_id" class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                <option value="">Toutes</option>
                @foreach($branches as $b)
                <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                @endforeach
            </select>
        </div>
        @endif
        <button class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700">
            <i class="fas fa-filter mr-1"></i> Filtrer
        </button>
    </form>
</div>

{{-- KPIs principaux --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
        <p class="text-xs text-gray-400 uppercase tracking-wide">Chiffre d'affaires TTC</p>
        <p class="text-2xl font-bold text-blue-700 mt-1">{{ number_format($totals->ca_ttc, 0, ',', ' ') }}</p>
        <p class="text-xs text-gray-400 mt-1">FCFA — {{ $totals->nb_ventes }} vente{{ $totals->nb_ventes > 1 ? 's' : '' }}</p>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
        <p class="text-xs text-gray-400 uppercase tracking-wide">Coût des ventes</p>
        <p class="text-2xl font-bold text-red-600 mt-1">{{ number_format($coutVentes, 0, ',', ' ') }}</p>
        <p class="text-xs text-gray-400 mt-1">FCFA (prix achat)</p>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
        <p class="text-xs text-gray-400 uppercase tracking-wide">Marge brute</p>
        <p class="text-2xl font-bold {{ $margeBrute >= 0 ? 'text-green-600' : 'text-red-600' }} mt-1">
            {{ number_format($margeBrute, 0, ',', ' ') }}
        </p>
        <p class="text-xs text-gray-400 mt-1">FCFA — {{ number_format($tauxMarge, 1) }}% de marge</p>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
        <p class="text-xs text-gray-400 uppercase tracking-wide">Crédit en cours</p>
        <p class="text-2xl font-bold text-orange-600 mt-1">{{ number_format($creditEnCours, 0, ',', ' ') }}</p>
        <p class="text-xs text-gray-400 mt-1">FCFA non encaissé</p>
    </div>
</div>

<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
    <div class="bg-blue-50 rounded-xl px-4 py-3">
        <p class="text-xs text-blue-500">CA HT</p>
        <p class="text-lg font-bold text-blue-800">{{ number_format($totals->ca_ht, 0, ',', ' ') }} FCFA</p>
    </div>
    <div class="bg-gray-50 rounded-xl px-4 py-3">
        <p class="text-xs text-gray-500">TVA collectée</p>
        <p class="text-lg font-bold text-gray-800">{{ number_format($totals->total_tva, 0, ',', ' ') }} FCFA</p>
    </div>
    <div class="bg-yellow-50 rounded-xl px-4 py-3">
        <p class="text-xs text-yellow-600">Remises accordées</p>
        <p class="text-lg font-bold text-yellow-800">{{ number_format($totals->total_remise, 0, ',', ' ') }} FCFA</p>
    </div>
    <div class="bg-green-50 rounded-xl px-4 py-3">
        <p class="text-xs text-green-500">Panier moyen</p>
        <p class="text-lg font-bold text-green-800">
            {{ $totals->nb_ventes > 0 ? number_format($totals->ca_ttc / $totals->nb_ventes, 0, ',', ' ') : 0 }} FCFA
        </p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">

    {{-- Ventes par catégorie --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <h3 class="text-sm font-semibold text-gray-700 mb-3"><i class="fas fa-tags text-blue-400 mr-1"></i> CA par catégorie</h3>
        @forelse($byCategory as $cat)
        @php $pct = $totals->ca_ttc > 0 ? ($cat->ca / $totals->ca_ttc) * 100 : 0; @endphp
        <div class="mb-2">
            <div class="flex justify-between text-sm mb-0.5">
                <span class="text-gray-700">{{ $cat->category }}</span>
                <span class="font-semibold">{{ number_format($cat->ca, 0, ',', ' ') }} FCFA</span>
            </div>
            <div class="w-full bg-gray-100 rounded-full h-1.5">
                <div class="bg-blue-500 h-1.5 rounded-full" style="width: {{ $pct }}%"></div>
            </div>
            <p class="text-xs text-gray-400 text-right">{{ number_format($pct, 1) }}% — {{ number_format($cat->qty, 0) }} unités</p>
        </div>
        @empty
        <p class="text-sm text-gray-400 text-center py-4">Aucune donnée</p>
        @endforelse
    </div>

    {{-- Ventes par mode de paiement --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <h3 class="text-sm font-semibold text-gray-700 mb-3"><i class="fas fa-wallet text-green-400 mr-1"></i> CA par mode de paiement</h3>
        @forelse($byPayment as $pay)
        @php
            $pct = $totals->ca_ttc > 0 ? ($pay->ca / $totals->ca_ttc) * 100 : 0;
            $label = match($pay->method) {
                'cash' => 'Espèces', 'mobile_money' => 'Mobile Money',
                'bank_transfer' => 'Virement', 'cheque' => 'Chèque',
                'credit' => 'Crédit', default => $pay->method ?? 'Autre'
            };
        @endphp
        <div class="mb-2">
            <div class="flex justify-between text-sm mb-0.5">
                <span class="text-gray-700">{{ $label }}</span>
                <span class="font-semibold">{{ number_format($pay->ca, 0, ',', ' ') }} FCFA</span>
            </div>
            <div class="w-full bg-gray-100 rounded-full h-1.5">
                <div class="bg-green-500 h-1.5 rounded-full" style="width: {{ $pct }}%"></div>
            </div>
            <p class="text-xs text-gray-400 text-right">{{ number_format($pct, 1) }}% — {{ $pay->nb }} vente{{ $pay->nb > 1 ? 's' : '' }}</p>
        </div>
        @empty
        <p class="text-sm text-gray-400 text-center py-4">Aucune donnée</p>
        @endforelse
    </div>

</div>

{{-- Ventes par vendeur --}}
@if($byUser->isNotEmpty())
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 mb-4">
    <h3 class="text-sm font-semibold text-gray-700 mb-3"><i class="fas fa-user-tie text-purple-400 mr-1"></i> CA par vendeur</h3>
    <table class="w-full text-sm">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-3 py-2 text-left text-xs text-gray-500 uppercase">Vendeur</th>
                <th class="px-3 py-2 text-right text-xs text-gray-500 uppercase">Ventes</th>
                <th class="px-3 py-2 text-right text-xs text-gray-500 uppercase">CA TTC</th>
                <th class="px-3 py-2 text-right text-xs text-gray-500 uppercase">Panier moyen</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @foreach($byUser as $u)
            <tr class="hover:bg-gray-50">
                <td class="px-3 py-2 font-medium">{{ $u->user?->name ?? 'Inconnu' }}</td>
                <td class="px-3 py-2 text-right text-gray-600">{{ $u->nb }}</td>
                <td class="px-3 py-2 text-right font-semibold text-blue-700">{{ number_format($u->ca, 0, ',', ' ') }} FCFA</td>
                <td class="px-3 py-2 text-right text-gray-600">{{ number_format($u->ca / max(1,$u->nb), 0, ',', ' ') }} FCFA</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

{{-- Évolution CA par jour --}}
@if($byDay->isNotEmpty())
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
    <h3 class="text-sm font-semibold text-gray-700 mb-3"><i class="fas fa-chart-line text-blue-400 mr-1"></i> Évolution journalière</h3>
    @php $maxCa = $byDay->max('ca') ?: 1; @endphp
    <div class="space-y-1.5">
        @foreach($byDay as $day)
        @php $pct = ($day->ca / $maxCa) * 100; @endphp
        <div class="flex items-center gap-3 text-sm">
            <span class="text-gray-400 w-20 text-xs">{{ \Carbon\Carbon::parse($day->date)->format('d/m/Y') }}</span>
            <div class="flex-1 bg-gray-100 rounded-full h-5 relative">
                <div class="bg-blue-500 h-5 rounded-full" style="width: {{ $pct }}%"></div>
                <span class="absolute inset-0 flex items-center px-2 text-xs font-medium text-white">
                    {{ number_format($day->ca, 0, ',', ' ') }} FCFA
                </span>
            </div>
            <span class="text-xs text-gray-400 w-12 text-right">{{ $day->nb }} vte{{ $day->nb > 1 ? 's' : '' }}</span>
        </div>
        @endforeach
    </div>
</div>
@endif

@endsection
