@extends('layouts.app')

@section('title', 'Tableau de bord — Commissionnaire')
@section('page-title', 'Tableau de bord')

@section('content')
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl p-5 border border-gray-100 shadow-sm">
        <p class="text-xs text-gray-500 mb-1">Boutiques créées</p>
        <p class="text-2xl font-bold text-blue-700">{{ $totalShops }}</p>
    </div>
    <div class="bg-white rounded-xl p-5 border border-gray-100 shadow-sm">
        <p class="text-xs text-gray-500 mb-1">Boutiques actives</p>
        <p class="text-2xl font-bold text-green-600">{{ $activeShops }}</p>
    </div>
    <div class="bg-white rounded-xl p-5 border border-gray-100 shadow-sm">
        <p class="text-xs text-gray-500 mb-1">Commissions en attente</p>
        <p class="text-2xl font-bold text-yellow-600">{{ number_format($pendingEarned, 0, ',', ' ') }} XOF</p>
    </div>
    <div class="bg-white rounded-xl p-5 border border-gray-100 shadow-sm">
        <p class="text-xs text-gray-500 mb-1">Commissions perçues</p>
        <p class="text-2xl font-bold text-gray-800">{{ number_format($paidEarned, 0, ',', ' ') }} XOF</p>
    </div>
</div>

<div class="bg-white rounded-xl border border-gray-100 shadow-sm">
    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
        <h2 class="font-semibold text-gray-800 text-sm">Dernières boutiques</h2>
        <a href="{{ route('commissioner.shops.create') }}"
           class="bg-blue-600 text-white text-xs font-semibold px-3 py-1.5 rounded-lg hover:bg-blue-700 transition flex items-center gap-1.5">
            <i class="fas fa-plus"></i> Nouvelle boutique
        </a>
    </div>
    <div class="divide-y divide-gray-50">
        @forelse($recentShops as $shop)
        <div class="flex items-center justify-between px-5 py-3">
            <div>
                <p class="text-sm font-medium text-gray-800">{{ $shop->shop_name }}</p>
                <p class="text-xs text-gray-400">{{ $shop->plan?->name ?? 'Sans plan' }} · {{ $shop->city }}</p>
            </div>
            <span class="text-xs font-medium px-2 py-0.5 rounded-full
                @if($shop->status === 'active') bg-green-100 text-green-700
                @elseif($shop->status === 'trial') bg-yellow-100 text-yellow-700
                @elseif($shop->status === 'suspended') bg-red-100 text-red-700
                @else bg-gray-100 text-gray-600 @endif">
                {{ ucfirst($shop->status) }}
            </span>
        </div>
        @empty
        <p class="px-5 py-6 text-center text-sm text-gray-400">Aucune boutique créée pour l'instant.</p>
        @endforelse
    </div>
    @if($recentShops->count())
    <div class="px-5 py-3 border-t border-gray-50">
        <a href="{{ route('commissioner.shops') }}" class="text-xs text-blue-600 hover:underline">Voir toutes les boutiques →</a>
    </div>
    @endif
</div>
@endsection
