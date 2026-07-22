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

        @if($errors->has('payment'))
            <div class="bg-red-50 border border-red-200 text-red-700 rounded-lg px-4 py-3 text-sm">{{ $errors->first('payment') }}</div>
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

                    <div class="mt-5 space-y-3">
                        <div class="rounded-lg bg-gray-50 border border-gray-100 px-3 py-2 text-xs text-gray-600">
                            Prochaine échéance: <span class="font-semibold text-blue-700 activation-date">{{ now()->addMonth()->format('d/m/Y') }}</span>
                        </div>

                        <button type="button"
                                class="w-full bg-blue-600 text-white py-2.5 rounded-lg text-sm font-semibold hover:bg-blue-700 flex items-center justify-center gap-2 open-payment-modal"
                                data-plan-id="{{ $plan->id }}"
                                data-plan-name="{{ $plan->name }}"
                                data-monthly-price="{{ (float) $plan->monthly_price }}"
                                data-annual-price="{{ (float) $plan->annual_price }}"
                                data-monthly-date="{{ now()->addMonth()->format('d/m/Y') }}"
                                data-annual-date="{{ now()->addYear()->format('d/m/Y') }}">
                            <i class="fas fa-mobile-alt"></i> Paiement en ligne / Mobile Money
                        </button>
                    </div>
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

<div id="paymentModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 px-4">
    <div class="w-full max-w-lg bg-white rounded-2xl shadow-xl overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <div>
                <h3 class="font-bold text-gray-900">Paiement en ligne / Mobile Money</h3>
                <p class="text-xs text-gray-500">Confirmez le paiement pour réactiver le compte.</p>
            </div>
            <button type="button" id="closePaymentModal" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form id="paymentModalForm" method="POST" action="{{ route('account.activation.store') }}" class="p-5 space-y-4">
            @csrf
            <input type="hidden" name="plan_id" id="modalPlanId">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Forfait</label>
                    <input type="text" id="modalPlanName" disabled class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm bg-gray-50">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Cycle</label>
                    <select name="billing_cycle" id="modalBillingCycle" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                        <option value="monthly">Mensuel</option>
                        <option value="annual">Annuel</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div class="rounded-xl bg-blue-50 border border-blue-100 p-4">
                    <p class="text-xs text-blue-700 font-medium">Montant mensuel</p>
                    <p class="text-2xl font-bold text-blue-900" id="modalMonthlyPrice">0 FCFA</p>
                </div>
                <div class="rounded-xl bg-indigo-50 border border-indigo-100 p-4">
                    <p class="text-xs text-indigo-700 font-medium">Montant annuel</p>
                    <p class="text-2xl font-bold text-indigo-900" id="modalAnnualPrice">0 FCFA</p>
                </div>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-2">Moyen de paiement</label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    @foreach($paymentMethods as $key => $method)
                        <label class="cursor-pointer rounded-xl border border-gray-200 p-3 transition hover:border-blue-400 hover:bg-blue-50/40">
                            <input type="radio" name="payment_method" value="{{ $key }}" class="sr-only peer" {{ $loop->first ? 'checked' : '' }}>
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <div class="text-sm font-semibold text-gray-800">{{ $method['label'] }}</div>
                                    <div class="text-xs text-gray-500 mt-0.5">{{ $method['description'] }}</div>
                                </div>
                                <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-1 text-[11px] font-semibold text-gray-700">{{ $method['badge'] }}</span>
                            </div>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-900">
                Choisissez l’un des moyens de paiement configurés dans le panneau Super Admin.
            </div>

            <div class="rounded-lg bg-gray-50 border border-gray-100 px-3 py-2 text-xs text-gray-600">
                Prochaine échéance: <span class="font-semibold text-blue-700" id="modalNextDate">—</span>
            </div>

            <button type="submit" class="w-full bg-blue-600 text-white py-2.5 rounded-lg text-sm font-semibold hover:bg-blue-700">
                Continuer vers le paiement sécurisé
            </button>
        </form>
    </div>
</div>

@push('scripts')
<script>
const paymentModal = document.getElementById('paymentModal');
const paymentModalForm = document.getElementById('paymentModalForm');
const modalPlanId = document.getElementById('modalPlanId');
const modalPlanName = document.getElementById('modalPlanName');
const modalBillingCycle = document.getElementById('modalBillingCycle');
const modalMonthlyPrice = document.getElementById('modalMonthlyPrice');
const modalAnnualPrice = document.getElementById('modalAnnualPrice');
const modalNextDate = document.getElementById('modalNextDate');
let activeButtonData = null;

function formatCurrency(amount) {
    return Math.round(amount).toLocaleString('fr-FR') + ' FCFA';
}

function refreshModalPricing() {
    if (!activeButtonData) return;

    modalMonthlyPrice.textContent = formatCurrency(activeButtonData.monthlyPrice);
    modalAnnualPrice.textContent = formatCurrency(activeButtonData.annualPrice);
    modalNextDate.textContent = modalBillingCycle.value === 'annual'
        ? activeButtonData.annualDate
        : activeButtonData.monthlyDate;
}

document.querySelectorAll('.open-payment-modal').forEach(button => {
    button.addEventListener('click', () => {
        activeButtonData = {
            planId: button.dataset.planId,
            planName: button.dataset.planName,
            monthlyPrice: parseFloat(button.dataset.monthlyPrice || '0'),
            annualPrice: parseFloat(button.dataset.annualPrice || '0'),
            monthlyDate: button.dataset.monthlyDate,
            annualDate: button.dataset.annualDate,
        };

        modalPlanId.value = activeButtonData.planId;
        modalPlanName.value = activeButtonData.planName;
        modalBillingCycle.value = 'monthly';
        refreshModalPricing();
        paymentModal.classList.remove('hidden');
        paymentModal.classList.add('flex');
    });
});

modalBillingCycle.addEventListener('change', refreshModalPricing);

document.getElementById('closePaymentModal').addEventListener('click', () => {
    paymentModal.classList.add('hidden');
    paymentModal.classList.remove('flex');
});

paymentModal.addEventListener('click', (event) => {
    if (event.target === paymentModal) {
        paymentModal.classList.add('hidden');
        paymentModal.classList.remove('flex');
    }
});

paymentModalForm.addEventListener('submit', () => {
    document.getElementById('closePaymentModal').disabled = true;
});
</script>
@endpush
@endsection
