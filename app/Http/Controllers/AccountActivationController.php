<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionPlan;
use App\Models\Setting;
use App\Models\Tenant;
use App\Services\Payments\CinetPayGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AccountActivationController extends Controller
{
    public function index(Request $request)
    {
        $tenant = $this->resolveTenant($request);

        abort_unless($tenant && $request->user()?->hasRole('proprietaire'), 403);

        $plans = SubscriptionPlan::where('is_active', true)->orderBy('sort_order')->get();
        $paymentMethods = $this->availablePaymentMethods();

        return view('account.activation', [
            'tenant' => $tenant,
            'plans' => $plans,
            'currentPlan' => $tenant->plan,
            'paymentMethods' => $paymentMethods,
        ]);
    }

    public function store(Request $request, CinetPayGateway $gateway)
    {
        $tenant = $this->resolveTenant($request);

        abort_unless($tenant && $request->user()?->hasRole('proprietaire'), 403);

        $data = $request->validate([
            'plan_id' => 'required|exists:subscription_plans,id',
            'billing_cycle' => 'required|in:monthly,annual',
            'payment_method' => ['required', Rule::in(array_keys($this->availablePaymentMethods()))],
        ]);

        $plan = SubscriptionPlan::findOrFail($data['plan_id']);
        $price = $data['billing_cycle'] === 'annual'
            ? (float) $plan->annual_price
            : (float) $plan->monthly_price;

        $startsAt = now();
        $endsAt = $data['billing_cycle'] === 'annual'
            ? now()->addYear()
            : now()->addMonth();

        $subscription = null;
        $payment = null;

        DB::transaction(function () use ($tenant, $plan, $price, $startsAt, $endsAt, $data, &$subscription, &$payment) {
            $subscription = Subscription::create([
                'tenant_id' => $tenant->id,
                'subscription_plan_id' => $plan->id,
                'billing_cycle' => $data['billing_cycle'],
                'amount_paid' => $price,
                'status' => 'grace',
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'grace_ends_at' => null,
                'payment_method' => 'mobile_money',
                'payment_reference' => null,
            ]);

            $payment = SubscriptionPayment::create([
                'tenant_id' => $tenant->id,
                'subscription_id' => $subscription->id,
                'amount' => $price,
                'currency' => 'XOF',
                'method' => $data['payment_method'],
                'provider' => 'cinetpay',
                'reference' => (string) Str::uuid(),
                'status' => 'pending',
                'metadata' => [
                    'plan_id' => $plan->id,
                    'billing_cycle' => $data['billing_cycle'],
                    'payment_method' => $data['payment_method'],
                ],
                'paid_at' => null,
            ]);
        });

        $initiation = $gateway->initiate($payment);

        if (! ($initiation['success'] ?? false)) {
            $payment->update([
                'status' => 'failed',
                'metadata' => array_merge($payment->metadata ?? [], [
                    'initiation_error' => $initiation['message'] ?? 'unknown',
                ]),
            ]);

            $subscription->update([
                'status' => 'cancelled',
            ]);

            return back()->withInput()->withErrors([
                'payment' => $initiation['message'] ?? 'Le paiement en ligne a échoué.',
            ]);
        }

        return redirect()->away($initiation['payment_url'])
            ->with('success', 'Redirection vers le prestataire de paiement...');
    }

    public function cinetpayReturn(Request $request, CinetPayGateway $gateway)
    {
        return $this->syncGatewayPayment($request, $gateway, true);
    }

    public function cinetpayNotify(Request $request, CinetPayGateway $gateway)
    {
        return $this->syncGatewayPayment($request, $gateway, false);
    }

    private function syncGatewayPayment(Request $request, CinetPayGateway $gateway, bool $redirectToActivation)
    {
        $reference = $request->input('transaction_id')
            ?? $request->input('cpm_trans_id')
            ?? $request->input('cpm_trans_id_number');

        if (! $reference) {
            return $redirectToActivation
                ? redirect()->route('account.activation.index')->withErrors(['payment' => 'Transaction introuvable.'])
                : response('Transaction introuvable', 422);
        }

        $payment = SubscriptionPayment::where('reference', $reference)->first();

        if (! $payment) {
            return $redirectToActivation
                ? redirect()->route('account.activation.index')->withErrors(['payment' => 'Paiement introuvable.'])
                : response('Paiement introuvable', 404);
        }

        $verification = $gateway->verify($payment);
        $status = strtoupper((string) ($verification['status'] ?? ''));

        $payment->update([
            'metadata' => array_merge($payment->metadata ?? [], [
                'verification_response' => $verification['raw'] ?? [],
            ]),
        ]);

        if (! ($verification['success'] ?? false) || ! in_array($status, ['ACCEPTED', 'SUCCESS', 'COMPLETED', 'PAID'], true)) {
            $payment->update([
                'status' => 'failed',
            ]);

            return $redirectToActivation
                ? redirect()->route('account.activation.index')->withErrors(['payment' => 'Le paiement n’a pas été confirmé.'])
                : response('Payment not confirmed', 200);
        }

        $subscription = $payment->subscription;
        $tenant = $payment->tenant;

        DB::transaction(function () use ($payment, $subscription, $tenant) {
            $payment->update([
                'status' => 'success',
                'paid_at' => now(),
            ]);

            $subscription->update([
                'status' => 'active',
                'payment_method' => $payment->method,
                'payment_reference' => $payment->reference,
            ]);

            $tenant->update([
                'subscription_plan_id' => $subscription->subscription_plan_id,
                'status' => 'active',
                'subscription_ends_at' => $subscription->ends_at,
                'trial_ends_at' => null,
            ]);
        });

        if (! $redirectToActivation) {
            return response('OK', 200);
        }

        return redirect()->route('account.activation.index')->with('success', 'Paiement confirmé et compte activé.');
    }

    private function resolveTenant(Request $request): ?Tenant
    {
        if (app()->bound('currentTenant')) {
            return app('currentTenant');
        }

        $user = $request->user();

        if (!$user?->tenant_id) {
            return null;
        }

        return Tenant::withoutGlobalScopes()->with('plan')->find($user->tenant_id);
    }

    private function availablePaymentMethods(): array
    {
        $methods = [
            'orange_money' => [
                'label' => 'Orange Money',
                'description' => 'Paiement via Orange Money',
                'enabled' => Setting::get('orange_money_enabled', '1') === '1',
                'badge' => 'Orange',
            ],
            'mtn_momo' => [
                'label' => 'MTN Mobile Money',
                'description' => 'Paiement via MTN MoMo',
                'enabled' => Setting::get('mtn_momo_enabled', '1') === '1',
                'badge' => 'MTN',
            ],
            'wave' => [
                'label' => 'Wave',
                'description' => 'Paiement via Wave',
                'enabled' => Setting::get('wave_enabled', '1') === '1',
                'badge' => 'Wave',
            ],
            'moov_money' => [
                'label' => 'Moov Money',
                'description' => 'Paiement via Moov Money',
                'enabled' => Setting::get('moov_money_enabled', '1') === '1',
                'badge' => 'Moov',
            ],
        ];

        $enabled = array_filter($methods, static fn (array $method) => $method['enabled']);

        return $enabled !== [] ? $enabled : $methods;
    }
}
