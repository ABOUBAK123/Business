@extends('layouts.app')
@section('title', 'Gestion des stocks')
@section('page-title', 'Gestion des stocks')

@section('content')

@if(session('success'))
<div class="mb-4 bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 text-sm flex items-center gap-2">
    <i class="fas fa-check-circle"></i> {{ session('success') }}
</div>
@endif

{{-- Filtres --}}
<form class="flex flex-wrap gap-2 mb-5">
    <input type="text" name="search" value="{{ request('search') }}"
           placeholder="Rechercher un article..."
           class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 w-56">
    <select name="category_id" class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
        <option value="">Toutes catégories</option>
        @foreach($categories as $cat)
            <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
        @endforeach
    </select>
    <select name="status" class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
        <option value="">Tous les articles</option>
        <option value="low"  {{ request('status') === 'low'  ? 'selected' : '' }}>Stock bas</option>
        <option value="out"  {{ request('status') === 'out'  ? 'selected' : '' }}>Rupture</option>
    </select>
    <button class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700">
        <i class="fas fa-search mr-1"></i> Filtrer
    </button>
    @if(request()->hasAny(['search','category_id','status']))
    <a href="{{ route('stock.index') }}" class="border border-gray-200 text-gray-500 px-4 py-2 rounded-lg text-sm hover:bg-gray-50">
        <i class="fas fa-times mr-1"></i> Réinitialiser
    </a>
    @endif
</form>

@if($articles->isEmpty())
<div class="bg-white rounded-xl border border-gray-100 shadow-sm px-4 py-12 text-center text-gray-400">
    <i class="fas fa-box-open text-4xl mb-3 block"></i>
    Aucun article trouvé.
</div>
@else
<div class="space-y-3">
    @foreach($articles as $article)
    @php
        $totalStock = $article->branchStocks->sum('quantity');
        $isOut = $totalStock == 0;
        $isLow = !$isOut && $totalStock <= $article->stock_min;
    @endphp
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        {{-- En-tête article --}}
        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-50 cursor-pointer select-none"
             onclick="toggleForm('form-{{ $article->id }}', this)">
            <div class="flex items-center gap-3 min-w-0">
                <div class="flex-shrink-0 w-9 h-9 bg-blue-50 rounded-lg flex items-center justify-center">
                    <i class="fas fa-box text-blue-500 text-sm"></i>
                </div>
                <div class="min-w-0">
                    <p class="font-semibold text-gray-800 text-sm truncate">{{ $article->designation }}</p>
                    <p class="text-xs text-gray-400 font-mono">{{ $article->reference }}
                        @if($article->marque) · {{ $article->marque }}@endif
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-4 flex-shrink-0">
                {{-- Stock par succursale (seulement si plusieurs) --}}
                @if($branches->count() > 1)
                <div class="hidden sm:flex items-center gap-2">
                    @foreach($article->branchStocks as $bs)
                    <div class="text-center">
                        <p class="text-xs text-gray-400 leading-none mb-0.5">{{ $bs->branch?->name ?? '—' }}</p>
                        <span class="font-bold text-sm {{ $bs->quantity == 0 ? 'text-red-600' : ($bs->quantity <= $article->stock_min ? 'text-yellow-600' : 'text-gray-700') }}">
                            {{ $bs->quantity }}
                        </span>
                    </div>
                    @endforeach
                </div>
                @endif
                {{-- Badge total --}}
                <div class="text-center">
                    <p class="text-xs text-gray-400 leading-none mb-0.5">Total</p>
                    <span class="font-bold text-base {{ $isOut ? 'text-red-600' : ($isLow ? 'text-yellow-600' : 'text-blue-700') }}">
                        {{ $totalStock }} <span class="text-xs font-normal">{{ $article->unit }}</span>
                    </span>
                </div>
                {{-- Statut --}}
                @if($isOut)
                    <span class="hidden sm:inline bg-red-100 text-red-700 text-xs px-2 py-0.5 rounded-full">Rupture</span>
                @elseif($isLow)
                    <span class="hidden sm:inline bg-yellow-100 text-yellow-700 text-xs px-2 py-0.5 rounded-full">Stock bas</span>
                @else
                    <span class="hidden sm:inline bg-green-100 text-green-700 text-xs px-2 py-0.5 rounded-full">OK</span>
                @endif
                <i class="fas fa-chevron-down text-gray-400 text-xs transition-transform" id="icon-{{ $article->id }}"></i>
            </div>
        </div>

        {{-- Formulaire approvisionnement (masqué par défaut) --}}
        <div id="form-{{ $article->id }}" class="hidden px-4 py-4 bg-blue-50/40">
            <form method="POST" action="{{ route('stock.replenish', $article) }}" class="flex flex-wrap items-end gap-3">
                @csrf
                @if($branches->count() === 1)
                    <input type="hidden" name="branch_id" value="{{ $branches->first()->id }}">
                    @php $currentQty = $article->branchStocks->firstWhere('branch_id', $branches->first()->id)?->quantity ?? 0; @endphp
                    <div>
                        <p class="text-xs font-medium text-gray-600 mb-1">Boutique</p>
                        <p class="text-sm font-semibold text-gray-700 px-3 py-2 bg-white border border-gray-200 rounded-lg">
                            {{ $branches->first()->name }}
                            <span class="text-gray-400 font-normal">— stock actuel : {{ $currentQty }} {{ $article->unit }}</span>
                        </p>
                    </div>
                @else
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Succursale *</label>
                    <select name="branch_id" required
                            class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 bg-white">
                        @foreach($branches as $branch)
                        @php $currentQty = $article->branchStocks->firstWhere('branch_id', $branch->id)?->quantity ?? 0; @endphp
                        <option value="{{ $branch->id }}">{{ $branch->name }} (actuellement : {{ $currentQty }} {{ $article->unit }})</option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Quantité à ajouter *</label>
                    <input type="number" name="quantity" min="1" required placeholder="Ex : 50"
                           class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 bg-white w-32">
                </div>
                <div class="flex-1 min-w-48">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Note (facultatif)</label>
                    <input type="text" name="notes" placeholder="Ex : Livraison fournisseur, Transfert…"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 bg-white">
                </div>
                <button type="submit"
                        class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-blue-700 flex items-center gap-2 whitespace-nowrap">
                    <i class="fas fa-plus"></i> Approvisionner
                </button>
            </form>
        </div>
    </div>
    @endforeach
</div>

<div class="mt-4">{{ $articles->links() }}</div>
@endif

@endsection

@push('scripts')
<script>
function toggleForm(formId, header) {
    const form = document.getElementById(formId);
    const articleId = formId.replace('form-', '');
    const icon = document.getElementById('icon-' + articleId);
    const isHidden = form.classList.contains('hidden');
    form.classList.toggle('hidden', !isHidden);
    icon.classList.toggle('rotate-180', isHidden);
}
</script>
@endpush
