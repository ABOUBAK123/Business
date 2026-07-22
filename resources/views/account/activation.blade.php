@extends('layouts.app')
@section('title', 'Activation de compte')
@section('page-title', 'Activation de compte')

@section('content')
<div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
    <div class="xl:col-span-2 space-y-4">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-start justify-between gap-4 flex-wrap">
                <div>
                    <h2 class="text-xl font-bold text-gray-900">Réactiver mon compte</h2>
                    <p class="text-sm text-gray-500 mt-1">Choisissez un nouveau forfait et confirmez le paiement pour réactiver votre boutique.</p>
                </div>
                <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold {{ $tenant->status === 'active' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                    <i class="fas fa-circle"></i> {{ ucfirst($tenant->status) }}
                </span>
            </div>
        </div>

        @if(session('success'))
            <div class="bg-green-50 border border-green-200 text-green-700 rounded-lg px-4 py-3 text-sm">{{ session('success') }}</div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach($plans as $plan)
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 flex flex-col">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h3 class="font-semibold text-gray-800">{{ $plan->name }}</h3>
                            <p class="text-xs text-gray-400 mt-1">{{ $plan->description ?? 'Forfait adapté pour la réactivation' }}</p>
                        </div>
                        @if($tenant->subscription_plan_id === $plan->id)
                            <span class="text-[11px] bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full font-semibold">Plan actuel</span>
                        @endif
                    </div>

                    <div class="mt-4 mb-4">
                        <div class="text-2xl font-bold text-gray-900">{{ number_format($plan->monthly_price, 0, ',', ' ') }} <span class="text-sm font-medium text-gray-400">FCFA/mois</span></div>
                        <div class="text-sm text-gray-500">{{ number_format($plan->annual_price, 0, ',', ' ') }} FCFA/an</div>
                    </div>

                    <ul class="space-y-1.5 text-sm text-gray-600 flex-1">
                        <li>Succursales: {{ $plan->max_branches == -1 ? 'illimitées' : $plan->max_branches }}</li>
                        <li>Articles: {{ $plan->max_articles == -1 ? 'illimités' : number_format($plan->max_articles, 0) }}</li>
                        <li>Utilisateurs: {{ $plan->max_users == -1 ? 'illimités' : $plan->max_users }}</li>
                    </ul>

                    <form method="POST" action="{{ route('account.activation.store') }}" class="mt-5 space-y-3 activation-form" data-monthly-date="{{ now()->addMonth()->format('d/m/Y') }}" data-annual-date="{{ now()->addYear()->format('d/m/Y') }}">
                        @csrf
                        <input type="hidden" name="plan_id" value="{{ $plan->id }}">

                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Cycle de paiement</label>
                            <select name="billing_cycle" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                                <option value="monthly">Mensuel</option>
                                <option value="annual">Annuel</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Méthode de paiement</label>
                            <select name="payment_method" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500" required>
                                <option value="cash">Espèces</option>
                                <option value="mobile_money">Mobile Money</option>
                                <option value="card">Carte bancaire</option>
                                <option value="bank_transfer">Virement bancaire</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Référence paiement (optionnel)</label>
                            <input type="text" name="payment_reference" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500" placeholder="N° transaction, reçu, etc.">
                        </div>

                        <div class="rounded-lg bg-gray-50 border border-gray-100 px-3 py-2 text-xs text-gray-600">
                            Prochaine échéance: <span class="font-semibold text-blue-700 activation-date">{{ now()->addMonth()->format('d/m/Y') }}</span>
                        </div>

                        <button type="submit" class="w-full bg-blue-600 text-white py-2.5 rounded-lg text-sm font-semibold hover:bg-blue-700">
                            Réactiver avec ce forfait
                        </button>
                    </form>
                </div>
            @endforeach
        </div>
    </div>

    <div class="space-y-4">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <h3 class="text-sm font-semibold text-gray-700 mb-3">Boutique</h3>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between"><span class="text-gray-500">Nom</span><span class="font-medium">{{ $tenant->shop_name }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Statut</span><span class="font-medium capitalize">{{ $tenant->status }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Plan actuel</span><span class="font-medium">{{ $currentPlan?->name ?? '—' }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Expiration actuelle</span><span class="font-medium">{{ $tenant->subscription_ends_at?->format('d/m/Y') ?? '—' }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Réactivation prochaine</span><span class="font-medium text-blue-700">{{ now()->addMonth()->format('d/m/Y') }}</span></div>
            </div>
        </div>

        <div class="bg-blue-50 border border-blue-100 rounded-xl p-5 text-sm text-blue-900">
            <p class="font-semibold mb-2">Important</p>
            <p>La boutique sera réactivée dès validation du nouveau forfait. La date affichée correspond à la prochaine échéance si vous choisissez le cycle mensuel.</p>
        </div>
    </div>
</div>
@push('scripts')
<script>
document.querySelectorAll('.activation-form').forEach(form => {
    const cycleSelect = form.querySelector('select[name="billing_cycle"]');
    const dateLabel = form.querySelector('.activation-date');

    const refreshDate = () => {
        const nextDate = cycleSelect.value === 'annual'
            ? form.dataset.annualDate
            : form.dataset.monthlyDate;

        dateLabel.textContent = nextDate;
    };

    cycleSelect.addEventListener('change', refreshDate);
    refreshDate();
});
</script>
@endpush
@endsection
