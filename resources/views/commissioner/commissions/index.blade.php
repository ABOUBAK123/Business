@extends('layouts.app')

@section('title', 'Mes commissions')
@section('page-title', 'Mes commissions')

@section('content')
<div class="grid grid-cols-2 gap-4 mb-6">
    <div class="bg-white rounded-xl p-5 border border-gray-100 shadow-sm">
        <p class="text-xs text-gray-500 mb-1">En attente</p>
        <p class="text-2xl font-bold text-yellow-600">{{ number_format($totalPending, 0, ',', ' ') }} XOF</p>
    </div>
    <div class="bg-white rounded-xl p-5 border border-gray-100 shadow-sm">
        <p class="text-xs text-gray-500 mb-1">Perçues (payées)</p>
        <p class="text-2xl font-bold text-green-600">{{ number_format($totalPaid, 0, ',', ' ') }} XOF</p>
    </div>
</div>

<div class="bg-white rounded-xl border border-gray-100 shadow-sm">
    <div class="flex flex-wrap items-center gap-3 px-5 py-4 border-b border-gray-100">
        <form method="GET" class="flex gap-2">
            <select name="status" class="border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">Tous les statuts</option>
                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>En attente</option>
                <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Payée</option>
            </select>
            <input type="month" name="period" value="{{ request('period') }}"
                   class="border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            <button type="submit" class="bg-blue-600 text-white px-3 py-1.5 rounded-lg text-sm hover:bg-blue-700 transition">
                Filtrer
            </button>
        </form>
    </div>

    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Boutique</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Période</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Base</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Taux</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Montant</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Statut</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Payée le</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @forelse($commissions as $commission)
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3 font-medium text-gray-800">{{ $commission->tenant?->shop_name ?? '—' }}</td>
                <td class="px-4 py-3 text-gray-600">{{ $commission->period ?? '—' }}</td>
                <td class="px-4 py-3 text-right text-gray-600">{{ number_format($commission->base_amount, 0, ',', ' ') }}</td>
                <td class="px-4 py-3 text-right text-gray-600">{{ $commission->rate }}%</td>
                <td class="px-4 py-3 text-right font-semibold text-gray-800">{{ number_format($commission->amount, 0, ',', ' ') }} XOF</td>
                <td class="px-4 py-3">
                    <span class="text-xs font-medium px-2 py-0.5 rounded-full
                        {{ $commission->status === 'paid' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                        {{ $commission->status === 'paid' ? 'Payée' : 'En attente' }}
                    </span>
                </td>
                <td class="px-4 py-3 text-gray-500 text-xs">
                    {{ $commission->paid_at?->format('d/m/Y') ?? '—' }}
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="px-4 py-8 text-center text-gray-400">Aucune commission trouvée.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    @if($commissions->hasPages())
    <div class="px-4 py-3 border-t border-gray-100">
        {{ $commissions->links() }}
    </div>
    @endif
</div>
@endsection
