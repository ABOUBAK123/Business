<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Branch;
use App\Models\Sale;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $branches = Branch::when(
            !$user->hasRole(['proprietaire', 'admin_boutique', 'comptable']),
            fn($q) => $q->where('id', $user->branch_id)
        )->where('is_active', true)->get();

        $branchIds = $branches->pluck('id');

        $todaySales = Sale::whereIn('branch_id', $branchIds)
            ->whereDate('created_at', today())
            ->sum('total_ttc');

        $todayTransactions = Sale::whereIn('branch_id', $branchIds)
            ->whereDate('created_at', today())
            ->count();

        $lowStockCount = Article::where('is_active', true)
            ->whereHas('branchStocks', function ($q) use ($branchIds) {
                $q->whereIn('branch_id', $branchIds)
                  ->whereRaw('quantity <= (SELECT stock_min FROM articles WHERE articles.id = article_branch_stock.article_id)');
            })->count();

        $totalArticles = Article::where('is_active', true)->count();

        $recentSales = Sale::with(['branch', 'user'])
            ->whereIn('branch_id', $branchIds)
            ->withCount('items')
            ->latest()
            ->take(5)
            ->get()
            ->map(fn($s) => [
                'id'             => $s->id,
                'invoice_number' => $s->invoice_number,
                'total_ttc'      => (float) $s->total_ttc,
                'payment_status' => $s->payment_status,
                'items_count'    => $s->items_count,
                'branch'         => $s->branch?->name,
                'cashier'        => $s->user?->name,
                'created_at'     => $s->created_at->toIso8601String(),
            ]);

        return response()->json([
            'today_sales'       => (float) $todaySales,
            'today_transactions'=> $todayTransactions,
            'low_stock_count'   => $lowStockCount,
            'total_articles'    => $totalArticles,
            'recent_sales'      => $recentSales,
        ]);
    }
}
