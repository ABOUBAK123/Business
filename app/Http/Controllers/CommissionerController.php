<?php

namespace App\Http\Controllers;

use App\Models\Commission;
use App\Models\CommissionPayout;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CommissionerController extends Controller
{
    public function dashboard(): View
    {
        $user = auth()->user();

        $shops       = $user->commissionedTenants()->withoutTrashed()->get();
        $totalShops  = $shops->count();
        $activeShops = $shops->where('status', 'active')->count();

        $totalEarned  = $user->commissions()->sum('amount');
        $pendingEarned = $user->commissions()->where('status', 'pending')->sum('amount');
        $paidEarned   = $user->commissions()->where('status', 'paid')->sum('amount');
        $availableBalance = $user->availableCommissionBalance();

        $recentShops = $user->commissionedTenants()
            ->withoutTrashed()
            ->with('plan')
            ->latest()
            ->take(5)
            ->get();

        return view('commissioner.dashboard', compact(
            'totalShops', 'activeShops', 'totalEarned', 'pendingEarned', 'paidEarned', 'recentShops', 'availableBalance'
        ));
    }

    public function shops(): View
    {
        $shops = auth()->user()->commissionedTenants()
            ->withoutTrashed()
            ->with('plan')
            ->latest()
            ->paginate(20);

        return view('commissioner.shops.index', compact('shops'));
    }

    public function createShop(): View
    {
        $plans = SubscriptionPlan::where('is_active', true)->orderBy('sort_order')->get();
        return view('commissioner.shops.create', compact('plans'));
    }

    public function storeShop(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'shop_name'           => 'required|string|max:255',
            'owner_name'          => 'required|string|max:255',
            'owner_email'         => 'required|email|unique:users,email',
            'owner_password'      => 'required|string|min:8',
            'subscription_plan_id'=> 'required|exists:subscription_plans,id',
            'phone'               => 'nullable|string|max:30',
            'address'             => 'nullable|string|max:255',
            'city'                => 'nullable|string|max:100',
            'country'             => 'nullable|string|max:100',
            'currency'            => 'nullable|string|max:10',
        ]);

        DB::transaction(function () use ($validated) {
            $plan = SubscriptionPlan::findOrFail($validated['subscription_plan_id']);

            $tenant = Tenant::create([
                'subscription_plan_id' => $plan->id,
                'commissioner_id'      => auth()->id(),
                'shop_name'            => $validated['shop_name'],
                'slug'                 => Str::slug($validated['shop_name']) . '-' . Str::random(5),
                'phone'                => $validated['phone'] ?? null,
                'address'              => $validated['address'] ?? null,
                'city'                 => $validated['city'] ?? null,
                'country'              => $validated['country'] ?? 'Bénin',
                'currency'             => $validated['currency'] ?? 'XOF',
                'status'               => 'trial',
                'trial_ends_at'        => now()->addDays($plan->trial_days ?? 14),
            ]);

            $owner = User::create([
                'tenant_id' => $tenant->id,
                'name'      => $validated['owner_name'],
                'email'     => $validated['owner_email'],
                'password'  => Hash::make($validated['owner_password']),
                'is_active' => true,
            ]);

            $tenant->update(['owner_id' => $owner->id]);
            $owner->assignRole('proprietaire');
        });

        return redirect()->route('commissioner.shops')->with('success', 'Boutique créée avec succès.');
    }

    public function commissions(Request $request): View
    {
        $user = auth()->user();

        $query = $user->commissions()->with('tenant');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('period')) {
            $query->where('period', $request->period);
        }

        $commissions  = $query->latest()->paginate(20);
        $totalPending = $user->commissions()->where('status', 'pending')->sum('amount');
        $totalPaid    = $user->commissions()->where('status', 'paid')->sum('amount');

        return view('commissioner.commissions.index', compact('commissions', 'totalPending', 'totalPaid'));
    }

    public function payouts(): View
    {
        $user = auth()->user();

        $payouts = $user->commissionPayouts()->latest()->paginate(20);
        $availableBalance = $user->availableCommissionBalance();

        return view('commissioner.payouts.index', compact('payouts', 'availableBalance'));
    }

    public function createPayout(): View|RedirectResponse
    {
        $user = auth()->user();
        $availableBalance = $user->availableCommissionBalance();

        if ($availableBalance <= 0) {
            return redirect()->route('commissioner.payouts')
                ->with('error', 'Aucun solde disponible pour un retrait pour le moment.');
        }

        return view('commissioner.payouts.create', compact('availableBalance'));
    }

    public function storePayout(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'method' => 'required|in:orange_money,mtn_momo,wave,moov_money',
            'phone_number' => 'required|string|max:30',
            'commissioner_notes' => 'nullable|string|max:255',
        ]);

        $user = auth()->user();

        $payout = DB::transaction(function () use ($validated, $user) {
            $commissions = $user->commissions()
                ->where('status', 'pending')
                ->whereNull('commission_payout_id')
                ->lockForUpdate()
                ->get();

            $amount = (float) $commissions->sum('amount');

            if ($amount <= 0) {
                abort(422, 'Aucun solde disponible pour un retrait.');
            }

            $payout = CommissionPayout::create([
                'commissioner_id' => $user->id,
                'amount' => $amount,
                'method' => $validated['method'],
                'phone_number' => $validated['phone_number'],
                'status' => 'pending',
                'commissioner_notes' => $validated['commissioner_notes'] ?? null,
            ]);

            Commission::whereIn('id', $commissions->pluck('id'))
                ->update(['commission_payout_id' => $payout->id]);

            return $payout;
        });

        return redirect()->route('commissioner.payouts')
            ->with('success', 'Demande de retrait de ' . number_format($payout->amount, 0, ',', ' ') . ' XOF envoyée.');
    }
}
