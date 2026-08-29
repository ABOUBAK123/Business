@extends('layouts.app')

@section('title', 'Paiements')
@section('page-title', 'Historique des paiements')

@section('content')

{{-- ── Statistiques ─────────────────────────────────────────────────── --}}
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
        <p class="text-xs text-gray-400 uppercase font-semibold">Réussis</p>
        <p class="text-2xl font-bold text-green-600 mt-1">{{ $stats['count_success'] }}</p>
        <p class="text-xs text-gray-500 mt-1">{{ number_format($stats['total_success'], 0, ',', ' ') }} FCFA encaissés</p>
    </div>
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
        <p class="text-xs text-gray-400 uppercase font-semibold">En attente</p>
        <p class="text-2xl font-bold text-yellow-600 mt-1">{{ $stats['count_pending'] }}</p>
    </div>
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
        <p class="text-xs text-gray-400 uppercase font-semibold">Échoués</p>
        <p class="text-2xl font-bold text-red-600 mt-1">{{ $stats['count_failed'] }}</p>
    </div>
</div>

{{-- ── Filtres ───────────────────────────────────────────────────────── --}}
<form class="flex items-center gap-2 mb-4 flex-wrap">
    <input type="text" name="search" value="{{ request('search') }}" placeholder="Boutique, référence..."
           class="border border-gray-200 rounded-lg px-3 py-2 text-sm w-56 focus:outline-none focus:ring-2 focus:ring-blue-500">
    <select name="status" class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
        <option value="">Tous les statuts</option>
        <option value="success" {{ request('status') === 'success' ? 'selected' : '' }}>Réussi</option>
        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>En attente</option>
        <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Échoué</option>
    </select>
    <select name="provider" class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
        <option value="">Tous les prestataires</option>
        @foreach($providers as $provider)
            <option value="{{ $provider }}" {{ request('provider') === $provider ? 'selected' : '' }}>
                {{ ucfirst(str_replace('_', ' ', $provider)) }}
            </option>
        @endforeach
    </select>
    <input type="date" name="date_from" value="{{ request('date_from') }}"
           class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
    <input type="date" name="date_to" value="{{ request('date_to') }}"
           class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
    <button class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700">Filtrer</button>
    @if(request()->anyFilled(['search', 'status', 'provider', 'date_from', 'date_to']))
        <a href="{{ route('super-admin.payments.index') }}" class="text-gray-400 hover:text-gray-600 text-sm px-2">
            Réinitialiser
        </a>
    @endif
</form>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Date</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Boutique</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Montant</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Prestataire</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Référence</th>
                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Statut</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @forelse($payments as $payment)
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3 text-gray-600 whitespace-nowrap">{{ $payment->created_at->format('d/m/Y H:i') }}</td>
                <td class="px-4 py-3">
                    <p class="font-medium text-gray-800">{{ $payment->tenant?->shop_name ?? '—' }}</p>
                    @if($payment->subscription?->plan)
                        <p class="text-xs text-gray-400">{{ $payment->subscription->plan->name }}</p>
                    @endif
                </td>
                <td class="px-4 py-3 text-right font-semibold text-gray-800 whitespace-nowrap">
                    {{ number_format($payment->amount, 0, ',', ' ') }} {{ $payment->currency }}
                </td>
                <td class="px-4 py-3 text-gray-600">
                    <span class="inline-flex items-center gap-1.5">
                        <i class="fas fa-{{ match($payment->provider) {
                            'mtn_momo' => 'mobile-alt',
                            'wave' => 'water',
                            'cinetpay' => 'credit-card',
                            default => 'money-bill',
                        } }} text-gray-400"></i>
                        {{ ucfirst(str_replace('_', ' ', $payment->provider ?? $payment->method)) }}
                    </span>
                </td>
                <td class="px-4 py-3 text-gray-500 text-xs font-mono">{{ $payment->reference ?? '—' }}</td>
                <td class="px-4 py-3 text-center">
                    <span class="text-xs font-medium px-2 py-0.5 rounded-full
                        {{ match($payment->status) {
                            'success' => 'bg-green-100 text-green-700',
                            'failed' => 'bg-red-100 text-red-700',
                            default => 'bg-yellow-100 text-yellow-700',
                        } }}">
                        {{ match($payment->status) {
                            'success' => 'Réussi',
                            'failed' => 'Échoué',
                            default => 'En attente',
                        } }}
                    </span>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="px-4 py-12 text-center text-gray-400">
                    <i class="fas fa-credit-card text-3xl mb-2 block"></i>Aucun paiement trouvé.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    @if($payments->hasPages())
        <div class="px-4 py-3 border-t border-gray-100">{{ $payments->links() }}</div>
    @endif
</div>
@endsection
