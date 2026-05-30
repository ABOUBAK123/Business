@extends('layouts.app')
@section('title', 'Inventaire du ' . $inventory->date->format('d/m/Y'))
@section('page-title', 'Inventaire — ' . $inventory->date->format('d/m/Y') . ' · ' . $inventory->branch?->name)

@section('content')

@if(session('success'))
<div class="bg-green-50 border border-green-200 text-green-700 rounded-lg p-3 mb-4 text-sm flex items-center gap-2">
    <i class="fas fa-check-circle"></i> {{ session('success') }}
</div>
@endif
@if(session('error'))
<div class="bg-red-50 border border-red-200 text-red-700 rounded-lg p-3 mb-4 text-sm flex items-center gap-2">
    <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
</div>
@endif

{{-- Barre de statut --}}
<div class="flex items-center justify-between mb-4 flex-wrap gap-2">
    <div class="flex items-center gap-3">
        @if($inventory->status === 'completed')
            <span class="bg-green-100 text-green-700 text-sm px-3 py-1 rounded-full font-semibold">
                <i class="fas fa-check mr-1"></i> Clôturé le {{ $inventory->completed_at?->format('d/m/Y à H:i') }}
            </span>
        @else
            <span class="bg-yellow-100 text-yellow-700 text-sm px-3 py-1 rounded-full font-semibold">
                <i class="fas fa-pen mr-1"></i> En cours de saisie
            </span>
        @endif
    </div>
    @if($inventory->status === 'draft')
    <form method="POST" action="{{ route('inventory.finalize', $inventory) }}"
          onsubmit="return confirm('Clôturer l\'inventaire ? Le stock sera ajusté selon les quantités comptées. Cette action est irréversible.')">
        @csrf
        <button type="submit"
                class="bg-green-600 text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-green-700 flex items-center gap-2">
            <i class="fas fa-lock"></i> Clôturer et ajuster le stock
        </button>
    </form>
    @endif
</div>

{{-- Compteurs --}}
@php
    $total   = $lines->total();
    $counted = $lines->filter(fn($l) => !is_null($l->counted_qty))->count();
    $withGap = $lines->filter(fn($l) => !is_null($l->counted_qty) && $l->gap != 0)->count();
@endphp
<div class="grid grid-cols-3 gap-3 mb-4">
    <div class="bg-white rounded-xl border p-3 text-center">
        <p class="text-2xl font-bold text-gray-800">{{ $total }}</p>
        <p class="text-xs text-gray-400">Articles total</p>
    </div>
    <div class="bg-white rounded-xl border p-3 text-center">
        <p class="text-2xl font-bold text-blue-600">{{ $counted }}</p>
        <p class="text-xs text-gray-400">Comptés</p>
    </div>
    <div class="bg-white rounded-xl border p-3 text-center">
        <p class="text-2xl font-bold {{ $withGap > 0 ? 'text-red-600' : 'text-green-600' }}">{{ $withGap }}</p>
        <p class="text-xs text-gray-400">Avec écart</p>
    </div>
</div>

@if($inventory->status === 'draft')
{{-- Formulaire de saisie --}}
<form method="POST" action="{{ route('inventory.save-lines', $inventory) }}" id="inventoryForm">
    @csrf
@endif

<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-100 sticky top-0">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Référence</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Désignation</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Catégorie</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Stock théorique</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Qté comptée</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Écart</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @foreach($lines as $i => $line)
            <tr class="hover:bg-gray-50 {{ !is_null($line->counted_qty) && $line->gap != 0 ? ($line->gap > 0 ? 'bg-blue-50' : 'bg-red-50') : '' }}">
                <td class="px-4 py-2 font-mono text-xs text-gray-500">{{ $line->article?->reference }}</td>
                <td class="px-4 py-2 font-medium text-gray-800 max-w-xs truncate">{{ $line->article?->designation }}</td>
                <td class="px-4 py-2 text-gray-500 text-xs">{{ $line->article?->category?->name ?? '—' }}</td>
                <td class="px-4 py-2 text-right font-semibold text-gray-700">
                    {{ number_format($line->theoretical_qty, 0) }} {{ $line->article?->unit }}
                </td>
                <td class="px-4 py-2 text-right">
                    @if($inventory->status === 'draft')
                        <input type="hidden" name="lines[{{ $i }}][id]" value="{{ $line->id }}">
                        <input type="number" name="lines[{{ $i }}][counted_qty]"
                               value="{{ $line->counted_qty ?? '' }}"
                               min="0" step="0.01" placeholder="0"
                               class="w-20 border border-gray-300 rounded-lg px-2 py-1 text-sm text-right focus:ring-2 focus:ring-blue-500 counted-input"
                               data-theoretical="{{ $line->theoretical_qty }}"
                               data-row="{{ $line->id }}"
                               oninput="updateGap(this)">
                    @else
                        <span class="font-semibold">
                            {{ is_null($line->counted_qty) ? '—' : number_format($line->counted_qty, 0) }}
                            {{ $line->article?->unit }}
                        </span>
                    @endif
                </td>
                <td class="px-4 py-2 text-right font-bold" id="gap-{{ $line->id }}">
                    @if(is_null($line->counted_qty))
                        <span class="text-gray-300">—</span>
                    @elseif($line->gap == 0)
                        <span class="text-green-600">✓ 0</span>
                    @elseif($line->gap > 0)
                        <span class="text-blue-600">+{{ number_format($line->gap, 0) }}</span>
                    @else
                        <span class="text-red-600">{{ number_format($line->gap, 0) }}</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @if($lines->hasPages())
        <div class="px-4 py-3 border-t">{{ $lines->links() }}</div>
    @endif
</div>

@if($inventory->status === 'draft')
</form>

<div class="mt-4 flex gap-3 justify-end">
    <button form="inventoryForm" type="submit"
            class="bg-blue-600 text-white px-6 py-2 rounded-lg text-sm font-semibold hover:bg-blue-700 flex items-center gap-2">
        <i class="fas fa-save"></i> Enregistrer la progression
    </button>
</div>
@endif

<script>
function updateGap(input) {
    const theoretical = parseFloat(input.dataset.theoretical) || 0;
    const counted     = parseFloat(input.value);
    const gapEl       = document.getElementById('gap-' + input.dataset.row);
    if (!gapEl) return;

    if (isNaN(counted) || input.value === '') {
        gapEl.innerHTML = '<span class="text-gray-300">—</span>';
        input.closest('tr').className = 'hover:bg-gray-50';
        return;
    }

    const gap = counted - theoretical;
    const tr  = input.closest('tr');

    if (gap === 0) {
        gapEl.innerHTML = '<span class="text-green-600">✓ 0</span>';
        tr.className = 'hover:bg-gray-50';
    } else if (gap > 0) {
        gapEl.innerHTML = `<span class="text-blue-600">+${gap.toFixed(0)}</span>`;
        tr.className = 'hover:bg-blue-50 bg-blue-50';
    } else {
        gapEl.innerHTML = `<span class="text-red-600">${gap.toFixed(0)}</span>`;
        tr.className = 'hover:bg-red-50 bg-red-50';
    }
}
</script>
@endsection
