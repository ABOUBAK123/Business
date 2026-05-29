@extends('layouts.app')
@section('title', 'Succursales')
@section('page-title', 'Gestion des succursales')

@section('content')
<div class="flex items-center justify-between mb-4">
    <p class="text-sm text-gray-500">{{ $branches->count() }} succursale(s)</p>
    @can('manage_branches')
    <a href="{{ route('branches.create') }}"
       class="flex items-center gap-2 bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700">
        <i class="fas fa-plus"></i> Nouvelle succursale
    </a>
    @endcan
</div>

@if(session('success'))
<div class="bg-green-50 border border-green-200 text-green-700 rounded-lg p-3 mb-4 text-sm">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="bg-red-50 border border-red-200 text-red-700 rounded-lg p-3 mb-4 text-sm">{{ session('error') }}</div>
@endif

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
    @forelse($branches as $branch)
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <div class="flex items-start justify-between mb-3">
            <div>
                <div class="flex items-center gap-2">
                    <h3 class="font-semibold text-gray-800">{{ $branch->name }}</h3>
                    @if($branch->is_main)
                        <span class="bg-blue-100 text-blue-700 text-xs px-2 py-0.5 rounded-full">Principale</span>
                    @endif
                </div>
                @if($branch->address)
                    <p class="text-xs text-gray-400 mt-0.5">{{ $branch->address }}</p>
                @endif
            </div>
            <span class="w-2.5 h-2.5 rounded-full mt-1 {{ $branch->is_active ? 'bg-green-500' : 'bg-gray-300' }}"></span>
        </div>

        <div class="space-y-1.5 text-sm text-gray-600 mb-4">
            @if($branch->phone)
            <div class="flex items-center gap-2">
                <i class="fas fa-phone text-gray-400 w-4"></i>
                <span>{{ $branch->phone }}</span>
            </div>
            @endif
            @if($branch->manager)
            <div class="flex items-center gap-2">
                <i class="fas fa-user-tie text-gray-400 w-4"></i>
                <span>{{ $branch->manager->name }}</span>
            </div>
            @endif
            <div class="flex items-center gap-2">
                <i class="fas fa-shopping-cart text-gray-400 w-4"></i>
                <span>{{ $branch->today_sales ?? 0 }} vente(s) aujourd'hui</span>
            </div>
        </div>

        @can('manage_branches')
        <div class="flex gap-2 pt-3 border-t border-gray-50">
            <a href="{{ route('branches.edit', $branch) }}"
               class="flex-1 text-center border border-gray-200 text-gray-600 py-1.5 rounded-lg text-xs hover:bg-gray-50">
                <i class="fas fa-pen mr-1"></i>Modifier
            </a>
            {{-- Delete not enabled in current plan --}}
        </div>
        @endcan
    </div>
    @empty
    <div class="col-span-3 text-center py-16 text-gray-400">
        <i class="fas fa-building text-4xl mb-3 block"></i>
        <p>Aucune succursale</p>
    </div>
    @endforelse
</div>
@endsection
