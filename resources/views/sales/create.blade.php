@extends('layouts.app')

@section('title', 'Nouvelle vente')
@section('page-title', 'Nouvelle vente')
@section('page-subtitle', 'Rechercher un article par référence ou nom, puis saisir la quantité.')

@section('content')
<div class="max-w-6xl mx-auto px-4 py-6">
    <div class="mb-6 rounded-2xl bg-gradient-to-r from-slate-900 via-slate-800 to-slate-700 text-white p-6 shadow-xl">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-xs uppercase tracking-[0.24em] text-slate-300">Vente rapide</p>
                <h1 class="mt-2 text-2xl font-black">Créer une vente</h1>
                <p class="mt-2 text-sm text-slate-300 max-w-2xl">Tape la référence ou le nom de l'article, sélectionne le résultat, puis indique la quantité à vendre.</p>
            </div>
            <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center rounded-xl border border-white/20 bg-white/10 px-4 py-2 text-sm font-semibold text-white hover:bg-white/15 transition">Retour tableau de bord</a>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            <ul class="list-disc pl-5 space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid gap-6 xl:grid-cols-[1.4fr_0.9fr]">
        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm p-5">
            <form method="GET" action="{{ route('sales.create') }}" class="mb-5 flex flex-col gap-3 md:flex-row md:items-end">
                <div class="flex-1">
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Rechercher un article</label>
                    <input type="text" name="q" value="{{ $search }}" placeholder="Référence ou nom de l'article" class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200" autocomplete="off">
                </div>
                <button type="submit" class="rounded-xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white hover:bg-slate-800 transition">Rechercher</button>
            </form>

            <div id="search-empty" class="hidden mb-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-800">
                Aucun article ne correspond à cette recherche.
            </div>

            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3" id="articles-grid">
                @forelse($articles as $article)
                    <button type="button"
                        class="article-card text-left rounded-2xl border px-4 py-4 transition hover:border-slate-400 hover:shadow-md {{ $selectedArticle && $selectedArticle->id === $article->id ? 'border-slate-900 ring-2 ring-slate-200' : 'border-gray-200' }}"
                        data-article-id="{{ $article->id }}"
                        data-article-reference="{{ strtolower($article->reference) }}"
                        data-article-name="{{ strtolower($article->name) }}"
                        data-article-unit="{{ $article->unit }}"
                        data-article-price="{{ number_format((float) $article->price, 2, '.', '') }}"
                        data-article-stock="{{ number_format((float) $article->stock, 3, '.', '') }}"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-xs uppercase tracking-wide text-gray-400">{{ $article->reference }}</p>
                                <h3 class="mt-1 text-sm font-bold text-gray-900">{{ $article->name }}</h3>
                            </div>
                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold text-slate-700">{{ $article->unit }}</span>
                        </div>
                        <div class="mt-4 flex items-center justify-between text-sm">
                            <span class="text-gray-500">Stock: <strong class="text-gray-800">{{ rtrim(rtrim(number_format((float) $article->stock, 3, '.', ''), '0'), '.') }}</strong></span>
                            <span class="font-semibold text-slate-900">{{ number_format((float) $article->price, 2, ',', ' ') }} FCFA</span>
                        </div>
                    </button>
                @empty
                    <div class="col-span-full rounded-2xl border border-dashed border-gray-300 bg-gray-50 px-5 py-8 text-center text-sm text-gray-500">
                        Aucun article disponible. Crée d'abord des articles pour pouvoir saisir une vente.
                    </div>
                @endforelse
            </div>
        </div>

        <div class="space-y-6">
            <div class="rounded-2xl border border-gray-200 bg-white shadow-sm p-5">
                <h2 class="text-base font-bold text-gray-900">Détails de la sélection</h2>
                <div id="selected-empty" class="mt-4 rounded-2xl border border-dashed border-gray-300 bg-gray-50 p-4 text-sm text-gray-500 {{ $selectedArticle ? 'hidden' : '' }}">
                    Sélectionne un article à gauche pour afficher la quantité.
                </div>

                <div id="selected-panel" class="mt-4 space-y-4 {{ $selectedArticle ? '' : 'hidden' }}">
                    <div class="rounded-2xl bg-slate-50 border border-slate-100 p-4">
                        <p class="text-xs uppercase tracking-wide text-slate-500">Article choisi</p>
                        <p id="selected-name" class="mt-1 text-base font-bold text-slate-900">{{ $selectedArticle?->name }}</p>
                        <p id="selected-ref" class="text-sm text-slate-600">{{ $selectedArticle?->reference }}</p>
                    </div>

                    <form method="POST" action="{{ route('sales.store') }}" id="sale-form" class="space-y-4">
                        @csrf
                        <input type="hidden" name="article_id" id="article_id" value="{{ old('article_id', $selectedArticle?->id) }}">

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Nom client</label>
                            <input type="text" name="customer_name" value="{{ old('customer_name') }}" placeholder="Optionnel" class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Quantité</label>
                            <input type="number" name="quantity" id="quantity" min="0.001" step="0.001" value="{{ old('quantity', 1) }}" class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200">
                            <p id="quantity-help" class="mt-1 text-xs text-gray-500">La quantité s'adapte aux unités mesurées comme mètre, kg ou litre.</p>
                        </div>

                        <div class="rounded-2xl bg-slate-900 p-4 text-white">
                            <div class="flex items-center justify-between text-sm text-slate-300">
                                <span>Prix unitaire</span>
                                <strong id="unit-price">{{ $selectedArticle ? number_format((float) $selectedArticle->price, 2, ',', ' ') . ' FCFA' : '0,00 FCFA' }}</strong>
                            </div>
                            <div class="mt-2 flex items-center justify-between text-sm text-slate-300">
                                <span>Total estimé</span>
                                <strong id="sale-total" class="text-lg text-white">0,00 FCFA</strong>
                            </div>
                        </div>

                        <button type="submit" class="w-full rounded-xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white hover:bg-emerald-700 transition">Enregistrer la vente</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const cards = Array.from(document.querySelectorAll('.article-card'));
    const selectedEmpty = document.getElementById('selected-empty');
    const selectedPanel = document.getElementById('selected-panel');
    const articleIdInput = document.getElementById('article_id');
    const quantityInput = document.getElementById('quantity');
    const unitPrice = document.getElementById('unit-price');
    const saleTotal = document.getElementById('sale-total');
    const selectedName = document.getElementById('selected-name');
    const selectedRef = document.getElementById('selected-ref');
    const searchInput = document.querySelector('input[name="q"]');

    function formatCurrency(value) {
        return new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(value) + ' FCFA';
    }

    function updateTotal() {
        const activeCard = cards.find(card => card.dataset.articleId === articleIdInput.value);
        const price = activeCard ? parseFloat(activeCard.dataset.articlePrice || '0') : 0;
        const quantity = parseFloat(quantityInput?.value || '0') || 0;
        saleTotal.textContent = formatCurrency(price * quantity);
        unitPrice.textContent = formatCurrency(price);
    }

    function applyQuantityRules(unit) {
        const normalizedUnit = (unit || '').toLowerCase();
        const measuredUnits = ['mètre', 'metre', 'kg', 'kilogramme', 'litre'];
        const isMeasured = measuredUnits.includes(normalizedUnit);

        if (!quantityInput) {
            return;
        }

        quantityInput.min = isMeasured ? '0.001' : '1';
        quantityInput.step = isMeasured ? '0.001' : '1';

        const current = parseFloat(quantityInput.value || '0');
        if (Number.isNaN(current) || current <= 0) {
            quantityInput.value = isMeasured ? '0.001' : '1';
        }
    }

    function selectCard(card) {
        articleIdInput.value = card.dataset.articleId;
        selectedName.textContent = card.querySelector('h3').textContent.trim();
        selectedRef.textContent = card.dataset.articleReference.toUpperCase();
        selectedEmpty.classList.add('hidden');
        selectedPanel.classList.remove('hidden');
        applyQuantityRules(card.dataset.articleUnit);

        cards.forEach(item => {
            item.classList.remove('border-slate-900', 'ring-2', 'ring-slate-200');
            item.classList.add('border-gray-200');
        });
        card.classList.add('border-slate-900', 'ring-2', 'ring-slate-200');
        card.classList.remove('border-gray-200');

        updateTotal();

        if (quantityInput) {
            quantityInput.focus();
            quantityInput.select();
        }
    }

    function filterCards(term) {
        const normalized = (term || '').trim().toLowerCase();
        const visibleCards = [];

        cards.forEach(card => {
            const matches = normalized === ''
                || card.dataset.articleReference.includes(normalized)
                || card.dataset.articleName.includes(normalized);

            card.classList.toggle('hidden', !matches);
            if (matches) {
                visibleCards.push(card);
            }
        });

        const emptyState = document.getElementById('search-empty');
        if (emptyState) {
            emptyState.classList.toggle('hidden', visibleCards.length !== 0);
        }

        if (visibleCards.length === 1) {
            selectCard(visibleCards[0]);
        }

        return visibleCards;
    }

    cards.forEach(card => {
        card.addEventListener('click', function () {
            selectCard(card);
        });
    });

    if (quantityInput) {
        quantityInput.addEventListener('input', updateTotal);
    }

    if (searchInput) {
        filterCards(searchInput.value);
        searchInput.addEventListener('input', function () {
            filterCards(searchInput.value);
        });
        searchInput.addEventListener('keydown', function (event) {
            if (event.key !== 'Enter') {
                return;
            }

            const visibleCards = filterCards(searchInput.value);
            if (visibleCards.length > 0) {
                event.preventDefault();
                selectCard(visibleCards[0]);
            }
        });
    }

    const preselected = cards.find(card => card.dataset.articleId === articleIdInput.value);
    if (preselected) {
        selectCard(preselected);
    } else {
        updateTotal();
    }
});
</script>
@endsection
