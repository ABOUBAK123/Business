@extends('layouts.app')
@section('title', $tenant->shop_name)
@section('page-title', $tenant->shop_name)

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <div class="lg:col-span-2 space-y-4">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-gray-700">Informations générales</h3>
                <span class="text-xs px-2 py-0.5 rounded-full
                    {{ $tenant->status === 'active' ? 'bg-green-100 text-green-700' :
                       ($tenant->status === 'trial' ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700') }}">
                    {{ ucfirst($tenant->status) }}
                </span>
            </div>
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div><p class="text-xs text-gray-400">Nom boutique</p><p class="font-medium">{{ $tenant->shop_name }}</p></div>
                <div><p class="text-xs text-gray-400">Slug</p><p class="font-mono text-xs">{{ $tenant->slug }}</p></div>
                <div><p class="text-xs text-gray-400">Ville</p><p>{{ $tenant->city ?? '—' }}</p></div>
                <div><p class="text-xs text-gray-400">Téléphone</p><p>{{ $tenant->phone ?? '—' }}</p></div>
                <div><p class="text-xs text-gray-400">Inscription</p><p>{{ $tenant->created_at->format('d/m/Y') }}</p></div>
                <div><p class="text-xs text-gray-400">Taux TVA</p><p>{{ $tenant->tax_rate }}%</p></div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <h3 class="text-sm font-semibold text-gray-700 mb-3">Propriétaire</h3>
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-user text-blue-600"></i>
                </div>
                <div>
                    <p class="font-medium text-gray-800">{{ $tenant->owner?->name }}</p>
                    <p class="text-sm text-gray-500">{{ $tenant->owner?->email }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <h3 class="text-sm font-semibold text-gray-700 mb-3">Succursales ({{ $tenant->branches->count() }})</h3>
            <div class="space-y-2">
                @foreach($tenant->branches as $branch)
                <div class="flex items-center justify-between py-2 border-b border-gray-50 last:border-0">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-building text-gray-400 w-4"></i>
                        <span class="text-sm text-gray-700">{{ $branch->name }}</span>
                        @if($branch->is_main) <span class="text-xs bg-blue-100 text-blue-600 px-1.5 py-0.5 rounded">Principale</span> @endif
                    </div>
                    <span class="text-xs {{ $branch->is_active ? 'text-green-600' : 'text-gray-400' }}">
                        {{ $branch->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="space-y-4">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <h3 class="text-sm font-semibold text-gray-700 mb-3">Abonnement</h3>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-500">Plan actuel</span>
                    <span class="font-semibold text-blue-700">{{ $tenant->plan?->name }}</span>
                </div>
                @if($tenant->trial_ends_at)
                <div class="flex justify-between">
                    <span class="text-gray-500">Fin essai</span>
                    <span>{{ $tenant->trial_ends_at->format('d/m/Y') }}</span>
                </div>
                @endif
                @if($tenant->subscription_ends_at)
                <div class="flex justify-between">
                    <span class="text-gray-500">Expiration</span>
                    <span>{{ $tenant->subscription_ends_at->format('d/m/Y') }}</span>
                </div>
                @endif
            </div>

            <form method="POST" action="{{ route('super-admin.tenants.change-plan', $tenant) }}" class="mt-4">
                @csrf @method('PATCH')
                <label class="text-xs font-medium text-gray-600 block mb-1">Changer de plan</label>
                <select name="plan_id" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm mb-2 focus:ring-2 focus:ring-blue-500">
                    @foreach($plans as $plan)
                        <option value="{{ $plan->id }}" {{ $tenant->plan_id == $plan->id ? 'selected' : '' }}>{{ $plan->name }}</option>
                    @endforeach
                </select>
                <button class="w-full bg-blue-600 text-white py-2 rounded-lg text-sm hover:bg-blue-700">Appliquer</button>
            </form>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <h3 class="text-sm font-semibold text-gray-700 mb-3">Statistiques</h3>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between text-gray-600">
                    <span>Utilisateurs</span>
                    <span class="font-semibold">{{ $tenant->users->count() }}</span>
                </div>
                <div class="flex justify-between text-gray-600">
                    <span>Articles</span>
                    <span class="font-semibold">{{ $tenant->articles->count() }}</span>
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('super-admin.tenants.toggle-status', $tenant) }}">
            @csrf @method('PATCH')
            <button type="submit"
                    class="w-full py-2.5 rounded-xl text-sm font-semibold border
                           {{ $tenant->status === 'active' ? 'border-red-200 text-red-600 hover:bg-red-50' : 'border-green-200 text-green-600 hover:bg-green-50' }}">
                <i class="fas fa-{{ $tenant->status === 'active' ? 'ban' : 'check-circle' }} mr-1"></i>
                {{ $tenant->status === 'active' ? 'Suspendre la boutique' : 'Réactiver la boutique' }}
            </button>
        </form>
    </div>
</div>
@endsection
