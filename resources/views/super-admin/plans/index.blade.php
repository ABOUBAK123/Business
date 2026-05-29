@extends('layouts.app')
@section('title', 'Plans')
@section('page-title', 'Gestion des plans')

@section('content')
<div class="flex items-center justify-between mb-4">
    <p class="text-sm text-gray-500">{{ $plans->count() }} plan(s) configuré(s)</p>
    <div class="flex items-center gap-2">
        <a href="{{ route('super-admin.commissioners.create') }}"
           style="display:inline-flex;align-items:center;gap:8px;background:#f59e0b;color:#fff;padding:8px 16px;border-radius:8px;font-size:14px;font-weight:600;text-decoration:none;">
            <i class="fas fa-user-tie"></i> Nouveau commissionnaire
        </a>
        <a href="{{ route('super-admin.plans.create') }}"
           class="flex items-center gap-2 bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700 transition">
            <i class="fas fa-plus"></i> Nouveau plan
        </a>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
    @foreach($plans as $plan)
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <div class="flex items-start justify-between mb-3">
            <div>
                <h3 class="font-semibold text-gray-800">{{ $plan->name }}</h3>
                <p class="text-xs text-gray-400 font-mono">{{ $plan->slug }}</p>
            </div>
            <span class="bg-blue-100 text-blue-700 text-xs font-bold px-2 py-0.5 rounded-full">
                {{ $plan->tenants_count ?? 0 }} boutiques
            </span>
        </div>

        <div class="mb-4">
            @if($plan->monthly_price == 0)
                <span class="text-xl font-bold text-gray-900">Gratuit</span>
            @elseif($plan->monthly_price < 0)
                <span class="text-xl font-bold text-gray-900">Sur devis</span>
            @else
                <span class="text-xl font-bold text-gray-900">{{ number_format($plan->monthly_price, 0, ',', ' ') }}</span>
                <span class="text-xs text-gray-400"> FCFA/mois</span>
            @endif
        </div>

        <ul class="space-y-1.5 text-xs text-gray-600 mb-4">
            <li><i class="fas fa-building text-gray-400 w-3 mr-1"></i>
                {{ $plan->max_branches == -1 ? '∞' : $plan->max_branches }} succursales
            </li>
            <li><i class="fas fa-box text-gray-400 w-3 mr-1"></i>
                {{ $plan->max_articles == -1 ? '∞' : number_format($plan->max_articles, 0) }} articles
            </li>
            <li><i class="fas fa-users text-gray-400 w-3 mr-1"></i>
                {{ $plan->max_users == -1 ? '∞' : $plan->max_users }} utilisateurs
            </li>
        </ul>

        <div class="flex gap-2">
            <a href="{{ route('super-admin.plans.edit', $plan) }}"
               class="flex-1 text-center border border-gray-200 text-gray-600 py-1.5 rounded-lg text-xs hover:bg-gray-50">
                <i class="fas fa-pen mr-1"></i>Modifier
            </a>
        </div>
    </div>
    @endforeach
</div>
@endsection
