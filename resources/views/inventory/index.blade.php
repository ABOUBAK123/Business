@extends('layouts.app')
@section('title', 'Inventaires physiques')
@section('page-title', 'Inventaires physiques')

@section('content')

@if(session('success'))
<div class="bg-green-50 border border-green-200 text-green-700 rounded-lg p-3 mb-4 text-sm flex items-center gap-2">
    <i class="fas fa-check-circle"></i> {{ session('success') }}
</div>
@endif

<div class="flex items-center justify-between mb-4">
    <p class="text-sm text-gray-500">Arrêtés de stock — comparaison théorique vs réel</p>
    <a href="{{ route('inventory.create') }}"
       class="flex items-center gap-2 bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700">
        <i class="fas fa-clipboard-list"></i> Nouvel inventaire
    </a>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Date</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Boutique</th>
                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Articles</th>
                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Statut</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Par</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @forelse($inventories as $inv)
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3 font-medium text-gray-800">{{ $inv->date->format('d/m/Y') }}</td>
                <td class="px-4 py-3 text-gray-600">{{ $inv->branch?->name }}</td>
                <td class="px-4 py-3 text-center text-gray-600">{{ $inv->lines_count }}</td>
                <td class="px-4 py-3 text-center">
                    @if($inv->status === 'completed')
                        <span class="bg-green-100 text-green-700 text-xs px-2 py-0.5 rounded-full font-medium">
                            <i class="fas fa-check mr-1"></i>Clôturé
                        </span>
                    @else
                        <span class="bg-yellow-100 text-yellow-700 text-xs px-2 py-0.5 rounded-full font-medium">
                            <i class="fas fa-pen mr-1"></i>En cours
                        </span>
                    @endif
                </td>
                <td class="px-4 py-3 text-gray-500 text-xs">{{ $inv->user?->name }}</td>
                <td class="px-4 py-3 text-right">
                    <a href="{{ route('inventory.show', $inv) }}"
                       class="text-blue-600 hover:underline text-xs font-medium">
                        {{ $inv->status === 'draft' ? 'Continuer' : 'Voir' }}
                    </a>
                </td>
            </tr>
            @empty
            <tr><td colspan="6" class="px-4 py-12 text-center text-gray-400">
                <i class="fas fa-clipboard-list text-3xl mb-2 block"></i>Aucun inventaire
            </td></tr>
            @endforelse
        </tbody>
    </table>
    @if($inventories->hasPages())
        <div class="px-4 py-3 border-t">{{ $inventories->links() }}</div>
    @endif
</div>
@endsection
