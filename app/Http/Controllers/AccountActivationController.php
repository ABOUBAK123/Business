<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccountActivationController extends Controller
{
    public function index(Request $request)
    {
        $tenant = $this->resolveTenant($request);

        abort_unless($tenant && $request->user()?->hasRole('proprietaire'), 403);

        $plans = SubscriptionPlan::where('is_active', true)->orderBy('sort_order')->get();

        return view('account.activation', [
            'tenant' => $tenant,
            'plans' => $plans,
            'currentPlan' => $tenant->plan,
        ]);
    }

    public function store(Request $request)
    {
        $tenant = $this->resolveTenant($request);

        abort_unless($tenant && $request->user()?->hasRole('proprietaire'), 403);

        $data = $request->validate([
            'plan_id' => 'required|exists:subscription_plans,id',
            'billing_cycle' => 'required|in:monthly,annual',
            'payment_method' => 'required|in:cash,mobile_money,card,bank_transfer',
            'payment_reference' => 'nullable|string|max:191',
        ]);

        $plan = SubscriptionPlan::findOrFail($data['plan_id']);
        $price = $data['billing_cycle'] === 'annual'
            ? (float) $plan->annual_price
            : (float) $plan->monthly_price;

        $startsAt = now();
        $endsAt = $data['billing_cycle'] === 'annual'
            ? now()->addYear()
            : now()->addMonth();

        DB::transaction(function () use ($tenant, $plan, $data, $price, $startsAt, $endsAt) {
            $subscription = Subscription::create([
                'tenant_id' => $tenant->id,
                'subscription_plan_id' => $plan->id,
                'billing_cycle' => $data['billing_cycle'],
                'amount_paid' => $price,
                'status' => 'active',
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'grace_ends_at' => null,
                'payment_method' => $data['payment_method'],
                'payment_reference' => $data['payment_reference'] ?? null,
            ]);

            SubscriptionPayment::create([
                'tenant_id' => $tenant->id,
                'subscription_id' => $subscription->id,
                'amount' => $price,
                'currency' => 'XOF',
                'method' => $data['payment_method'],
                'provider' => null,
                'reference' => $data['payment_reference'] ?? null,
                'status' => 'success',
                'paid_at' => now(),
            ]);

            $tenant->update([
                'subscription_plan_id' => $plan->id,
                'status' => 'active',
                'subscription_ends_at' => $endsAt,
            ]);
        });

        return redirect()
            ->route('account.activation.index')
            ->with('success', 'Compte réactivé avec succès. Prochaine échéance: ' . $endsAt->format('d/m/Y'));
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
}
