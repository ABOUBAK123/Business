<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TenantRegistrationController extends Controller
{
    public function showPlans()
    {
        $plans = SubscriptionPlan::where('is_active', true)->orderBy('sort_order')->get();
        return view('register.plans', compact('plans'));
    }

    public function showForm(Request $request)
    {
        $plan = SubscriptionPlan::where('slug', $request->plan ?? 'decouverte')->firstOrFail();
        return view('register.form', compact('plan'));
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:191',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8|confirmed',
            'phone' => 'nullable|string|max:20',
            'shop_name' => 'required|string|max:191',
            'city' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:10',
            'plan_id' => 'required|exists:subscription_plans,id',
        ]);

        DB::transaction(function () use ($request) {
            $plan = SubscriptionPlan::findOrFail($request->plan_id);

            $tenant = Tenant::create([
                'subscription_plan_id' => $plan->id,
                'shop_name' => $request->shop_name,
                'slug' => Str::slug($request->shop_name) . '-' . Str::random(4),
                'city' => $request->city,
                'country' => $request->country ?? 'CI',
                'status' => $plan->trial_days > 0 ? 'trial' : 'active',
                'trial_ends_at' => $plan->trial_days > 0 ? now()->addDays($plan->trial_days) : null,
                'subscription_ends_at' => $plan->trial_days > 0 ? now()->addDays($plan->trial_days) : now()->addMonth(),
            ]);

            $owner = User::create([
                'tenant_id' => $tenant->id,
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'phone' => $request->phone,
                'is_active' => true,
                'email_verified_at' => now(),
            ]);

            $owner->assignRole('proprietaire');
            $tenant->update(['owner_id' => $owner->id]);

            $branch = Branch::create([
                'tenant_id' => $tenant->id,
                'name' => $request->shop_name . ' (Principal)',
                'city' => $request->city,
                'is_main' => true,
                'is_active' => true,
                'manager_id' => $owner->id,
            ]);

            $owner->update(['branch_id' => $branch->id]);

            if ($plan->trial_days === 0) {
                Subscription::create([
                    'tenant_id' => $tenant->id,
                    'subscription_plan_id' => $plan->id,
                    'billing_cycle' => 'monthly',
                    'amount_paid' => $plan->monthly_price,
                    'status' => 'active',
                    'starts_at' => now(),
                    'ends_at' => now()->addMonth(),
                ]);
            }

            auth()->login($owner);
        });

        return redirect()->route('dashboard')->with('success', 'Bienvenue ! Votre boutique est prête.');
    }
}
