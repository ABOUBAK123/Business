@extends('layouts.app')
@section('title', isset($article->id) ? 'Modifier article' : 'Nouvel article')
@section('page-title', isset($article->id) ? 'Modifier : '.$article->designation : 'Nouvel article')

@section('content')
<form method="POST"
      action="{{ isset($article->id) ? route('articles.update', $article) : route('articles.store') }}"
      enctype="multipart/form-data">
    @csrf
    @if(isset($article->id)) @method('PUT') @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        {{-- Main info --}}
        <div class="lg:col-span-2 space-y-4">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                <h2 class="text-sm font-semibold text-gray-700 mb-4">Informations générales</h2>
                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Désignation *</label>
                        <input type="text" name="designation" value="{{ old('designation', $article->designation ?? '') }}"
                               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Marque</label>
                        <input type="text" name="marque" value="{{ old('marque', $article->marque ?? '') }}"
                               placeholder="Ex : Bosch, Stanley, Legrand…"
                               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Code article</label>
                        <div class="flex gap-2">
                            <input type="text" name="reference" id="articleCode"
                                   value="{{ old('reference', $article->reference ?? $generatedCode ?? '') }}"
                                   class="flex-1 border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono tracking-wide">
                            @if(!isset($article->id))
                            <button type="button" onclick="regenCode()"
                                    title="Générer un nouveau code"
                                    class="flex-shrink-0 border border-gray-200 text-gray-500 hover:text-blue-600 hover:border-blue-300 px-3 py-2 rounded-lg transition">
                                <i class="fas fa-sync-alt text-sm"></i>
                            </button>
                            @endif
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Unité *</label>
                        <select name="unit" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @foreach(['pièce','mètre','kg','litre','boite','lot','paquet','rouleau'] as $u)
                                <option value="{{ $u }}" {{ old('unit', $article->unit ?? 'pièce') === $u ? 'selected' : '' }}>{{ $u }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Catégorie</label>
                        <select name="category_id" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Aucune catégorie</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" {{ old('category_id', $article->category_id ?? '') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Fournisseur</label>
                        <select name="supplier_id" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Aucun</option>
                            @foreach($suppliers as $s)
                                <option value="{{ $s->id }}" {{ old('supplier_id', $article->supplier_id ?? '') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-span-2">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Description courte</label>
                        <textarea name="short_description" rows="2"
                                  class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('short_description', $article->short_description ?? '') }}</textarea>
                    </div>
                </div>
            </div>

            {{-- Pricing --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                <h2 class="text-sm font-semibold text-gray-700 mb-4">Prix & TVA</h2>
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Prix achat HT</label>
                        <input type="number" name="purchase_price_ht" step="0.01" min="0"
                               value="{{ old('purchase_price_ht', $article->purchase_price_ht ?? 0) }}"
                               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Prix vente HT *</label>
                        <input type="number" name="sale_price_ht" id="saleHt" step="0.01" min="0"
                               value="{{ old('sale_price_ht', $article->sale_price_ht ?? 0) }}"
                               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">TVA (%)</label>
                        <select name="tax_rate" id="taxRate" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @foreach([0, 5, 10, 18, 20] as $t)
                                <option value="{{ $t }}" {{ old('tax_rate', $article->tax_rate ?? 18) == $t ? 'selected' : '' }}>{{ $t }}%</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-span-3 bg-blue-50 rounded-lg p-3 flex items-center justify-between">
                        <span class="text-sm text-blue-700 font-medium">Prix TTC calculé :</span>
                        <span id="priceTtc" class="text-xl font-bold text-blue-800">
                            {{ number_format(($article->sale_price_ttc ?? 0), 0, ',', ' ') }} FCFA
                        </span>
                    </div>
                </div>
            </div>

            {{-- Stock --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                <h2 class="text-sm font-semibold text-gray-700 mb-4">Gestion des stocks</h2>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Stock minimum (alerte)</label>
                        <input type="number" name="stock_min" min="0"
                               value="{{ old('stock_min', $article->stock_min ?? 5) }}"
                               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    @if(!isset($article->id))
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Stock initial</label>
                        <input type="number" name="initial_stock" min="0"
                               value="{{ old('initial_stock', 0) }}"
                               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <p class="text-xs text-gray-400 mt-0.5">Quantité en stock au moment de la création</p>
                    </div>
                    @else
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Stock total actuel</label>
                        <div class="w-full border border-gray-100 bg-gray-50 rounded-lg px-3 py-2 text-sm font-semibold text-gray-700">
                            {{ $article->branchStocks->sum('quantity') }} {{ $article->unit }}
                        </div>
                        <p class="text-xs text-gray-400 mt-0.5">Gérez le stock par succursale depuis la fiche article</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-4">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                <h2 class="text-sm font-semibold text-gray-700 mb-4">Photos (max 5)</h2>
                <input type="file" name="photos[]" multiple accept="image/*"
                       class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                @if(isset($article->photos) && count($article->photos ?? []))
                    <div class="grid grid-cols-3 gap-2 mt-3">
                        @foreach($article->photos as $photo)
                            <img src="{{ asset('storage/'.$photo) }}" class="rounded-lg object-cover w-full h-16">
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 space-y-3">
                <button type="submit"
                        class="w-full bg-blue-600 text-white py-2.5 rounded-lg text-sm font-semibold hover:bg-blue-700 flex items-center justify-center gap-2">
                    <i class="fas fa-save"></i>
                    {{ isset($article->id) ? 'Enregistrer les modifications' : 'Créer l\'article' }}
                </button>
                <a href="{{ route('articles.index') }}"
                   class="w-full block text-center border border-gray-200 text-gray-600 py-2.5 rounded-lg text-sm hover:bg-gray-50">
                    Annuler
                </a>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
function regenCode() {
    const btn = document.querySelector('[onclick="regenCode()"]');
    btn.querySelector('i').classList.add('fa-spin');
    fetch('{{ route("articles.generate-code") }}', {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        document.getElementById('articleCode').value = data.code;
    })
    .finally(() => btn.querySelector('i').classList.remove('fa-spin'));
}

function updateTtc() {
    const ht = parseFloat(document.getElementById('saleHt').value) || 0;
    const tax = parseFloat(document.getElementById('taxRate').value) || 0;
    const ttc = ht * (1 + tax / 100);
    document.getElementById('priceTtc').textContent = ttc.toLocaleString('fr-FR', {maximumFractionDigits: 0}) + ' FCFA';
}
document.getElementById('saleHt').addEventListener('input', updateTtc);
document.getElementById('taxRate').addEventListener('change', updateTtc);
</script>
@endpush
