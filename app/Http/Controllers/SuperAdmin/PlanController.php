<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;

class PlanController extends Controller
{
    public function index()
    {
        $plans = SubscriptionPlan::orderBy('sort_order')->get();
        return view('super-admin.plans.index', compact('plans'));
    }

    public function create()
    {
        return view('super-admin.plans.form', ['plan' => new SubscriptionPlan()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'                       => 'required|string|max:100',
            'slug'                       => 'required|string|unique:subscription_plans,slug',
            'description'                => 'nullable|string',
            'monthly_price'              => 'required|numeric',
            'annual_price'               => 'required|numeric|min:0',
            'max_branches'               => 'required|integer|min:-1',
            'max_articles'               => 'required|integer|min:-1',
            'max_users'                  => 'required|integer|min:-1',
            'max_transactions_per_month' => 'required|integer|min:-1',
            'has_advanced_reports'       => 'boolean',
            'has_api_access'             => 'boolean',
            'has_priority_support'       => 'boolean',
            'trial_days'                 => 'nullable|integer|min:0',
            'sort_order'                 => 'nullable|integer|min:0',
        ]);

        $data['has_advanced_reports'] = $request->boolean('has_advanced_reports');
        $data['has_api_access']       = $request->boolean('has_api_access');
        $data['has_priority_support'] = $request->boolean('has_priority_support');
        $data['trial_days']           = $data['trial_days'] ?? 0;
        $data['sort_order']           = $data['sort_order'] ?? 0;

        SubscriptionPlan::create($data);
        return redirect()->route('super-admin.plans.index')->with('success', 'Plan créé avec succès.');
    }

    public function edit(SubscriptionPlan $plan)
    {
        return view('super-admin.plans.form', compact('plan'));
    }

    public function update(Request $request, SubscriptionPlan $plan)
    {
        $data = $request->validate([
            'name'                       => 'required|string|max:100',
            'description'                => 'nullable|string',
            'monthly_price'              => 'required|numeric',
            'annual_price'               => 'required|numeric|min:0',
            'max_branches'               => 'required|integer|min:-1',
            'max_articles'               => 'required|integer|min:-1',
            'max_users'                  => 'required|integer|min:-1',
            'max_transactions_per_month' => 'required|integer|min:-1',
            'has_advanced_reports'       => 'boolean',
            'has_api_access'             => 'boolean',
            'has_priority_support'       => 'boolean',
            'trial_days'                 => 'nullable|integer|min:0',
            'sort_order'                 => 'nullable|integer|min:0',
        ]);

        $data['has_advanced_reports'] = $request->boolean('has_advanced_reports');
        $data['has_api_access']       = $request->boolean('has_api_access');
        $data['has_priority_support'] = $request->boolean('has_priority_support');
        $data['trial_days']           = $data['trial_days'] ?? 0;
        $data['sort_order']           = $data['sort_order'] ?? 0;

        $plan->update($data);
        return redirect()->route('super-admin.plans.index')->with('success', 'Plan modifié avec succès.');
    }
}
