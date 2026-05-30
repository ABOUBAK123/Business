@extends('layouts.app')
@section('title', 'Nouveau retour')
@section('page-title', 'Enregistrer un retour')

@section('content')

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

    {{-- Formulaire gauche --}}
    <div class="lg:col-span-2 space-y-4">

        {{-- Recherche facture d'origine --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <h3 class="text-sm font-semibold text-gray-700 mb-3"><i class="fas fa-search text-gray-400 mr-1"></i> Facture d'origine (optionnel)</h3>
            <form class="flex gap-2" method="GET" action="{{ route('returns.create') }}">
                <input type="text" name="sale_id" placeholder="Numéro ou ID de facture..."
                       value="{{ request('sale_id') }}"
                       class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                <button class="bg-gray-100 px-4 py-2 rounded-lg text-sm hover:bg-gray-200">Charger</button>
            </form>
            @if($sale)
            <div class="mt-3 bg-blue-50 rounded-lg p-3 text-sm">
                <p class="font-semibold text-blue-800">{{ $sale->invoice_number }}</p>
                <p class="text-blue-600 text-xs">{{ $sale->created_at->format('d/m/Y') }} · {{ number_format($sale->total_ttc, 0, ',', ' ') }} FCFA</p>
            </div>
            @endif
        </div>

        {{-- Formulaire principal --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <form method="POST" action="{{ route('returns.store') }}" id="returnForm">
                @csrf
                @if($sale)
                <input type="hidden" name="sale_id" value="{{ $sale->id }}">
                @endif

                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Boutique *</label>
                        <select name="branch_id" required
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            @foreach($branches as $b)
                            <option value="{{ $b->id }}">{{ $b->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Client</label>
                        <select name="customer_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option value="">— Sans client —</option>
                            @foreach($customers as $c)
                            <option value="{{ $c->id }}" {{ $sale?->customer_id == $c->id ? 'selected':'' }}>
                                {{ $c->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Raison du retour *</label>
                        <input type="text" name="reason" required placeholder="Article défectueux, erreur de commande..."
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Mode de remboursement *</label>
                        <select name="refund_method" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option value="cash">Remboursement espèces</option>
                            <option value="credit">Avoir client (crédit)</option>
                            <option value="exchange">Échange</option>
                        </select>
                    </div>
                    <div class="col-span-2">
                        <label class="block text-xs text-gray-500 mb-1">Notes</label>
                        <textarea name="notes" rows="2"
                                  class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"></textarea>
                    </div>
                </div>

                {{-- Articles à retourner --}}
                <h3 class="text-sm font-semibold text-gray-700 mb-3 border-t pt-3">
                    <i class="fas fa-box-open text-gray-400 mr-1"></i> Articles retournés
                </h3>

                <div id="itemsContainer" class="space-y-2 mb-3">
                    {{-- Si facture sélectionnée, pré-remplir avec ses articles --}}
                    @if($sale)
                        @foreach($sale->items as $idx => $item)
                        <div class="flex gap-2 items-center bg-gray-50 rounded-lg p-2" data-item-row>
                            <input type="hidden" name="items[{{ $idx }}][article_id]" value="{{ $item->article_id }}">
                            <span class="text-sm font-medium flex-1">{{ $item->designation }}</span>
                            <div>
                                <label class="text-xs text-gray-400">Qté</label>
                                <input type="number" name="items[{{ $idx }}][quantity]"
                                       min="0.01" max="{{ $item->quantity }}" step="0.01"
                                       value="{{ $item->quantity }}"
                                       class="w-20 border border-gray-300 rounded px-2 py-1 text-sm text-right">
                            </div>
                            <div>
                                <label class="text-xs text-gray-400">Prix unit.</label>
                                <input type="number" name="items[{{ $idx }}][unit_price]"
                                       min="0" step="1" value="{{ $item->unit_price_ttc }}"
                                       class="w-24 border border-gray-300 rounded px-2 py-1 text-sm text-right">
                            </div>
                            <div class="flex items-center gap-1">
                                <input type="checkbox" name="items[{{ $idx }}][restock]" value="1" checked
                                       id="restock_{{ $idx }}" class="rounded">
                                <label for="restock_{{ $idx }}" class="text-xs text-gray-500">Remettre en stock</label>
                            </div>
                        </div>
                        @endforeach
                    @endif
                </div>

                {{-- Ajouter un article manuellement --}}
                <div class="border border-dashed border-gray-300 rounded-lg p-3 mb-4">
                    <p class="text-xs text-gray-500 mb-2">Ajouter un article :</p>
                    <div class="flex gap-2 flex-wrap">
                        <select id="addArticleSelect" class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm min-w-0">
                            <option value="">— Choisir un article —</option>
                            @foreach($articles as $art)
                            <option value="{{ $art->id }}"
                                    data-name="{{ $art->designation }}"
                                    data-price="{{ $art->sale_price_ttc }}"
                                    data-unit="{{ $art->unit }}">
                                {{ $art->reference }} — {{ $art->designation }}
                            </option>
                            @endforeach
                        </select>
                        <button type="button" onclick="addItem()"
                                class="bg-blue-600 text-white px-3 py-2 rounded-lg text-sm hover:bg-blue-700">
                            <i class="fas fa-plus"></i> Ajouter
                        </button>
                    </div>
                </div>

                <button type="submit"
                        class="w-full bg-blue-700 text-white py-2.5 rounded-lg font-semibold hover:bg-blue-800 flex items-center justify-center gap-2">
                    <i class="fas fa-save"></i> Enregistrer le retour
                </button>
            </form>
        </div>
    </div>

    {{-- Panneau droit : aide --}}
    <div class="space-y-4">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <h3 class="text-sm font-semibold text-gray-700 mb-3">Modes de remboursement</h3>
            <div class="space-y-3 text-sm">
                <div class="flex items-start gap-2">
                    <span class="bg-green-100 text-green-700 text-xs px-2 py-0.5 rounded-full mt-0.5 whitespace-nowrap">Espèces</span>
                    <p class="text-gray-500 text-xs">Le client est remboursé en cash immédiatement.</p>
                </div>
                <div class="flex items-start gap-2">
                    <span class="bg-blue-100 text-blue-700 text-xs px-2 py-0.5 rounded-full mt-0.5 whitespace-nowrap">Avoir</span>
                    <p class="text-gray-500 text-xs">Crédite le compte client — il pourra l'utiliser lors d'un prochain achat.</p>
                </div>
                <div class="flex items-start gap-2">
                    <span class="bg-purple-100 text-purple-700 text-xs px-2 py-0.5 rounded-full mt-0.5 whitespace-nowrap">Échange</span>
                    <p class="text-gray-500 text-xs">Remplacement par un autre article (pas de remboursement monétaire).</p>
                </div>
            </div>
        </div>
        <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 text-xs text-amber-700">
            <p class="font-semibold mb-1"><i class="fas fa-info-circle mr-1"></i> Remettre en stock</p>
            <p>Si coché, la quantité retournée est automatiquement réintégrée dans le stock de la boutique.</p>
        </div>
    </div>
</div>

<script>
let itemCount = {{ $sale ? $sale->items->count() : 0 }};

function addItem() {
    const sel = document.getElementById('addArticleSelect');
    const opt = sel.selectedOptions[0];
    if (!opt.value) return;

    const idx = itemCount++;
    const row = document.createElement('div');
    row.className = 'flex gap-2 items-center bg-gray-50 rounded-lg p-2';
    row.setAttribute('data-item-row', '');
    row.innerHTML = `
        <input type="hidden" name="items[${idx}][article_id]" value="${opt.value}">
        <span class="text-sm font-medium flex-1">${opt.dataset.name}</span>
        <div>
            <label class="text-xs text-gray-400">Qté</label>
            <input type="number" name="items[${idx}][quantity]" min="0.01" step="0.01" value="1"
                   class="w-20 border border-gray-300 rounded px-2 py-1 text-sm text-right">
        </div>
        <div>
            <label class="text-xs text-gray-400">Prix unit.</label>
            <input type="number" name="items[${idx}][unit_price]" min="0" step="1" value="${opt.dataset.price}"
                   class="w-24 border border-gray-300 rounded px-2 py-1 text-sm text-right">
        </div>
        <div class="flex items-center gap-1">
            <input type="checkbox" name="items[${idx}][restock]" value="1" checked id="restock_${idx}" class="rounded">
            <label for="restock_${idx}" class="text-xs text-gray-500">Remettre en stock</label>
        </div>
        <button type="button" onclick="this.closest('[data-item-row]').remove()"
                class="text-red-400 hover:text-red-600 ml-1"><i class="fas fa-times"></i></button>
    `;
    document.getElementById('itemsContainer').appendChild(row);
    sel.value = '';
}
</script>
@endsection
