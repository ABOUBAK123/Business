@extends('layouts.app')

@section('title', 'Mes retraits')
@section('page-title', 'Mes retraits')

@section('content')
<div class="bg-white rounded-xl p-5 border border-gray-100 shadow-sm mb-6 flex items-center justify-between">
    <div>
        <p class="text-xs text-gray-500 mb-1">Solde disponible</p>
        <p class="text-2xl font-bold text-green-600">{{ number_format($availableBalance, 0, ',', ' ') }} XOF</p>
    </div>
    @if($availableBalance > 0)
    <a href="{{ route('commissioner.payouts.create') }}"
       class="bg-blue-600 text-white px-4 py-2.5 rounded-lg text-sm font-semibold hover:bg-blue-700 transition flex items-center gap-2">
        <i class="fas fa-money-bill-wave"></i> Demander un retrait
    </a>
    @endif
</div>

<div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Date</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Montant</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Moyen</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Numéro</th>
                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Statut</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Note admin</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @forelse($payouts as $payout)
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3 text-gray-600">{{ $payout->created_at->format('d/m/Y H:i') }}</td>
                <td class="px-4 py-3 text-right font-semibold text-gray-800">{{ number_format($payout->amount, 0, ',', ' ') }} XOF</td>
                <td class="px-4 py-3 text-gray-600">{{ ucfirst(str_replace('_', ' ', $payout->method)) }}</td>
                <td class="px-4 py-3 text-gray-600">{{ $payout->phone_number }}</td>
                <td class="px-4 py-3 text-center">
                    <span class="text-xs font-medium px-2 py-0.5 rounded-full
                        {{ match($payout->status) {
                            'paid' => 'bg-green-100 text-green-700',
                            'rejected' => 'bg-red-100 text-red-700',
                            'approved' => 'bg-blue-100 text-blue-700',
                            default => 'bg-yellow-100 text-yellow-700',
                        } }}">
                        {{ match($payout->status) {
                            'paid' => 'Payé',
                            'rejected' => 'Rejeté',
                            'approved' => 'Approuvé',
                            default => 'En attente',
                        } }}
                    </span>
                </td>
                <td class="px-4 py-3 text-gray-500 text-xs">{{ $payout->admin_notes ?? '—' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="px-4 py-8 text-center text-gray-400">Aucune demande de retrait pour le moment.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    @if($payouts->hasPages())
    <div class="px-4 py-3 border-t border-gray-100">
        {{ $payouts->links() }}
    </div>
    @endif
</div>
@endsection
