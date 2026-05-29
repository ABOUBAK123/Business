<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\ArticleBranchStock;
use App\Models\Branch;
use App\Models\Category;
use App\Models\StockMovement;
use Illuminate\Http\Request;

class StockManagementController extends Controller
{
    public function index(Request $request)
    {
        $branches = Branch::where('is_active', true)->get();

        if ($branches->isEmpty()) {
            $tenant   = app('currentTenant');
            $default  = Branch::create([
                'tenant_id' => $tenant->id,
                'name'      => $tenant->shop_name ?? 'Boutique principale',
                'is_main'   => true,
                'is_active' => true,
            ]);
            Article::where('is_active', true)->each(function ($a) use ($default) {
                ArticleBranchStock::firstOrCreate(
                    ['article_id' => $a->id, 'branch_id' => $default->id],
                    ['quantity' => 0]
                );
            });
            $branches = collect([$default]);
        }

        $categories = Category::where('is_active', true)->get();

        $query = Article::with(['branchStocks', 'category'])->where('is_active', true);

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('designation', 'like', "%{$request->search}%")
                  ->orWhere('reference', 'like', "%{$request->search}%");
            });
        }

        if ($request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->status === 'low') {
            $query->whereHas('branchStocks', fn($q) => $q
                ->whereRaw('quantity <= (SELECT stock_min FROM articles a WHERE a.id = article_branch_stock.article_id)')
                ->where('quantity', '>', 0)
            );
        } elseif ($request->status === 'out') {
            $query->whereHas('branchStocks', fn($q) => $q->where('quantity', 0));
        }

        $articles = $query->orderBy('designation')->paginate(25)->withQueryString();

        return view('stock.index', compact('articles', 'branches', 'categories'));
    }

    public function replenish(Request $request, Article $article)
    {
        $data = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'quantity'  => 'required|integer|min:1',
            'notes'     => 'nullable|string|max:255',
        ]);

        $stock = ArticleBranchStock::firstOrCreate(
            ['article_id' => $article->id, 'branch_id' => $data['branch_id']],
            ['quantity' => 0]
        );

        $before = $stock->quantity;
        $stock->increment('quantity', $data['quantity']);

        StockMovement::create([
            'tenant_id'    => app('currentTenant')->id,
            'branch_id'    => $data['branch_id'],
            'article_id'   => $article->id,
            'user_id'      => auth()->id(),
            'type'         => 'in',
            'quantity'     => $data['quantity'],
            'stock_before' => $before,
            'stock_after'  => $before + $data['quantity'],
            'notes'        => $data['notes'] ?? 'Approvisionnement',
        ]);

        return back()->with('success', "Stock de « {$article->designation} » approvisionné : +{$data['quantity']} {$article->unit}.");
    }
}
