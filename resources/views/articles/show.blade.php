@extends('layouts.app')
@section('title', $article->designation)
@section('page-title', $article->designation)

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <div class="lg:col-span-2 space-y-4">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <span class="font-mono text-xs text-gray-400">{{ $article->reference }}</span>
                    <h2 class="text-xl font-bold text-gray-800">{{ $article->designation }}</h2>
                    @if($article->category) <span class="bg-blue-100 text-blue-700 text-xs px-2 py-0.5 rounded-full">{{ $article->category->name }}</span> @endif
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('articles.edit', $article) }}" class="flex items-center gap-2 border border-gray-200 text-gray-600 px-3 py-1.5 rounded-lg text-sm hover:bg-gray-50">
                        <i class="fas fa-pen"></i> Modifier
                    </a>
                </div>
            </div>
            <div class="grid grid-cols-3 gap-4 p-4 bg-blue-50 rounded-xl">
                <div class="text-center">
                    <p class="text-xs text-blue-600 font-medium">Prix HT</p>
                    <p class="text-lg font-bold text-blue-800">{{ number_format($article->sale_price_ht, 0) }}</p>
                </div>
                <div class="text-center border-x border-blue-100">
                    <p class="text-xs text-blue-600 font-medium">TVA {{ $article->tax_rate }}%</p>
                    <p class="text-lg font-bold text-blue-800">{{ number_format($article->sale_price_ttc - $article->sale_price_ht, 0) }}</p>
                </div>
                <div class="text-center">
                    <p class="text-xs text-blue-600 font-medium">Prix TTC</p>
                    <p class="text-xl font-bold text-blue-900">{{ number_format($article->sale_price_ttc, 0, ',', ' ') }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <h3 class="text-sm font-semibold text-gray-700 mb-3">Stock par succursale</h3>
            <div class="space-y-2">
                @foreach($article->branchStocks as $bs)
                    <div class="flex items-center justify-between py-2 border-b border-gray-50 last:border-0">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-building text-gray-400 w-4"></i>
                            <span class="text-sm text-gray-700">{{ $bs->branch->name }}</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="text-lg font-bold {{ $bs->quantity <= $article->stock_min ? 'text-red-600' : 'text-gray-800' }}">
                                {{ $bs->quantity }}
                            </span>
                            <span class="text-xs text-gray-400">{{ $article->unit }}</span>
                            @if($bs->quantity <= $article->stock_min)
                                <span class="bg-red-100 text-red-600 text-xs px-2 py-0.5 rounded-full">Stock bas</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="space-y-4">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 text-center">
            <h3 class="text-sm font-semibold text-gray-700 mb-3">QR Code</h3>
            <div class="inline-block p-3 bg-white border-2 border-gray-100 rounded-xl">
                {!! $qrImage !!}
            </div>
            <div class="mt-3 flex gap-2 justify-center">
                <a href="{{ route('articles.qr', $article) }}" target="_blank"
                   class="flex items-center gap-1 bg-blue-600 text-white px-3 py-1.5 rounded-lg text-xs font-medium hover:bg-blue-700">
                    <i class="fas fa-print"></i> Imprimer
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
