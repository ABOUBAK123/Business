@extends('layouts.app')
@section('title', isset($plan) ? 'Modifier plan' : 'Nouveau plan')
@section('page-title', isset($plan) ? 'Modifier : ' . $plan->name : 'Nouveau plan')

@section('content')
<div class="max-w-lg">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        @if($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 rounded-lg p-3 mb-4 text-sm">
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
        @endif

        <form method="POST" action="{{ isset($plan) ? route('super-admin.plans.update', $plan) : route('super-admin.plans.store') }}">
            @csrf
            @if(isset($plan)) @method('PUT') @endif

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Nom *</label>
                    <input type="text" name="name" value="{{ old('name', $plan->name ?? '') }}" required
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Slug *</label>
                    <input type="text" name="slug" value="{{ old('slug', $plan->slug ?? '') }}" required
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm font-mono focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Prix mensuel (FCFA)</label>
                    <input type="number" name="monthly_price" value="{{ old('monthly_price', $plan->monthly_price ?? 0) }}" min="0"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    <p class="text-xs text-gray-400 mt-0.5">0 = gratuit, -1 = sur devis</p>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Prix annuel (FCFA)</label>
                    <input type="number" name="annual_price" value="{{ old('annual_price', $plan->annual_price ?? 0) }}" min="0"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            <h4 class="text-xs font-semibold text-gray-500 uppercase mb-3 mt-2">Limites (-1 = illimité)</h4>
            <div class="grid grid-cols-2 gap-3 mb-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Succursales</label>
                    <input type="number" name="max_branches" value="{{ old('max_branches', $plan->max_branches ?? 1) }}" min="-1"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Articles</label>
                    <input type="number" name="max_articles" value="{{ old('max_articles', $plan->max_articles ?? 100) }}" min="-1"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Utilisateurs</label>
                    <input type="number" name="max_users" value="{{ old('max_users', $plan->max_users ?? 3) }}" min="-1"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Transactions/mois</label>
                    <input type="number" name="max_transactions_per_month" value="{{ old('max_transactions_per_month', $plan->max_transactions_per_month ?? -1) }}" min="-1"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3 mb-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Jours d'essai gratuit</label>
                    <input type="number" name="trial_days" value="{{ old('trial_days', $plan->trial_days ?? 0) }}" min="0"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Ordre d'affichage</label>
                    <input type="number" name="sort_order" value="{{ old('sort_order', $plan->sort_order ?? 0) }}" min="0"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            <div class="space-y-2 mb-6">
                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                    <input type="checkbox" name="has_advanced_reports" value="1"
                           {{ old('has_advanced_reports', $plan->has_advanced_reports ?? false) ? 'checked' : '' }}
                           class="rounded border-gray-300 text-blue-600">
                    Rapports avancés
                </label>
                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                    <input type="checkbox" name="has_api_access" value="1"
                           {{ old('has_api_access', $plan->has_api_access ?? false) ? 'checked' : '' }}
                           class="rounded border-gray-300 text-blue-600">
                    Accès API
                </label>
                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                    <input type="checkbox" name="has_priority_support" value="1"
                           {{ old('has_priority_support', $plan->has_priority_support ?? false) ? 'checked' : '' }}
                           class="rounded border-gray-300 text-blue-600">
                    Support prioritaire
                </label>
            </div>

            <div class="flex gap-3">
                <button type="submit" class="bg-blue-600 text-white px-6 py-2.5 rounded-lg text-sm font-semibold hover:bg-blue-700">
                    {{ isset($plan) ? 'Enregistrer' : 'Créer le plan' }}
                </button>
                <a href="{{ route('super-admin.plans.index') }}" class="px-6 py-2.5 border border-gray-200 text-gray-600 rounded-lg text-sm hover:bg-gray-50">
                    Annuler
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
