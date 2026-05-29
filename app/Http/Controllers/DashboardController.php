<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\Tenant;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->is_super_admin) {
            return $this->superAdminDashboard();
        }

        return $this->tenantDashboard($user);
    }

    private function superAdminDashboard()
    {
        $byPlan = \App\Models\SubscriptionPlan::withCount([
            'tenants' => fn($q) => $q->withoutGlobalScopes()
        ])->get();

        $stats = [
            'total_tenants'     => Tenant::withoutGlobalScopes()->count(),
            'active_tenants'    => Tenant::withoutGlobalScopes()->where('status', 'active')->count(),
            'trial_tenants'     => Tenant::withoutGlobalScopes()->where('status', 'trial')->count(),
            'suspended_tenants' => Tenant::withoutGlobalScopes()->whereIn('status', ['suspended', 'expired'])->count(),
            'total_users'       => \App\Models\User::withoutGlobalScopes()->where('is_super_admin', false)->count(),
            'by_plan'           => $byPlan,
            'recent_tenants'    => Tenant::withoutGlobalScopes()->with('plan')->latest()->take(8)->get(),
        ];

        return view('super-admin.dashboard', compact('stats'));
    }

    private function tenantDashboard($user)
    {
        $tenant = app('currentTenant');
        $branches = Branch::when(!$user->hasRole(['proprietaire', 'admin_boutique', 'comptable']),
            fn($q) => $q->where('id', $user->branch_id)
        )->get();

        $branchIds = $branches->pluck('id');

        $todaySales = Sale::whereIn('branch_id', $branchIds)
            ->whereDate('created_at', today())
            ->sum('total_ttc');

        $monthSales = Sale::whereIn('branch_id', $branchIds)
            ->whereMonth('created_at', now()->month)
            ->sum('total_ttc');

        $todayTransactions = Sale::whereIn('branch_id', $branchIds)
            ->whereDate('created_at', today())
            ->count();

        $lowStockArticles = Article::whereHas('branchStocks', function ($q) use ($branchIds) {
            $q->whereIn('branch_id', $branchIds)
              ->whereRaw('quantity <= (SELECT stock_min FROM articles WHERE articles.id = article_branch_stock.article_id)');
        })->count();

        $topArticles = Sale::join('sale_items', 'sales.id', '=', 'sale_items.sale_id')
            ->whereIn('sales.branch_id', $branchIds)
            ->whereMonth('sales.created_at', now()->month)
            ->groupBy('sale_items.article_id', 'sale_items.designation')
            ->selectRaw('sale_items.article_id, sale_items.designation, SUM(sale_items.quantity) as total_qty, SUM(sale_items.total_ttc) as total_amount')
            ->orderByDesc('total_qty')
            ->take(5)
            ->get();

        $salesByDay = Sale::whereIn('branch_id', $branchIds)
            ->whereMonth('created_at', now()->month)
            ->selectRaw('DATE(created_at) as date, SUM(total_ttc) as total, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return view('dashboard', compact(
            'tenant', 'branches', 'todaySales', 'monthSales',
            'todayTransactions', 'lowStockArticles', 'topArticles', 'salesByDay'
        ));
    }
}
