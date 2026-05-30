@extends('layouts.app')
@section('title', $customer->name)
@section('page-title', $customer->name)

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

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

    {{-- Colonne gauche --}}
    <div class="lg:col-span-2 space-y-4">

        {{-- Infos client --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <h2 class="text-xl font-bold text-gray-800">{{ $customer->name }}</h2>
                    <span class="text-xs px-2 py-0.5 rounded-full
                        {{ $customer->type === 'wholesale' ? 'bg-purple-100 text-purple-700' :
                           ($customer->type === 'professional' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-600') }}">
                        {{ $customer->type === 'individual' ? 'Particulier' :
                           ($customer->type === 'professional' ? 'Professionnel' : 'Grossiste') }}
                    </span>
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('customers.edit', $customer) }}"
                       class="flex items-center gap-1 border border-gray-200 text-gray-600 px-3 py-1.5 rounded-lg text-sm hover:bg-gray-50">
                        <i class="fas fa-pen"></i> Modifier
                    </a>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3 text-sm">
                @if($customer->phone)
                <div><p class="text-xs text-gray-400">Téléphone</p><p class="font-medium">{{ $customer->phone }}</p></div>
                @endif
                @if($customer->email)
                <div><p class="text-xs text-gray-400">Email</p><p>{{ $customer->email }}</p></div>
                @endif
                @if($customer->address)
                <div class="col-span-2"><p class="text-xs text-gray-400">Adresse</p><p>{{ $customer->address }}</p></div>
                @endif
                @if($customer->nif)
                <div><p class="text-xs text-gray-400">NIF</p><p>{{ $customer->nif }}</p></div>
                @endif
            </div>
        </div>

        {{-- Formulaire paiement crédit --}}
        @if($customer->credit_balance > 0)
        <div class="bg-amber-50 border border-amber-200 rounded-xl p-5">
            <h3 class="text-sm font-bold text-amber-800 mb-3 flex items-center gap-2">
                <i class="fas fa-hand-holding-usd"></i>
                Enregistrer un paiement — Solde dû : <span class="text-red-600">{{ number_format($customer->credit_balance, 0, ',', ' ') }} FCFA</span>
            </h3>
            <form method="POST" action="{{ route('customers.payment', $customer) }}" class="flex flex-wrap gap-3 items-end">
                @csrf
                <div>
                    <label class="block text-xs text-gray-600 mb-1">Montant (FCFA) *</label>
                    <input type="number" name="amount" min="1" max="{{ $customer->credit_balance }}"
                           value="{{ old('amount', $customer->credit_balance) }}"
                           class="border border-gray-300 rounded-lg px-3 py-2 text-sm w-36 focus:ring-2 focus:ring-blue-500" required>
                </div>
                <div>
                    <label class="block text-xs text-gray-600 mb-1">Mode de paiement *</label>
                    <select name="payment_method" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500" required>
                        <option value="cash">Espèces</option>
                        <option value="mobile_money">Mobile Money</option>
                        <option value="bank_transfer">Virement</option>
                        <option value="cheque">Chèque</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-gray-600 mb-1">Référence</label>
                    <input type="text" name="reference" placeholder="N° reçu, transaction..." value="{{ old('reference') }}"
                           class="border border-gray-300 rounded-lg px-3 py-2 text-sm w-40 focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs text-gray-600 mb-1">Notes</label>
                    <input type="text" name="notes" value="{{ old('notes') }}"
                           class="border border-gray-300 rounded-lg px-3 py-2 text-sm w-40 focus:ring-2 focus:ring-blue-500">
                </div>
                <button type="submit"
                        class="bg-green-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-green-700 flex items-center gap-2">
                    <i class="fas fa-check"></i> Valider le paiement
                </button>
            </form>
            @error('amount')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
        </div>
        @endif

        {{-- Historique des paiements --}}
        @if($customer->payments->isNotEmpty())
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <h3 class="text-sm font-semibold text-gray-700 mb-3">
                <i class="fas fa-money-bill-wave text-green-500 mr-1"></i> Historique des paiements
            </h3>
            <div class="space-y-2">
                @foreach($customer->payments->sortByDesc('created_at') as $payment)
                <div class="flex items-center justify-between py-2 border-b border-gray-50 last:border-0 text-sm">
                    <div>
                        <span class="font-medium text-green-700">+{{ number_format($payment->amount, 0, ',', ' ') }} FCFA</span>
                        <span class="ml-2 text-xs px-1.5 py-0.5 rounded bg-gray-100 text-gray-600">
                            {{ match($payment->payment_method) {
                                'cash' => 'Espèces',
                                'mobile_money' => 'Mobile Money',
                                'bank_transfer' => 'Virement',
                                'cheque' => 'Chèque',
                                default => $payment->payment_method
                            } }}
                        </span>
                        @if($payment->reference)
                            <span class="text-xs text-gray-400 ml-1">Réf: {{ $payment->reference }}</span>
                        @endif
                    </div>
                    <div class="text-right text-xs text-gray-400">
                        <div>{{ $payment->created_at->format('d/m/Y H:i') }}</div>
                        <div>par {{ $payment->user?->name }}</div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Historique achats --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <h3 class="text-sm font-semibold text-gray-700 mb-3">
                <i class="fas fa-receipt text-blue-500 mr-1"></i> Historique des achats
            </h3>
            @forelse($sales as $sale)
            <div class="flex items-center justify-between py-2 border-b border-gray-50 last:border-0">
                <div>
                    <a href="{{ route('sales.show', $sale) }}" class="text-sm font-mono text-blue-700 hover:underline">{{ $sale->invoice_number }}</a>
                    <p class="text-xs text-gray-400">{{ $sale->created_at->format('d/m/Y H:i') }} — {{ $sale->branch?->name }}</p>
                </div>
                <div class="text-right">
                    <p class="font-semibold text-gray-800 text-sm">{{ number_format($sale->total_ttc, 0, ',', ' ') }} FCFA</p>
                    @if($sale->payment_status === 'paid')
                        <span class="text-xs text-green-600">Payé</span>
                    @elseif($sale->payment_status === 'partial')
                        <span class="text-xs text-yellow-600">Partiel</span>
                    @else
                        <span class="text-xs text-red-500">Crédit</span>
                    @endif
                </div>
            </div>
            @empty
            <p class="text-sm text-gray-400 text-center py-4">Aucun achat</p>
            @endforelse
        </div>

    </div>

    {{-- Colonne droite : stats --}}
    <div class="space-y-4">

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 space-y-3">
            <h3 class="text-sm font-semibold text-gray-700">Statistiques</h3>

            <div class="flex justify-between text-sm">
                <span class="text-gray-500">Total achats</span>
                <span class="font-bold text-gray-800">{{ number_format($stats['total_achats'], 0, ',', ' ') }} FCFA</span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-gray-500">Nb. commandes</span>
                <span class="font-semibold">{{ $stats['nb_commandes'] }}</span>
            </div>
            <hr>
            <div class="flex justify-between text-sm">
                <span class="text-gray-500">Total à crédit</span>
                <span class="font-semibold text-orange-600">{{ number_format($stats['credit_sales'], 0, ',', ' ') }} FCFA</span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-gray-500">Total remboursé</span>
                <span class="font-semibold text-green-600">{{ number_format($stats['total_paye'], 0, ',', ' ') }} FCFA</span>
            </div>
            <div class="flex justify-between text-sm border-t pt-2">
                <span class="text-gray-700 font-medium">Solde dû</span>
                <span class="font-bold text-lg {{ $customer->credit_balance > 0 ? 'text-red-600' : 'text-green-600' }}">
                    {{ number_format($customer->credit_balance, 0, ',', ' ') }} FCFA
                </span>
            </div>
            @if($customer->credit_limit > 0)
            <div class="flex justify-between text-sm">
                <span class="text-gray-500">Limite crédit</span>
                <span class="font-semibold">{{ number_format($customer->credit_limit, 0, ',', ' ') }} FCFA</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2">
                <div class="bg-red-500 h-2 rounded-full" style="width: {{ min(100, ($customer->credit_balance / $customer->credit_limit) * 100) }}%"></div>
            </div>
            @endif
        </div>

        <p class="text-xs text-gray-400 text-center">Client depuis le {{ $customer->created_at->format('d/m/Y') }}</p>
    </div>

</div>
@endsection
