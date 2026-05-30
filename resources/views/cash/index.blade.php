@extends('layouts.app')
@section('title', 'Clôtures de caisse')
@section('page-title', 'Clôtures de caisse')

@section('content')

@if(session('success'))
<div class="bg-green-50 border border-green-200 text-green-700 rounded-lg p-3 mb-4 text-sm flex items-center gap-2">
    <i class="fas fa-check-circle"></i> {{ session('success') }}
</div>
@endif

<div class="flex items-center justify-between mb-4">
    <p class="text-sm text-gray-500">Récapitulatifs journaliers de caisse</p>
    <a href="{{ route('cash.create') }}"
       class="flex items-center gap-2 bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700">
        <i class="fas fa-cash-register"></i> Nouvelle clôture
    </a>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Date</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Boutique</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Ventes</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">CA total</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Espèces théoriques</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Espèces comptées</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Écart</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @forelse($closings as $c)
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3 font-medium text-gray-800">{{ $c->date->format('d/m/Y') }}</td>
                <td class="px-4 py-3 text-gray-600">{{ $c->branch?->name }}</td>
                <td class="px-4 py-3 text-right text-gray-600">{{ $c->sales_count }}</td>
                <td class="px-4 py-3 text-right font-semibold text-blue-700">{{ number_format($c->total_sales, 0, ',', ' ') }}</td>
                <td class="px-4 py-3 text-right text-gray-700">{{ number_format($c->theoretical_cash, 0, ',', ' ') }}</td>
                <td class="px-4 py-3 text-right text-gray-700">{{ number_format($c->closing_cash, 0, ',', ' ') }}</td>
                <td class="px-4 py-3 text-right font-bold {{ $c->cash_gap == 0 ? 'text-green-600' : ($c->cash_gap > 0 ? 'text-blue-600' : 'text-red-600') }}">
                    {{ $c->cash_gap >= 0 ? '+' : '' }}{{ number_format($c->cash_gap, 0, ',', ' ') }}
                </td>
                <td class="px-4 py-3 text-right">
                    <a href="{{ route('cash.show', $c) }}" class="text-gray-400 hover:text-blue-600"><i class="fas fa-eye"></i></a>
                </td>
            </tr>
            @empty
            <tr><td colspan="8" class="px-4 py-12 text-center text-gray-400">
                <i class="fas fa-cash-register text-3xl mb-2 block"></i>Aucune clôture enregistrée
            </td></tr>
            @endforelse
        </tbody>
    </table>
    @if($closings->hasPages())
        <div class="px-4 py-3 border-t border-gray-100">{{ $closings->links() }}</div>
    @endif
</div>
@endsection
