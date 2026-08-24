@extends('layouts.app')

@section('title', 'Retraits commissionnaires')
@section('page-title', 'Demandes de retrait')

@section('content')
<div class="flex items-center justify-between mb-4 flex-wrap gap-2">
    <form class="flex gap-2">
        <select name="status" onchange="this.form.submit()"
                class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
            <option value="">Tous les statuts</option>
            <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>En attente</option>
            <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Payé</option>
            <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejeté</option>
        </select>
    </form>
    @if($pendingCount > 0)
        <span class="text-xs px-2.5 py-1 rounded-full bg-yellow-100 text-yellow-700 font-medium">
            {{ $pendingCount }} demande(s) en attente
        </span>
    @endif
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Date</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Commissionnaire</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Montant</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Moyen / Numéro</th>
                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Statut</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @forelse($payouts as $payout)
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3 text-gray-600">{{ $payout->created_at->format('d/m/Y H:i') }}</td>
                <td class="px-4 py-3">
                    <p class="font-medium text-gray-800">{{ $payout->commissioner?->name }}</p>
                    <p class="text-xs text-gray-400">{{ $payout->commissioner?->email }}</p>
                </td>
                <td class="px-4 py-3 text-right font-semibold text-gray-800">{{ number_format($payout->amount, 0, ',', ' ') }} XOF</td>
                <td class="px-4 py-3 text-gray-600">
                    {{ ucfirst(str_replace('_', ' ', $payout->method)) }}
                    <div class="text-xs text-gray-400">{{ $payout->phone_number }}</div>
                </td>
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
                <td class="px-4 py-3">
                    @if($payout->isPending())
                        <div class="flex items-center justify-end gap-2">
                            <button type="button"
                                    onclick="document.getElementById('pay-{{ $payout->id }}').classList.toggle('hidden')"
                                    class="text-green-600 hover:text-green-700 text-xs font-medium px-2 py-1 rounded-lg hover:bg-green-50">
                                <i class="fas fa-check-circle"></i> Marquer payé
                            </button>
                            <button type="button"
                                    onclick="document.getElementById('reject-{{ $payout->id }}').classList.toggle('hidden')"
                                    class="text-red-600 hover:text-red-700 text-xs font-medium px-2 py-1 rounded-lg hover:bg-red-50">
                                <i class="fas fa-times-circle"></i> Rejeter
                            </button>
                        </div>
                        <form id="pay-{{ $payout->id }}" method="POST"
                              action="{{ route('super-admin.commission-payouts.mark-paid', $payout) }}"
                              class="hidden mt-2 flex gap-2">
                            @csrf
                            <input type="text" name="admin_notes" placeholder="Référence de paiement (optionnel)"
                                   class="flex-1 border border-gray-200 rounded-lg px-2 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-green-500">
                            <button type="submit" class="bg-green-600 text-white text-xs font-semibold px-3 py-1.5 rounded-lg hover:bg-green-700">
                                Confirmer
                            </button>
                        </form>
                        <form id="reject-{{ $payout->id }}" method="POST"
                              action="{{ route('super-admin.commission-payouts.reject', $payout) }}"
                              class="hidden mt-2 flex gap-2">
                            @csrf
                            <input type="text" name="admin_notes" required placeholder="Raison du rejet"
                                   class="flex-1 border border-gray-200 rounded-lg px-2 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-red-500">
                            <button type="submit" class="bg-red-600 text-white text-xs font-semibold px-3 py-1.5 rounded-lg hover:bg-red-700">
                                Confirmer
                            </button>
                        </form>
                    @else
                        <p class="text-xs text-gray-400 text-right">{{ $payout->admin_notes ?? '—' }}</p>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="px-4 py-12 text-center text-gray-400">
                    <i class="fas fa-money-bill-wave text-3xl mb-2 block"></i>Aucune demande de retrait.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    @if($payouts->hasPages())
        <div class="px-4 py-3 border-t border-gray-100">{{ $payouts->links() }}</div>
    @endif
</div>
@endsection
