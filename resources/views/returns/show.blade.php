@extends('layouts.app')
@section('title', $return->return_number)
@section('page-title', 'Retour — ' . $return->return_number)

@section('content')
@php
    $modes = ['cash'=>['Remboursement espèces','bg-green-100 text-green-700'],
              'credit'=>['Avoir client','bg-blue-100 text-blue-700'],
              'exchange'=>['Échange','bg-purple-100 text-purple-700']];
    [$modeLabel, $modeCls] = $modes[$return->refund_method] ?? [$return->refund_method,'bg-gray-100 text-gray-600'];
@endphp

<div class="max-w-2xl mx-auto space-y-4">

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex justify-between items-start mb-5">
            <div>
                <h2 class="text-xl font-bold text-gray-800 font-mono">{{ $return->return_number }}</h2>
                <p class="text-sm text-gray-500">{{ $return->created_at->format('d/m/Y à H:i') }}</p>
            </div>
            <span class="text-xs font-semibold px-3 py-1 rounded-full {{ $modeCls }}">{{ $modeLabel }}</span>
        </div>

        <div class="grid grid-cols-2 gap-3 text-sm mb-5">
            <div><p class="text-xs text-gray-400">Boutique</p><p class="font-medium">{{ $return->branch?->name }}</p></div>
            <div><p class="text-xs text-gray-400">Traité par</p><p class="font-medium">{{ $return->user?->name }}</p></div>
            @if($return->sale)
            <div><p class="text-xs text-gray-400">Facture d'origine</p>
                <a href="{{ route('sales.show', $return->sale) }}" class="font-mono text-blue-600 hover:underline text-xs">{{ $return->sale->invoice_number }}</a>
            </div>
            @endif
            @if($return->customer)
            <div><p class="text-xs text-gray-400">Client</p>
                <a href="{{ route('customers.show', $return->customer) }}" class="font-medium text-blue-600 hover:underline">{{ $return->customer->name }}</a>
            </div>
            @endif
            <div class="col-span-2"><p class="text-xs text-gray-400">Raison</p><p class="font-medium">{{ $return->reason }}</p></div>
            @if($return->notes)
            <div class="col-span-2"><p class="text-xs text-gray-400">Notes</p><p class="text-gray-600">{{ $return->notes }}</p></div>
            @endif
        </div>

        <h3 class="text-sm font-semibold text-gray-700 mb-2 border-t pt-3">Articles retournés</h3>
        <table class="w-full text-sm mb-4">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-3 py-2 text-left text-xs text-gray-500">Article</th>
                    <th class="px-3 py-2 text-right text-xs text-gray-500">Qté</th>
                    <th class="px-3 py-2 text-right text-xs text-gray-500">Prix unit.</th>
                    <th class="px-3 py-2 text-right text-xs text-gray-500">Total</th>
                    <th class="px-3 py-2 text-center text-xs text-gray-500">Stock</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($return->items as $item)
                <tr>
                    <td class="px-3 py-2 font-medium">{{ $item->designation }}</td>
                    <td class="px-3 py-2 text-right">{{ number_format($item->quantity, 0) }} {{ $item->unit }}</td>
                    <td class="px-3 py-2 text-right text-gray-600">{{ number_format($item->unit_price, 0, ',', ' ') }}</td>
                    <td class="px-3 py-2 text-right font-semibold">{{ number_format($item->total, 0, ',', ' ') }}</td>
                    <td class="px-3 py-2 text-center">
                        @if($item->restock)
                            <span class="text-xs bg-green-100 text-green-700 px-1.5 py-0.5 rounded">Remis</span>
                        @else
                            <span class="text-xs bg-gray-100 text-gray-500 px-1.5 py-0.5 rounded">Non remis</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot class="bg-gray-50 border-t-2 border-gray-200">
                <tr>
                    <td colspan="3" class="px-3 py-2 font-bold text-gray-700">Total remboursé</td>
                    <td class="px-3 py-2 text-right font-bold text-blue-700 text-base">
                        {{ number_format($return->total_amount, 0, ',', ' ') }} FCFA
                    </td>
                    <td></td>
                </tr>
            </tfoot>
        </table>

        <div class="flex gap-3">
            <a href="{{ route('returns.index') }}"
               class="flex-1 text-center border border-gray-200 text-gray-600 px-4 py-2 rounded-lg text-sm hover:bg-gray-50">
                <i class="fas fa-arrow-left mr-1"></i> Retour liste
            </a>
            <button onclick="window.print()"
                    class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700">
                <i class="fas fa-print mr-1"></i> Imprimer
            </button>
        </div>
    </div>
</div>
@endsection
