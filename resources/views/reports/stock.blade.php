@extends('layouts.app')
@section('title', 'Rapport stock')
@section('page-title', 'Rapport de stock')

@section('content')
<div class="flex items-center gap-4 mb-4 flex-wrap">
    <form class="flex gap-2 flex-wrap">
        <select name="branch_id" class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
            <option value="">Toutes succursales</option>
            @foreach($allBranches as $b)
                <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
            @endforeach
        </select>
        <select name="status" class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
            <option value="">Tout le stock</option>
            <option value="low" {{ request('status') === 'low' ? 'selected' : '' }}>Stock bas</option>
            <option value="out" {{ request('status') === 'out' ? 'selected' : '' }}>Rupture</option>
        </select>
        <button class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700">Appliquer</button>
    </form>
</div>

<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
        <p class="text-xs text-gray-500 uppercase font-medium mb-1">Articles actifs</p>
        <p class="text-2xl font-bold text-gray-900">{{ $summary['total_articles'] }}</p>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
        <p class="text-xs text-gray-500 uppercase font-medium mb-1">Val. stock vente HT</p>
        <p class="text-2xl font-bold text-gray-900">{{ number_format($summary['stock_value'], 0, ',', ' ') }}</p>
        <p class="text-xs text-gray-400">FCFA</p>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
        <p class="text-xs text-gray-500 uppercase font-medium mb-1">Stock bas</p>
        <p class="text-2xl font-bold text-yellow-600">{{ $summary['low_stock_count'] }}</p>
        <p class="text-xs text-gray-400">articles</p>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
        <p class="text-xs text-gray-500 uppercase font-medium mb-1">Rupture</p>
        <p class="text-2xl font-bold text-red-600">{{ $summary['out_of_stock_count'] }}</p>
        <p class="text-xs text-gray-400">articles</p>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
        <h3 class="text-sm font-semibold text-gray-700">État du stock</h3>
        <span class="text-xs text-gray-400">{{ $articles->total() }} article(s)</span>
    </div>

    @if($articles->isEmpty())
        <div class="px-4 py-10 text-center text-gray-400 text-sm">
            <i class="fas fa-box-open text-3xl mb-3 block"></i>
            Aucun article actif trouvé.
        </div>
    @else
    <div class="overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Article</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Catégorie</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Stock min.</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-blue-600 uppercase">Stock</th>
                @if($branches->count() > 1)
                @foreach($branches as $b)
                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-400 uppercase">{{ $b->name }}</th>
                @endforeach
                @endif
                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Val. vente HT</th>
                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Statut</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @foreach($articles as $article)
            @php $totalStock = $article->branchStocks->sum('quantity'); @endphp
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-2.5">
                    <p class="font-medium text-gray-800">{{ Str::limit($article->designation, 35) }}</p>
                    <p class="text-xs text-gray-400 font-mono">{{ $article->reference }}</p>
                </td>
                <td class="px-4 py-2.5 text-gray-600 text-xs">{{ $article->category?->name ?? '—' }}</td>
                <td class="px-4 py-2.5 text-right text-gray-500">{{ $article->stock_min }}</td>
                <td class="px-4 py-2.5 text-right">
                    <span class="font-bold text-lg {{ $totalStock == 0 ? 'text-red-600' : ($totalStock <= $article->stock_min ? 'text-yellow-600' : 'text-blue-700') }}">
                        {{ $totalStock }}
                    </span>
                    <span class="text-xs text-gray-400 ml-0.5">{{ $article->unit }}</span>
                </td>
                @if($branches->count() > 1)
                @foreach($branches as $b)
                @php $bs = $article->branchStocks->firstWhere('branch_id', $b->id); $qty = $bs?->quantity ?? 0; @endphp
                <td class="px-4 py-2.5 text-right text-xs {{ $qty == 0 ? 'text-red-500' : ($qty <= $article->stock_min ? 'text-yellow-600' : 'text-gray-500') }}">
                    {{ $qty }}
                </td>
                @endforeach
                @endif
                <td class="px-4 py-2.5 text-right text-gray-600 text-xs">
                    {{ number_format($totalStock * $article->sale_price_ht, 0, ',', ' ') }}
                </td>
                <td class="px-4 py-2.5 text-center">
                    @if($totalStock == 0)
                        <span class="bg-red-100 text-red-700 text-xs px-2 py-0.5 rounded-full">Rupture</span>
                    @elseif($totalStock <= $article->stock_min)
                        <span class="bg-yellow-100 text-yellow-700 text-xs px-2 py-0.5 rounded-full">Stock bas</span>
                    @else
                        <span class="bg-green-100 text-green-700 text-xs px-2 py-0.5 rounded-full">OK</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    </div>
    @endif

    @if($articles->hasPages())
        <div class="px-4 py-3 border-t border-gray-100">{{ $articles->links() }}</div>
    @endif
</div>
@endsection
