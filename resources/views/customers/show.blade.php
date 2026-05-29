@extends('layouts.app')
@section('title', $customer->name)
@section('page-title', $customer->name)

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <div class="lg:col-span-2 space-y-4">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <h2 class="text-xl font-bold text-gray-800">{{ $customer->name }}</h2>
                    @if($customer->company_name)
                        <p class="text-sm text-gray-500">{{ $customer->company_name }}</p>
                    @endif
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('customers.edit', $customer) }}"
                       class="flex items-center gap-1 border border-gray-200 text-gray-600 px-3 py-1.5 rounded-lg text-sm hover:bg-gray-50">
                        <i class="fas fa-pen"></i> Modifier
                    </a>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div>
                    <p class="text-xs text-gray-400">Type</p>
                    <p class="font-medium capitalize">
                        {{ $customer->type === 'individual' ? 'Particulier' :
                           ($customer->type === 'professional' ? 'Professionnel' : 'Grossiste') }}
                    </p>
                </div>
                @if($customer->phone)
                <div>
                    <p class="text-xs text-gray-400">Téléphone</p>
                    <p>{{ $customer->phone }}</p>
                </div>
                @endif
                @if($customer->email)
                <div>
                    <p class="text-xs text-gray-400">Email</p>
                    <p>{{ $customer->email }}</p>
                </div>
                @endif
                @if($customer->address)
                <div>
                    <p class="text-xs text-gray-400">Adresse</p>
                    <p>{{ $customer->address }}</p>
                </div>
                @endif
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <h3 class="text-sm font-semibold text-gray-700 mb-3">Historique des achats</h3>
            @forelse($customer->sales()->latest()->take(10)->get() as $sale)
            <div class="flex items-center justify-between py-2 border-b border-gray-50 last:border-0">
                <div>
                    <p class="text-sm font-mono text-blue-700">{{ $sale->invoice_number }}</p>
                    <p class="text-xs text-gray-400">{{ $sale->created_at->format('d/m/Y H:i') }}</p>
                </div>
                <div class="text-right">
                    <p class="font-semibold text-gray-800">{{ number_format($sale->total_ttc, 0, ',', ' ') }} FCFA</p>
                    <span class="text-xs {{ $sale->payment_status === 'paid' ? 'text-green-600' : 'text-red-500' }}">
                        {{ $sale->payment_status === 'paid' ? 'Payé' : 'Crédit' }}
                    </span>
                </div>
            </div>
            @empty
            <p class="text-sm text-gray-400 text-center py-4">Aucun achat</p>
            @endforelse
        </div>
    </div>

    <div class="space-y-4">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <h3 class="text-sm font-semibold text-gray-700 mb-3">Statistiques</h3>
            <div class="space-y-3 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-500">Total achats</span>
                    <span class="font-bold text-gray-800">{{ number_format($customer->sales()->sum('total_ttc'), 0, ',', ' ') }} FCFA</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Nb. commandes</span>
                    <span class="font-semibold">{{ $customer->sales()->count() }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Crédit en cours</span>
                    <span class="font-semibold {{ $customer->credit_balance > 0 ? 'text-red-600' : 'text-gray-800' }}">
                        {{ number_format($customer->credit_balance, 0, ',', ' ') }} FCFA
                    </span>
                </div>
                @if($customer->credit_limit > 0)
                <div class="flex justify-between">
                    <span class="text-gray-500">Limite crédit</span>
                    <span class="font-semibold">{{ number_format($customer->credit_limit, 0, ',', ' ') }} FCFA</span>
                </div>
                @endif
            </div>
        </div>

        @if($customer->notes)
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <h3 class="text-sm font-semibold text-gray-700 mb-2">Notes</h3>
            <p class="text-sm text-gray-600">{{ $customer->notes }}</p>
        </div>
        @endif

        <p class="text-xs text-gray-400 text-center">Client depuis le {{ $customer->created_at->format('d/m/Y') }}</p>
    </div>
</div>
@endsection
