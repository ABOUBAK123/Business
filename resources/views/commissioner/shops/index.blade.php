@extends('layouts.app')

@section('title', 'Mes boutiques')
@section('page-title', 'Mes boutiques')

@section('content')
<div class="flex justify-end mb-4">
    <a href="{{ route('commissioner.shops.create') }}"
       class="bg-blue-600 text-white text-sm font-semibold px-4 py-2 rounded-lg hover:bg-blue-700 transition flex items-center gap-2">
        <i class="fas fa-plus"></i> Nouvelle boutique
    </a>
</div>

<div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Boutique</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Plan</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Ville</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Statut</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Créée le</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @forelse($shops as $shop)
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3">
                    <p class="font-medium text-gray-800">{{ $shop->shop_name }}</p>
                    <p class="text-xs text-gray-400">{{ $shop->email }}</p>
                </td>
                <td class="px-4 py-3 text-gray-600">{{ $shop->plan?->name ?? '—' }}</td>
                <td class="px-4 py-3 text-gray-600">{{ $shop->city ?? '—' }}</td>
                <td class="px-4 py-3">
                    <span class="text-xs font-medium px-2 py-0.5 rounded-full
                        @if($shop->status === 'active') bg-green-100 text-green-700
                        @elseif($shop->status === 'trial') bg-yellow-100 text-yellow-700
                        @elseif($shop->status === 'suspended') bg-red-100 text-red-700
                        @elseif($shop->status === 'grace') bg-orange-100 text-orange-700
                        @else bg-gray-100 text-gray-600 @endif">
                        {{ ucfirst($shop->status) }}
                    </span>
                </td>
                <td class="px-4 py-3 text-gray-500 text-xs">{{ $shop->created_at->format('d/m/Y') }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="px-4 py-8 text-center text-gray-400">Aucune boutique créée.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    @if($shops->hasPages())
    <div class="px-4 py-3 border-t border-gray-100">
        {{ $shops->links() }}
    </div>
    @endif
</div>
@endsection
