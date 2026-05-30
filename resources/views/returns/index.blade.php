@extends('layouts.app')
@section('title', 'Retours & avoirs')
@section('page-title', 'Retours & avoirs')

@section('content')

@if(session('success'))
<div class="bg-green-50 border border-green-200 text-green-700 rounded-lg p-3 mb-4 text-sm flex items-center gap-2">
    <i class="fas fa-check-circle"></i> {{ session('success') }}
</div>
@endif

<div class="flex items-center justify-between mb-4">
    <p class="text-sm text-gray-500">Articles retournés, remboursements et avoirs clients</p>
    <a href="{{ route('returns.create') }}"
       class="flex items-center gap-2 bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700">
        <i class="fas fa-undo"></i> Nouveau retour
    </a>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">N° Retour</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Date</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Facture d'origine</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Client</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Raison</th>
                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Mode</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Montant</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @forelse($returns as $ret)
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3 font-mono text-xs font-semibold text-blue-700">{{ $ret->return_number }}</td>
                <td class="px-4 py-3 text-gray-500">{{ $ret->created_at->format('d/m/Y') }}</td>
                <td class="px-4 py-3 text-gray-600 font-mono text-xs">{{ $ret->sale?->invoice_number ?? '—' }}</td>
                <td class="px-4 py-3 text-gray-600">{{ $ret->customer?->name ?? '—' }}</td>
                <td class="px-4 py-3 text-gray-600 max-w-xs truncate">{{ $ret->reason }}</td>
                <td class="px-4 py-3 text-center">
                    @php
                        $modes = ['cash'=>['Remboursement','bg-green-100 text-green-700'],
                                  'credit'=>['Avoir client','bg-blue-100 text-blue-700'],
                                  'exchange'=>['Échange','bg-purple-100 text-purple-700']];
                        [$label, $cls] = $modes[$ret->refund_method] ?? [$ret->refund_method, 'bg-gray-100 text-gray-600'];
                    @endphp
                    <span class="text-xs px-2 py-0.5 rounded-full {{ $cls }}">{{ $label }}</span>
                </td>
                <td class="px-4 py-3 text-right font-semibold text-gray-800">
                    {{ number_format($ret->total_amount, 0, ',', ' ') }} FCFA
                </td>
                <td class="px-4 py-3 text-right">
                    <a href="{{ route('returns.show', $ret) }}" class="text-gray-400 hover:text-blue-600">
                        <i class="fas fa-eye"></i>
                    </a>
                </td>
            </tr>
            @empty
            <tr><td colspan="8" class="px-4 py-12 text-center text-gray-400">
                <i class="fas fa-undo text-3xl mb-2 block"></i>Aucun retour enregistré
            </td></tr>
            @endforelse
        </tbody>
    </table>
    @if($returns->hasPages())
        <div class="px-4 py-3 border-t">{{ $returns->links() }}</div>
    @endif
</div>
@endsection
