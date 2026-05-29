@extends('layouts.app')
@section('title', 'Boutiques')
@section('page-title', 'Gestion des boutiques')

@section('content')
<div class="flex items-center justify-between mb-4 flex-wrap gap-2">
    <form class="flex gap-2 flex-wrap">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Nom, slug..."
               class="border border-gray-200 rounded-lg px-3 py-2 text-sm w-48 focus:ring-2 focus:ring-blue-500">
        <select name="status" class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
            <option value="">Tous les statuts</option>
            <option value="trial" {{ request('status') === 'trial' ? 'selected' : '' }}>Essai</option>
            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Actif</option>
            <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>Suspendu</option>
            <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>Expiré</option>
        </select>
        <select name="plan_id" class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
            <option value="">Tous les plans</option>
            @foreach($plans as $plan)
                <option value="{{ $plan->id }}" {{ request('plan_id') == $plan->id ? 'selected' : '' }}>{{ $plan->name }}</option>
            @endforeach
        </select>
        <button class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700">Filtrer</button>
    </form>
    <span class="text-sm text-gray-500">{{ $tenants->total() }} boutique(s)</span>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Boutique</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Plan</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Propriétaire</th>
                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Statut</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Expiration</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @forelse($tenants as $tenant)
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3">
                    <p class="font-medium text-gray-800">{{ $tenant->shop_name }}</p>
                    <p class="text-xs text-gray-400">{{ $tenant->slug }}</p>
                </td>
                <td class="px-4 py-3 text-gray-600">{{ $tenant->plan?->name ?? '—' }}</td>
                <td class="px-4 py-3">
                    <p class="text-gray-700">{{ $tenant->owner?->name }}</p>
                    <p class="text-xs text-gray-400">{{ $tenant->owner?->email }}</p>
                </td>
                <td class="px-4 py-3 text-center">
                    <span class="text-xs px-2 py-0.5 rounded-full
                        {{ $tenant->status === 'active' ? 'bg-green-100 text-green-700' :
                           ($tenant->status === 'trial' ? 'bg-yellow-100 text-yellow-700' :
                           ($tenant->status === 'grace' ? 'bg-orange-100 text-orange-700' : 'bg-red-100 text-red-700')) }}">
                        {{ ucfirst($tenant->status) }}
                    </span>
                </td>
                <td class="px-4 py-3 text-gray-500 text-xs">
                    @if($tenant->subscription_ends_at)
                        {{ $tenant->subscription_ends_at->format('d/m/Y') }}
                    @elseif($tenant->trial_ends_at)
                        Essai : {{ $tenant->trial_ends_at->format('d/m/Y') }}
                    @else
                        —
                    @endif
                </td>
                <td class="px-4 py-3">
                    <div class="flex items-center justify-end gap-2">
                        <a href="{{ route('super-admin.tenants.show', $tenant) }}"
                           class="text-gray-400 hover:text-blue-600" title="Détail">
                            <i class="fas fa-eye"></i>
                        </a>
                        <form method="POST" action="{{ route('super-admin.tenants.toggle-status', $tenant) }}" class="inline">
                            @csrf @method('PATCH')
                            <button type="submit" class="text-gray-400 hover:text-{{ $tenant->status === 'active' ? 'red' : 'green' }}-600"
                                    title="{{ $tenant->status === 'active' ? 'Suspendre' : 'Activer' }}">
                                <i class="fas fa-{{ $tenant->status === 'active' ? 'ban' : 'check-circle' }}"></i>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="6" class="px-4 py-12 text-center text-gray-400">
                <i class="fas fa-store text-3xl mb-2 block"></i>Aucune boutique trouvée
            </td></tr>
            @endforelse
        </tbody>
    </table>
    @if($tenants->hasPages())
        <div class="px-4 py-3 border-t border-gray-100">{{ $tenants->links() }}</div>
    @endif
</div>
@endsection
