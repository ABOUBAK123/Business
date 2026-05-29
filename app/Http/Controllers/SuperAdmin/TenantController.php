<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;

class TenantController extends Controller
{
    public function index(Request $request)
    {
        $query = Tenant::withoutGlobalScopes()->with(['plan', 'owner']);

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('shop_name', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%");
            });
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->plan_id) {
            $query->where('subscription_plan_id', $request->plan_id);
        }

        $tenants = $query->latest()->paginate(20)->withQueryString();
        $plans = SubscriptionPlan::orderBy('monthly_price')->get();

        return view('super-admin.tenants.index', compact('tenants', 'plans'));
    }

    public function show(int $id)
    {
        $tenant = Tenant::withoutGlobalScopes()->with(['plan', 'owner', 'branches', 'users', 'articles', 'subscriptions.plan'])->findOrFail($id);
        $plans = SubscriptionPlan::orderBy('monthly_price')->get();
        return view('super-admin.tenants.show', compact('tenant', 'plans'));
    }

    public function toggleStatus(Request $request, int $id)
    {
        $tenant = Tenant::withoutGlobalScopes()->findOrFail($id);
        $tenant->status = $tenant->status === 'active' ? 'suspended' : 'active';
        $tenant->save();

        $msg = $tenant->status === 'active' ? 'activée' : 'suspendue';
        return back()->with('success', "Boutique {$msg} avec succès.");
    }

    public function changePlan(Request $request, int $id)
    {
        $request->validate(['plan_id' => 'required|exists:subscription_plans,id']);
        $tenant = Tenant::withoutGlobalScopes()->findOrFail($id);
        $tenant->update(['subscription_plan_id' => $request->plan_id]);

        return back()->with('success', 'Plan modifié avec succès.');
    }
}
