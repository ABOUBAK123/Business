@extends('layouts.app')
@section('title', 'Clients')
@section('page-title', 'Gestion des clients')

@section('content')
{{-- KPIs crédit --}}
@if($creditCount > 0)
<div class="grid grid-cols-2 gap-3 mb-4">
    <div class="bg-red-50 border border-red-100 rounded-xl px-4 py-3 flex items-center gap-3">
        <i class="fas fa-exclamation-triangle text-red-400 text-xl"></i>
        <div>
            <p class="text-xs text-red-500">Clients avec crédit</p>
            <p class="text-lg font-bold text-red-700">{{ $creditCount }}</p>
        </div>
    </div>
    <div class="bg-orange-50 border border-orange-100 rounded-xl px-4 py-3 flex items-center gap-3">
        <i class="fas fa-hand-holding-usd text-orange-400 text-xl"></i>
        <div>
            <p class="text-xs text-orange-500">Total crédit en cours</p>
            <p class="text-lg font-bold text-orange-700">{{ number_format($totalCredit, 0, ',', ' ') }} FCFA</p>
        </div>
    </div>
</div>
@endif

<div class="flex items-center justify-between mb-4 flex-wrap gap-2">
    <form class="flex gap-2 flex-wrap">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Nom, téléphone..."
               class="border border-gray-200 rounded-lg px-3 py-2 text-sm w-44 focus:ring-2 focus:ring-blue-500">
        <select name="type" class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
            <option value="">Tous les types</option>
            <option value="individual"    {{ request('type') === 'individual'    ? 'selected' : '' }}>Particulier</option>
            <option value="professional"  {{ request('type') === 'professional'  ? 'selected' : '' }}>Professionnel</option>
            <option value="wholesale"     {{ request('type') === 'wholesale'     ? 'selected' : '' }}>Grossiste</option>
        </select>
        <select name="filter" class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
            <option value="">Tous</option>
            <option value="credit" {{ request('filter') === 'credit' ? 'selected' : '' }}>Avec crédit en cours</option>
        </select>
        <button class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700">Filtrer</button>
    </form>
    <a href="{{ route('customers.create') }}"
       class="flex items-center gap-2 bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700">
        <i class="fas fa-plus"></i> Nouveau client
    </a>
</div>

@if(session('success'))
<div class="bg-green-50 border border-green-200 text-green-700 rounded-lg p-3 mb-4 text-sm">{{ session('success') }}</div>
@endif

<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Client</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Type</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Contact</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Crédit utilisé</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Total achats</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @forelse($customers as $customer)
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3">
                    <p class="font-medium text-gray-800">{{ $customer->name }}</p>
                    @if($customer->company_name)
                        <p class="text-xs text-gray-400">{{ $customer->company_name }}</p>
                    @endif
                </td>
                <td class="px-4 py-3">
                    <span class="text-xs px-2 py-0.5 rounded-full
                        {{ $customer->type === 'wholesale' ? 'bg-purple-100 text-purple-700' :
                           ($customer->type === 'professional' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-600') }}">
                        {{ $customer->type === 'individual' ? 'Particulier' :
                           ($customer->type === 'professional' ? 'Professionnel' : 'Grossiste') }}
                    </span>
                </td>
                <td class="px-4 py-3 text-gray-600">
                    @if($customer->phone)<div>{{ $customer->phone }}</div>@endif
                    @if($customer->email)<div class="text-xs text-gray-400">{{ $customer->email }}</div>@endif
                </td>
                <td class="px-4 py-3 text-right">
                    @if($customer->credit_balance > 0)
                        <span class="text-red-600 font-medium">{{ number_format($customer->credit_balance, 0, ',', ' ') }}</span>
                    @else
                        <span class="text-gray-400">—</span>
                    @endif
                </td>
                <td class="px-4 py-3 text-right font-medium text-gray-800">
                    {{ number_format($customer->sales_sum_total_ttc ?? 0, 0, ',', ' ') }}
                </td>
                <td class="px-4 py-3">
                    <div class="flex items-center justify-end gap-2">
                        <a href="{{ route('customers.show', $customer) }}" class="text-gray-400 hover:text-blue-600"><i class="fas fa-eye"></i></a>
                        <a href="{{ route('customers.edit', $customer) }}" class="text-gray-400 hover:text-yellow-600"><i class="fas fa-pen"></i></a>
                        <form method="POST" action="{{ route('customers.destroy', $customer) }}"
                              onsubmit="return confirm('Supprimer ce client ?')">
                            @csrf @method('DELETE')
                            <button class="text-gray-400 hover:text-red-600"><i class="fas fa-trash"></i></button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="6" class="px-4 py-12 text-center text-gray-400">
                <i class="fas fa-users text-3xl mb-2 block"></i>Aucun client trouvé
            </td></tr>
            @endforelse
        </tbody>
    </table>
    @if($customers->hasPages())
        <div class="px-4 py-3 border-t border-gray-100">{{ $customers->links() }}</div>
    @endif
</div>
@endsection
