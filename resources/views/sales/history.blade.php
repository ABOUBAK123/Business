@extends('layouts.app')

@section('title', 'Historique ventes')
@section('page-title', 'Historique ventes')
@section('page-subtitle', 'Retrouver rapidement une vente par numéro, client, article ou référence.')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-6">
    <div class="mb-6 rounded-2xl bg-gradient-to-r from-slate-900 via-slate-800 to-slate-700 text-white p-6 shadow-xl">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-xs uppercase tracking-[0.24em] text-slate-300">Ventes</p>
                <h1 class="mt-2 text-2xl font-black">Historique des ventes</h1>
                <p class="mt-2 text-sm text-slate-300 max-w-2xl">Recherche en direct sur les ventes affichées. Tu peux aussi lancer une recherche serveur pour tout l'historique.</p>
            </div>
            <a href="{{ route('sales.create') }}" class="inline-flex items-center justify-center rounded-xl border border-white/20 bg-white/10 px-4 py-2 text-sm font-semibold text-white hover:bg-white/15 transition">Nouvelle vente</a>
        </div>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white shadow-sm p-5">
        <form method="GET" action="{{ route('sales.history') }}" class="mb-5 flex flex-col gap-3 md:flex-row md:items-end">
            <div class="flex-1">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Rechercher une vente</label>
                <input type="text" name="q" id="history-search" value="{{ $search }}" placeholder="N° vente, client, article ou référence" class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200" autocomplete="off">
            </div>
            <button type="submit" class="rounded-xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white hover:bg-slate-800 transition">Rechercher</button>
        </form>

        <div id="history-empty" class="hidden mb-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-800">
            Aucun résultat ne correspond à ce filtre sur la page courante.
        </div>

        <div class="overflow-x-auto rounded-2xl border border-gray-200">
            <table class="min-w-full text-sm" id="history-table">
                <thead class="bg-gray-50 text-gray-600">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold">N° Vente</th>
                        <th class="px-4 py-3 text-left font-semibold">Date</th>
                        <th class="px-4 py-3 text-left font-semibold">Client</th>
                        <th class="px-4 py-3 text-left font-semibold">Articles</th>
                        <th class="px-4 py-3 text-left font-semibold">Saisie par</th>
                        <th class="px-4 py-3 text-right font-semibold">Montant</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($sales as $sale)
                        @php
                            $articleSummary = $sale->items->map(function ($item) {
                                $name = $item->article->name ?? 'Article supprimé';
                                $ref = $item->article->reference ?? '-';
                                return $name . ' (' . $ref . ')';
                            })->implode(' | ');
                        @endphp
                        <tr class="history-row hover:bg-gray-50"
                            data-search="{{ strtolower($sale->sale_number . ' ' . ($sale->customer_name ?? '') . ' ' . $articleSummary . ' ' . ($sale->user->name ?? '')) }}"
                        >
                            <td class="px-4 py-3 font-semibold text-gray-900">{{ $sale->sale_number }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ optional($sale->sold_at)->format('d/m/Y H:i') ?: optional($sale->created_at)->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ $sale->customer_name ?: '-' }}</td>
                            <td class="px-4 py-3 text-gray-700">
                                <div class="space-y-1">
                                    @foreach($sale->items as $item)
                                        <div>
                                            <span class="font-medium">{{ $item->article->name ?? 'Article supprimé' }}</span>
                                            <span class="text-gray-500">({{ $item->article->reference ?? '-' }})</span>
                                            <span class="text-gray-500">x {{ rtrim(rtrim(number_format((float) $item->quantity, 3, '.', ''), '0'), '.') }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </td>
                            <td class="px-4 py-3 text-gray-700">{{ $sale->user->name ?? '-' }}</td>
                            <td class="px-4 py-3 text-right font-semibold text-slate-900">{{ number_format((float) $sale->total_amount, 2, ',', ' ') }} FCFA</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-500">Aucune vente enregistrée pour le moment.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $sales->links() }}
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('history-search');
    const rows = Array.from(document.querySelectorAll('.history-row'));
    const emptyBox = document.getElementById('history-empty');

    function filterRows(term) {
        const normalized = (term || '').trim().toLowerCase();
        let visible = 0;

        rows.forEach(function (row) {
            const matches = normalized === '' || row.dataset.search.includes(normalized);
            row.classList.toggle('hidden', !matches);
            if (matches) {
                visible += 1;
            }
        });

        if (emptyBox) {
            emptyBox.classList.toggle('hidden', visible !== 0);
        }
    }

    if (searchInput) {
        filterRows(searchInput.value);
        searchInput.addEventListener('input', function () {
            filterRows(searchInput.value);
        });
    }
});
</script>
@endsection
