@extends('layouts.app')
@section('title', 'Super Admin')
@section('page-title', 'Tableau de bord Super Admin')

@section('content')
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
        <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-medium text-gray-500 uppercase">Boutiques actives</span>
            <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-store text-blue-600 text-sm"></i>
            </div>
        </div>
        <p class="text-2xl font-bold text-gray-900">{{ $stats['active_tenants'] }}</p>
        <p class="text-xs text-gray-400 mt-1">/ {{ $stats['total_tenants'] }} total</p>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
        <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-medium text-gray-500 uppercase">En essai</span>
            <div class="w-8 h-8 bg-yellow-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-clock text-yellow-600 text-sm"></i>
            </div>
        </div>
        <p class="text-2xl font-bold text-gray-900">{{ $stats['trial_tenants'] }}</p>
        <p class="text-xs text-gray-400 mt-1">boutiques en période d'essai</p>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
        <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-medium text-gray-500 uppercase">Suspendu/Expiré</span>
            <div class="w-8 h-8 bg-red-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-ban text-red-600 text-sm"></i>
            </div>
        </div>
        <p class="text-2xl font-bold text-gray-900">{{ $stats['suspended_tenants'] }}</p>
        <p class="text-xs text-gray-400 mt-1">nécessitent attention</p>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
        <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-medium text-gray-500 uppercase">Utilisateurs</span>
            <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-users text-green-600 text-sm"></i>
            </div>
        </div>
        <p class="text-2xl font-bold text-gray-900">{{ $stats['total_users'] }}</p>
        <p class="text-xs text-gray-400 mt-1">tous tenants confondus</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <h3 class="text-sm font-semibold text-gray-700 mb-4">Répartition par plan</h3>
        <div class="space-y-3">
            @foreach($stats['by_plan'] as $plan)
            <div class="flex items-center justify-between">
                <span class="text-sm text-gray-600">{{ $plan->name }}</span>
                <div class="flex items-center gap-3">
                    <div class="w-32 bg-gray-100 rounded-full h-2">
                        <div class="bg-blue-500 h-2 rounded-full"
                             style="width: {{ $stats['total_tenants'] > 0 ? ($plan->tenants_count / $stats['total_tenants'] * 100) : 0 }}%"></div>
                    </div>
                    <span class="text-sm font-semibold text-gray-800 w-6 text-right">{{ $plan->tenants_count }}</span>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-gray-700">Inscriptions récentes</h3>
            <a href="{{ route('super-admin.tenants.index') }}" class="text-xs text-blue-600 hover:text-blue-800">Voir tout →</a>
        </div>
        <div class="space-y-3">
            @foreach($stats['recent_tenants'] as $tenant)
            <div class="flex items-center justify-between py-2 border-b border-gray-50 last:border-0">
                <div>
                    <p class="text-sm font-medium text-gray-800">{{ $tenant->shop_name }}</p>
                    <p class="text-xs text-gray-400">{{ $tenant->created_at->diffForHumans() }}</p>
                </div>
                <span class="text-xs px-2 py-0.5 rounded-full
                    {{ $tenant->status === 'active' ? 'bg-green-100 text-green-700' :
                       ($tenant->status === 'trial' ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700') }}">
                    {{ $tenant->status }}
                </span>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
