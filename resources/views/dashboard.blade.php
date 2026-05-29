@extends('layouts.app')
@section('title', 'Tableau de bord')
@section('page-title', 'Tableau de bord')

@section('content')
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100">
        <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-medium text-gray-500 uppercase tracking-wider">Ventes du jour</span>
            <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-shopping-cart text-blue-600 text-xs"></i>
            </div>
        </div>
        <p class="text-2xl font-bold text-gray-900">{{ number_format($todaySales, 0, ',', ' ') }}</p>
        <p class="text-xs text-gray-400 mt-1">FCFA aujourd'hui</p>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100">
        <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-medium text-gray-500 uppercase tracking-wider">CA du mois</span>
            <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-chart-line text-green-600 text-xs"></i>
            </div>
        </div>
        <p class="text-2xl font-bold text-gray-900">{{ number_format($monthSales, 0, ',', ' ') }}</p>
        <p class="text-xs text-gray-400 mt-1">FCFA ce mois</p>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100">
        <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-medium text-gray-500 uppercase tracking-wider">Transactions</span>
            <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-receipt text-purple-600 text-xs"></i>
            </div>
        </div>
        <p class="text-2xl font-bold text-gray-900">{{ $todayTransactions }}</p>
        <p class="text-xs text-gray-400 mt-1">ventes aujourd'hui</p>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100">
        <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-medium text-gray-500 uppercase tracking-wider">Stock bas</span>
            <div class="w-8 h-8 {{ $lowStockArticles > 0 ? 'bg-red-100' : 'bg-gray-100' }} rounded-lg flex items-center justify-center">
                <i class="fas fa-exclamation-triangle {{ $lowStockArticles > 0 ? 'text-red-500' : 'text-gray-400' }} text-xs"></i>
            </div>
        </div>
        <p class="text-2xl font-bold {{ $lowStockArticles > 0 ? 'text-red-600' : 'text-gray-900' }}">{{ $lowStockArticles }}</p>
        <p class="text-xs text-gray-400 mt-1">articles en alerte</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <h2 class="text-sm font-semibold text-gray-700 mb-4">Ventes du mois (FCFA)</h2>
        <div class="h-44 flex items-end gap-1">
            @php $maxVal = $salesByDay->max('total') ?: 1; @endphp
            @forelse($salesByDay as $day)
                <div class="flex-1 flex flex-col items-center gap-1">
                    <div class="w-full bg-blue-500 hover:bg-blue-600 rounded-t transition-all"
                         style="height: {{ max(4, ($day->total / $maxVal) * 160) }}px"
                         title="{{ $day->date }}: {{ number_format($day->total,0,',',' ') }} FCFA"></div>
                    <span class="text-gray-400 text-xs">{{ \Carbon\Carbon::parse($day->date)->format('d') }}</span>
                </div>
            @empty
                <div class="w-full flex items-center justify-center text-gray-400 text-sm">
                    <div class="text-center"><i class="fas fa-chart-bar text-3xl mb-2 block"></i>Aucune vente ce mois</div>
                </div>
            @endforelse
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <h2 class="text-sm font-semibold text-gray-700 mb-4">Top articles du mois</h2>
        @forelse($topArticles as $i => $item)
            <div class="flex items-center gap-3 mb-3">
                <span class="w-6 h-6 rounded-full bg-blue-100 text-blue-600 text-xs font-bold flex items-center justify-center flex-shrink-0">{{ $i+1 }}</span>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-700 truncate">{{ $item->designation }}</p>
                    <p class="text-xs text-gray-400">{{ $item->total_qty }} unités</p>
                </div>
                <span class="text-sm font-semibold text-gray-700 flex-shrink-0">{{ number_format($item->total_amount,0) }}</span>
            </div>
        @empty
            <div class="text-center text-gray-400 py-6"><i class="fas fa-box-open text-2xl mb-2 block"></i>Aucune vente</div>
        @endforelse
    </div>
</div>

<div class="mt-4 flex gap-3 flex-wrap">
    <a href="{{ route('sales.create') }}" class="flex items-center gap-2 bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700">
        <i class="fas fa-cash-register"></i> Nouvelle vente
    </a>
    <a href="{{ route('articles.create') }}" class="flex items-center gap-2 bg-white text-gray-700 border border-gray-200 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-50">
        <i class="fas fa-plus"></i> Ajouter un article
    </a>
    <a href="{{ route('reports.sales') }}" class="flex items-center gap-2 bg-white text-gray-700 border border-gray-200 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-50">
        <i class="fas fa-chart-bar"></i> Rapports
    </a>
</div>
@endsection
