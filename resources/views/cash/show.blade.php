@extends('layouts.app')
@section('title', 'Clôture du ' . $cash->date->format('d/m/Y'))
@section('page-title', 'Clôture — ' . $cash->date->format('d/m/Y'))

@section('content')
@php
    $methodLabels = [
        'cash' => 'Espèces', 'mobile_money' => 'Mobile Money',
        'bank_transfer' => 'Virement', 'cheque' => 'Chèque', 'credit' => 'Crédit',
    ];
@endphp

<div class="max-w-2xl mx-auto space-y-4">

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex justify-between items-start mb-6">
            <div>
                <h2 class="text-xl font-bold text-gray-800">Clôture du {{ $cash->date->format('d/m/Y') }}</h2>
                <p class="text-sm text-gray-500">{{ $cash->branch?->name }} — par {{ $cash->user?->name }}</p>
            </div>
            <span class="{{ $cash->cash_gap == 0 ? 'bg-green-100 text-green-700' : ($cash->cash_gap > 0 ? 'bg-blue-100 text-blue-700' : 'bg-red-100 text-red-700') }} text-xs font-semibold px-3 py-1 rounded-full">
                {{ $cash->cash_gap >= 0 ? '+' : '' }}{{ number_format($cash->cash_gap, 0, ',', ' ') }} FCFA écart
            </span>
        </div>

        <div class="grid grid-cols-2 gap-4 mb-6">
            <div class="bg-blue-50 rounded-xl p-4 text-center">
                <p class="text-xs text-blue-500 mb-1">CA total TTC</p>
                <p class="text-2xl font-bold text-blue-800">{{ number_format($cash->total_sales, 0, ',', ' ') }}</p>
                <p class="text-xs text-blue-400">{{ $cash->sales_count }} vente{{ $cash->sales_count > 1 ? 's' : '' }}</p>
            </div>
            <div class="{{ $cash->cash_gap == 0 ? 'bg-green-50' : ($cash->cash_gap > 0 ? 'bg-blue-50' : 'bg-red-50') }} rounded-xl p-4 text-center">
                <p class="text-xs {{ $cash->cash_gap == 0 ? 'text-green-500' : ($cash->cash_gap > 0 ? 'text-blue-500' : 'text-red-500') }} mb-1">Écart de caisse</p>
                <p class="text-2xl font-bold {{ $cash->cash_gap == 0 ? 'text-green-700' : ($cash->cash_gap > 0 ? 'text-blue-700' : 'text-red-700') }}">
                    {{ $cash->cash_gap >= 0 ? '+' : '' }}{{ number_format($cash->cash_gap, 0, ',', ' ') }}
                </p>
                <p class="text-xs text-gray-400">FCFA</p>
            </div>
        </div>

        <h3 class="text-sm font-semibold text-gray-700 mb-3">Détail des encaissements</h3>
        <div class="space-y-2 mb-6">
            @foreach(($cash->payment_summary ?? []) as $method => $amount)
            <div class="flex justify-between text-sm">
                <span class="text-gray-600">{{ $methodLabels[$method] ?? $method }}</span>
                <span class="font-semibold">{{ number_format($amount, 0, ',', ' ') }} FCFA</span>
            </div>
            @endforeach
        </div>

        <h3 class="text-sm font-semibold text-gray-700 mb-3">Caisse espèces</h3>
        <div class="bg-gray-50 rounded-lg p-4 space-y-2 text-sm">
            <div class="flex justify-between">
                <span class="text-gray-600">Fond de caisse (ouverture)</span>
                <span class="font-medium">{{ number_format($cash->opening_cash, 0, ',', ' ') }} FCFA</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-600">+ Ventes espèces</span>
                <span class="font-medium">{{ number_format(($cash->payment_summary['cash'] ?? 0), 0, ',', ' ') }} FCFA</span>
            </div>
            <div class="flex justify-between border-t pt-2">
                <span class="text-gray-700 font-medium">= Espèces théoriques</span>
                <span class="font-bold">{{ number_format($cash->theoretical_cash, 0, ',', ' ') }} FCFA</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-700 font-medium">Espèces comptées</span>
                <span class="font-bold">{{ number_format($cash->closing_cash, 0, ',', ' ') }} FCFA</span>
            </div>
            <div class="flex justify-between border-t pt-2 font-bold text-base">
                <span class="{{ $cash->cash_gap == 0 ? 'text-green-700' : ($cash->cash_gap > 0 ? 'text-blue-700' : 'text-red-700') }}">
                    Écart {{ $cash->cash_gap >= 0 ? 'excédent' : 'manquant' }}
                </span>
                <span class="{{ $cash->cash_gap == 0 ? 'text-green-700' : ($cash->cash_gap > 0 ? 'text-blue-700' : 'text-red-700') }}">
                    {{ $cash->cash_gap >= 0 ? '+' : '' }}{{ number_format($cash->cash_gap, 0, ',', ' ') }} FCFA
                </span>
            </div>
        </div>

        @if($cash->notes)
        <div class="mt-4 bg-yellow-50 rounded-lg p-3">
            <p class="text-xs text-yellow-600 font-medium mb-1">Notes</p>
            <p class="text-sm text-gray-700">{{ $cash->notes }}</p>
        </div>
        @endif

        <div class="mt-4 text-xs text-gray-400 text-right">
            Enregistré le {{ $cash->created_at->format('d/m/Y à H:i') }}
        </div>
    </div>

    <div class="flex gap-3">
        <a href="{{ route('cash.index') }}" class="flex-1 text-center border border-gray-200 text-gray-600 px-4 py-2 rounded-lg text-sm hover:bg-gray-50">
            <i class="fas fa-arrow-left mr-1"></i> Retour
        </a>
        <button onclick="window.print()" class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700">
            <i class="fas fa-print mr-1"></i> Imprimer
        </button>
    </div>
</div>
@endsection
