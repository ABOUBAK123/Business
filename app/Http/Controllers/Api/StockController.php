<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\ArticleBranchStock;
use App\Models\Branch;
use App\Models\StockMovement;
use Illuminate\Http\Request;

class StockController extends Controller
{
    public function index(Request $request)
    {
        $query = Article::with(['branchStocks.branch', 'category'])
            ->where('is_active', true);

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

        $articles = $query->orderBy('designation')->paginate(25);

        $articles->getCollection()->transform(function (Article $a) {
            return [
                'id'           => $a->id,
                'reference'    => $a->reference,
                'designation'  => $a->designation,
                'unit'         => $a->unit,
                'stock_min'    => $a->stock_min,
                'total_stock'  => $a->branchStocks->sum('quantity'),
                'category'     => $a->category?->name,
                'branch_stocks'=> $a->branchStocks->map(fn($bs) => [
                    'branch_id'   => $bs->branch_id,
                    'branch_name' => $bs->branch?->name,
                    'quantity'    => $bs->quantity,
                    'is_low'      => $bs->quantity <= $a->stock_min,
                    'is_out'      => $bs->quantity === 0,
                ]),
            ];
        });

        return response()->json($articles);
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
            'notes'        => $data['notes'] ?? 'Approvisionnement via mobile',
        ]);

        $branch = Branch::find($data['branch_id']);

        return response()->json([
            'message'      => "Stock approvisionné : +{$data['quantity']} {$article->unit}",
            'article_id'   => $article->id,
            'designation'  => $article->designation,
            'branch'       => $branch?->name,
            'stock_before' => $before,
            'stock_after'  => $before + $data['quantity'],
        ]);
    }
}
