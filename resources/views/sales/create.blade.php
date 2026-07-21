@extends('layouts.app')
@section('title', 'Nouvelle vente')
@section('page-title', 'Point de Vente')

@section('content')
<form id="saleForm" method="POST" action="{{ route('sales.store') }}">
@csrf
<div class="grid grid-cols-1 lg:grid-cols-5 gap-4">

    {{-- LEFT: Search + scan --}}
    <div class="lg:col-span-3 space-y-4">
        {{-- Branch selector --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 flex items-center gap-4">
            <label class="text-sm font-medium text-gray-600">Succursale :</label>
            <select name="branch_id" id="branchSelect"
                    class="border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-blue-500">
                @foreach($branches as $b)
                    <option value="{{ $b->id }}" {{ $b->id == ($branchId ?? '') ? 'selected' : '' }}>{{ $b->name }}</option>
                @endforeach
            </select>
        </div>

        {{-- Search bar --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
            <div class="relative">
                <input type="text" id="searchInput" placeholder="Rechercher un article par nom ou référence..."
                       class="w-full border border-gray-200 rounded-lg pl-10 pr-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
            </div>
            <div id="searchResults" class="mt-2 space-y-1 max-h-56 overflow-y-auto hidden"></div>
        </div>

        {{-- Cart --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100">
            <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-700">Panier</h3>
                <span id="cartCount" class="bg-blue-100 text-blue-700 text-xs font-bold px-2 py-0.5 rounded-full">0 article(s)</span>
            </div>
            <div id="cartItems" class="divide-y divide-gray-50 min-h-[80px]">
                <div id="emptyCart" class="flex items-center justify-center py-10 text-gray-400 text-sm">
                    <div class="text-center"><i class="fas fa-shopping-cart text-2xl mb-2 block"></i>Panier vide</div>
                </div>
            </div>
        </div>

        {{-- Customer --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
            <label class="text-xs font-medium text-gray-600">Client (optionnel)</label>
            <select name="customer_id" class="mt-1 w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
                <option value="">— Vente anonyme —</option>
                @foreach($customers as $c)
                    <option value="{{ $c->id }}">{{ $c->name }} @if($c->phone)({{ $c->phone }})@endif</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- RIGHT: Total + payment --}}
    <div class="lg:col-span-2 space-y-4">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
            <h3 class="text-sm font-semibold text-gray-700 mb-3">Récapitulatif</h3>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between text-gray-600">
                    <span>Sous-total HT</span>
                    <span id="subtotalHt">0 FCFA</span>
                </div>
                <div class="flex justify-between text-gray-600">
                    <span>TVA</span>
                    <span id="taxTotal">0 FCFA</span>
                </div>
                <div class="flex justify-between text-gray-600">
                    <span>Remise</span>
                    <span id="discountTotal" class="text-red-500">0 FCFA</span>
                </div>
                <div class="flex justify-between font-bold text-lg text-gray-900 border-t border-gray-100 pt-2">
                    <span>TOTAL TTC</span>
                    <span id="grandTotal">0 FCFA</span>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
            <h3 class="text-sm font-semibold text-gray-700 mb-3">Paiement</h3>
            <div id="paymentMethods" class="space-y-2">
                <div class="payment-row flex items-center gap-2">
                    <select name="payment_methods[0][method]" class="border border-gray-200 rounded-lg px-2 py-1.5 text-sm flex-1">
                        <option value="cash">Espèces</option>
                        <option value="mobile_money">Mobile Money</option>
                        <option value="card">Carte bancaire</option>
                        <option value="credit">Crédit client</option>
                    </select>
                    <input type="number" name="payment_methods[0][amount]" step="0.01" min="0" placeholder="Montant"
                           class="border border-gray-200 rounded-lg px-2 py-1.5 text-sm w-28 pm-amount">
                </div>
            </div>
            <button type="button" id="addPaymentMethod"
                    class="mt-2 text-xs text-blue-600 hover:text-blue-800 flex items-center gap-1">
                <i class="fas fa-plus"></i> Ajouter mode de paiement
            </button>

            <div class="mt-3 pt-3 border-t border-gray-100 space-y-1 text-sm">
                <div class="flex justify-between text-gray-600">
                    <span>Montant reçu</span>
                    <span id="amountReceived" class="font-medium">0 FCFA</span>
                </div>
                <div class="flex justify-between font-bold">
                    <span>Monnaie</span>
                    <span id="change" class="text-green-600">0 FCFA</span>
                </div>
            </div>
        </div>

        <textarea name="notes" placeholder="Notes (optionnel)"
                  class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 resize-none" rows="2"></textarea>

        <button type="submit" id="submitBtn"
                class="w-full bg-blue-600 text-white py-3 rounded-xl text-sm font-bold hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                disabled>
            <i class="fas fa-check"></i> Valider la vente
        </button>
    </div>
</div>
</form>

<div id="itemsContainer" style="display:none"></div>
@endsection

@push('scripts')
<script>
let cart = [];
let pmIndex = 1;

// Search
let searchTimeout;
document.getElementById('searchInput').addEventListener('input', function() {
    clearTimeout(searchTimeout);
    const q = this.value.trim();
    if (q.length < 2) { document.getElementById('searchResults').classList.add('hidden'); return; }
    searchTimeout = setTimeout(() => {
        const branchId = document.getElementById('branchSelect').value;
        fetch(`{{ route('articles.search') }}?q=${encodeURIComponent(q)}&branch_id=${branchId}`)
            .then(r => r.json())
            .then(articles => {
                const el = document.getElementById('searchResults');
                el.innerHTML = articles.map(a => `
                    <div class="flex items-center justify-between p-2 rounded-lg hover:bg-blue-50 cursor-pointer"
                         onclick="addToCart(${JSON.stringify(a).replace(/"/g,'&quot;')})">
                        <div>
                            <span class="text-sm font-medium text-gray-800">${a.designation}</span>
                            <span class="text-xs text-gray-400 ml-2">${a.reference || ''}</span>
                        </div>
                        <div class="text-right flex-shrink-0 ml-4">
                            <span class="text-sm font-bold text-blue-700">${formatPrice(a.sale_price_ttc)}</span>
                            <span class="text-xs text-gray-400 block">Stock: ${a.stock}</span>
                        </div>
                    </div>
                `).join('') || '<div class="text-center text-gray-400 text-sm py-3">Aucun article trouvé</div>';
                el.classList.remove('hidden');
            });
    }, 300);
});

function addToCart(article) {
    const availableStock = parseInt(article.stock ?? 0, 10);
    if (availableStock <= 0) {
        alert('Stock indisponible pour cet article dans cette succursale.');
        return;
    }

    const existing = cart.find(i => i.id === article.id);
    if (existing) {
        if (existing.quantity >= availableStock) {
            alert(`Quantite maximale atteinte (stock: ${availableStock}).`);
            return;
        }
        existing.quantity++;
    } else {
        cart.push({ ...article, stock: availableStock, quantity: 1, discount: 0 });
    }
    document.getElementById('searchInput').value = '';
    document.getElementById('searchResults').classList.add('hidden');
    renderCart();
}

function renderCart() {
    const el = document.getElementById('cartItems');
    const empty = document.getElementById('emptyCart');
    document.getElementById('cartCount').textContent = cart.length + ' article(s)';

    if (cart.length === 0) {
        el.innerHTML = '';
        el.appendChild(empty);
        document.getElementById('submitBtn').disabled = true;
        updateTotals();
        return;
    }

    empty.remove();
    el.innerHTML = cart.map((item, i) => `
        <div class="flex items-center gap-3 px-4 py-3">
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-800 truncate">${item.designation}</p>
                <p class="text-xs text-gray-400">${formatPrice(item.sale_price_ttc)} / ${item.unit} | Stock: ${item.stock ?? 0}</p>
            </div>
            <div class="flex items-center gap-1">
                <button type="button" onclick="changeQty(${i}, -1)"
                        class="w-7 h-7 rounded bg-gray-100 text-gray-600 hover:bg-red-100 hover:text-red-600 text-sm font-bold">−</button>
                <input type="number" id="qty-${i}" value="${item.quantity}" min="1"
                       oninput="setQty(${i}, this.value)"
                       class="w-12 text-center border border-gray-200 rounded text-sm py-0.5 font-semibold">
                <button type="button" onclick="changeQty(${i}, 1)"
                        class="w-7 h-7 rounded bg-gray-100 text-gray-600 hover:bg-green-100 hover:text-green-600 text-sm font-bold">+</button>
            </div>
            <div class="text-right min-w-[90px]">
                <span id="row-total-${i}" class="text-sm font-bold text-blue-700">${formatPrice(item.sale_price_ttc * item.quantity)}</span>
                <p class="text-xs text-gray-400">${item.quantity} × ${formatPrice(item.sale_price_ttc)}</p>
            </div>
            <button type="button" onclick="removeItem(${i})" class="text-red-400 hover:text-red-600 ml-1">
                <i class="fas fa-times text-xs"></i>
            </button>
        </div>
    `).join('');

    document.getElementById('submitBtn').disabled = false;
    updateTotals();
    buildHiddenInputs();
}

function changeQty(i, delta) {
    const maxQty = Math.max(1, parseInt(cart[i].stock ?? 0, 10));
    cart[i].quantity = Math.min(maxQty, Math.max(1, cart[i].quantity + delta));
    const input = document.getElementById(`qty-${i}`);
    if (input) input.value = cart[i].quantity;
    updateRowTotal(i);
    updateTotals();
    buildHiddenInputs();
}

function setQty(i, val) {
    const maxQty = Math.max(1, parseInt(cart[i].stock ?? 0, 10));
    const parsed = Math.min(maxQty, Math.max(1, parseInt(val) || 1));
    cart[i].quantity = parsed;
    const input = document.getElementById(`qty-${i}`);
    if (input) input.value = parsed;
    updateRowTotal(i);
    updateTotals();
    buildHiddenInputs();
}

function updateRowTotal(i) {
    const span = document.getElementById(`row-total-${i}`);
    if (!span) return;
    const item = cart[i];
    span.textContent = formatPrice(item.sale_price_ttc * item.quantity);
    const sub = span.nextElementSibling;
    if (sub) sub.textContent = `${item.quantity} × ${formatPrice(item.sale_price_ttc)}`;
}
function removeItem(i) {
    cart.splice(i, 1);
    renderCart();
}

function updateTotals() {
    let subtotalTtc = 0, tax = 0;
    cart.forEach(item => { subtotalTtc += item.sale_price_ttc * item.quantity; });
    const taxRate = 0.18;
    const subtotalHt = subtotalTtc / (1 + taxRate);
    tax = subtotalTtc - subtotalHt;

    document.getElementById('subtotalHt').textContent = formatPrice(subtotalHt);
    document.getElementById('taxTotal').textContent = formatPrice(tax);
    document.getElementById('discountTotal').textContent = '0 FCFA';
    document.getElementById('grandTotal').textContent = formatPrice(subtotalTtc);

    // auto-fill first payment amount
    const firstAmt = document.querySelector('.pm-amount');
    if (firstAmt && !firstAmt.value) firstAmt.value = Math.round(subtotalTtc);
    calcChange();
}

function calcChange() {
    const total = cart.reduce((s, i) => s + i.sale_price_ttc * i.quantity, 0);
    const received = Array.from(document.querySelectorAll('.pm-amount'))
        .reduce((s, inp) => s + (parseFloat(inp.value) || 0), 0);
    document.getElementById('amountReceived').textContent = formatPrice(received);
    document.getElementById('change').textContent = formatPrice(Math.max(0, received - total));
}

document.addEventListener('input', e => { if (e.target.classList.contains('pm-amount')) calcChange(); });

document.getElementById('addPaymentMethod').addEventListener('click', function() {
    const div = document.createElement('div');
    div.className = 'payment-row flex items-center gap-2';
    div.innerHTML = `
        <select name="payment_methods[${pmIndex}][method]" class="border border-gray-200 rounded-lg px-2 py-1.5 text-sm flex-1">
            <option value="cash">Espèces</option>
            <option value="mobile_money">Mobile Money</option>
            <option value="card">Carte bancaire</option>
            <option value="credit">Crédit client</option>
        </select>
        <input type="number" name="payment_methods[${pmIndex}][amount]" step="0.01" min="0" placeholder="Montant"
               class="border border-gray-200 rounded-lg px-2 py-1.5 text-sm w-28 pm-amount">
        <button type="button" onclick="this.parentElement.remove()" class="text-red-400 hover:text-red-600 text-xs">
            <i class="fas fa-times"></i>
        </button>`;
    document.getElementById('paymentMethods').appendChild(div);
    pmIndex++;
});

document.getElementById('branchSelect').addEventListener('change', function() {
    cart = [];
    renderCart();
    document.getElementById('searchInput').value = '';
    document.getElementById('searchResults').classList.add('hidden');
});

function buildHiddenInputs() {
    const container = document.getElementById('itemsContainer');
    container.innerHTML = '';
    cart.forEach((item, i) => {
        container.innerHTML += `
            <input type="hidden" name="items[${i}][article_id]" value="${item.id}">
            <input type="hidden" name="items[${i}][quantity]" value="${item.quantity}">
            <input type="hidden" name="items[${i}][unit_price_ttc]" value="${item.sale_price_ttc}">
            <input type="hidden" name="items[${i}][discount_amount]" value="${item.discount || 0}">
        `;
    });
    container.style.display = 'none';
    document.getElementById('saleForm').appendChild(container);
}

function formatPrice(n) {
    return Math.round(n).toLocaleString('fr-FR') + ' FCFA';
}
</script>
@endpush
