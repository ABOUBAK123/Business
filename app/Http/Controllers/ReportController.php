<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\ArticleBranchStock;
use App\Models\Branch;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function sales(Request $request)
    {
        $branchIds = $this->getBranchIds(auth()->user());
        $from = $request->date_from ?? now()->startOfMonth()->toDateString();
        $to = $request->date_to ?? now()->toDateString();

        if ($request->branch_id) {
            $branchIds = array_intersect($branchIds, [$request->branch_id]);
        }

        $query = Sale::with(['branch', 'user', 'customer'])
            ->whereIn('branch_id', $branchIds)
            ->whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59']);

        $sales = (clone $query)->latest()->paginate(30)->withQueryString();

        $totals = (clone $query)->selectRaw(
            'COALESCE(SUM(total_ttc), 0) as total, COUNT(*) as count,
             COALESCE(SUM(tax_amount), 0) as tax, COALESCE(AVG(total_ttc), 0) as avg'
        )->first();

        $salesByDay = (clone $query)
            ->selectRaw('DATE(created_at) as date, SUM(total_ttc) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $maxDay = $salesByDay->max('total') ?: 1;

        $byPaymentMethod = DB::table('sales')
            ->join('sale_items', 'sales.id', '=', 'sale_items.sale_id')
            ->whereIn('sales.branch_id', $branchIds)
            ->whereBetween('sales.created_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->selectRaw("JSON_UNQUOTE(JSON_EXTRACT(sales.payment_methods, '$[0].method')) as method, SUM(sales.total_ttc) as total")
            ->groupBy('method')
            ->orderByDesc('total')
            ->get();

        $summary = [
            'total_revenue'     => $totals->total ?? 0,
            'transaction_count' => $totals->count ?? 0,
            'avg_basket'        => $totals->avg ?? 0,
            'total_tax'         => $totals->tax ?? 0,
            'max_day'           => $maxDay,
        ];

        $branches = Branch::whereIn('id', $this->getBranchIds(auth()->user()))->get();

        return view('reports.sales', compact('sales', 'summary', 'salesByDay', 'byPaymentMethod', 'branches'));
    }

    public function stock(Request $request)
    {
        $allBranchIds = $this->getBranchIds(auth()->user());
        $allBranches  = Branch::whereIn('id', $allBranchIds)->get();

        // Narrow to selected branch if requested
        $branchIds = ($request->branch_id && in_array((int)$request->branch_id, $allBranchIds))
            ? [(int)$request->branch_id]
            : $allBranchIds;
        $branches = $allBranches->whereIn('id', $branchIds)->values();

        $query = Article::with(['branchStocks', 'category'])->where('is_active', true);

        if ($request->status === 'low') {
            $query->whereHas('branchStocks', fn($q) => $q
                ->whereIn('branch_id', $branchIds)
                ->whereRaw('quantity <= (SELECT stock_min FROM articles a WHERE a.id = article_branch_stock.article_id)')
                ->where('quantity', '>', 0)
            );
        } elseif ($request->status === 'out') {
            $query->whereHas('branchStocks', fn($q) => $q
                ->whereIn('branch_id', $branchIds)
                ->where('quantity', 0)
            );
        }

        $articles = $query->paginate(50)->withQueryString();

        $allArticles = Article::with('branchStocks')->where('is_active', true)->get();

        $summary = [
            'total_articles'     => $allArticles->count(),
            'stock_value'        => $allArticles->sum(fn($a) => $a->branchStocks->sum('quantity') * $a->sale_price_ht),
            'low_stock_count'    => $allArticles->filter(fn($a) => $a->branchStocks->sum('quantity') > 0 && $a->branchStocks->sum('quantity') <= $a->stock_min)->count(),
            'out_of_stock_count' => $allArticles->filter(fn($a) => $a->branchStocks->sum('quantity') == 0)->count(),
        ];

        return view('reports.stock', compact('articles', 'summary', 'branches', 'allBranches'));
    }

    public function topArticles(Request $request)
    {
        $branchIds = $this->getBranchIds(auth()->user());
        $from   = $request->date_from ?? now()->startOfMonth()->toDateString();
        $to     = $request->date_to ?? now()->toDateString();
        $limit  = min((int)($request->limit ?? 10), 50);

        if ($request->branch_id) {
            $branchIds = array_intersect($branchIds, [$request->branch_id]);
        }

        $base = SaleItem::join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('articles', 'articles.id', '=', 'sale_items.article_id')
            ->whereIn('sales.branch_id', $branchIds)
            ->whereBetween('sales.created_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->selectRaw('
                sale_items.article_id,
                sale_items.designation,
                articles.reference,
                articles.unit,
                articles.profit_margin,
                SUM(sale_items.quantity) as total_qty,
                SUM(sale_items.total_ttc) as total_revenue
            ')
            ->groupBy('sale_items.article_id', 'sale_items.designation', 'articles.reference', 'articles.unit', 'articles.profit_margin');

        $topByQuantity = (clone $base)->orderByDesc('total_qty')->take($limit)->get();
        $topByRevenue  = (clone $base)->orderByDesc('total_revenue')->take($limit)->get();

        $branches = Branch::whereIn('id', $this->getBranchIds(auth()->user()))->get();

        return view('reports.top-articles', compact('topByQuantity', 'topByRevenue', 'branches'));
    }

    private function getBranchIds($user): array
    {
        if ($user->hasRole(['proprietaire', 'admin_boutique', 'comptable'])) {
            return Branch::pluck('id')->toArray();
        }
        return $user->branch_id ? [$user->branch_id] : [];
    }
}
