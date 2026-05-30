<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Article;
use Illuminate\Http\Request;

class ArticleController extends Controller
{
    public function index(Request $request)
    {
        $query = Article::with(['category', 'branchStocks'])
            ->where('is_active', true);

        if ($request->q) {
            $query->where(function ($q) use ($request) {
                $q->where('designation', 'like', "%{$request->q}%")
                  ->orWhere('reference', 'like', "%{$request->q}%")
                  ->orWhere('marque', 'like', "%{$request->q}%");
            });
        }

        if ($request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        $articles = $query->orderBy('designation')->paginate(20);

        $articles->getCollection()->transform(function (Article $a) {
            return [
                'id'             => $a->id,
                'reference'      => $a->reference,
                'designation'    => $a->designation,
                'marque'         => $a->marque,
                'unit'           => $a->unit,
                'sale_price_ttc' => (float) $a->sale_price_ttc,
                'sale_price_ht'  => (float) $a->sale_price_ht,
                'tax_rate'       => (float) $a->tax_rate,
                'stock'          => $a->branchStocks->sum('quantity'),
                'stock_min'      => $a->stock_min,
                'category'       => $a->category?->name,
                'category_id'    => $a->category_id,
            ];
        });

        return response()->json($articles);
    }

    public function show(Article $article)
    {
        $article->load(['category', 'branchStocks.branch']);

        return response()->json([
            'id'                  => $article->id,
            'reference'           => $article->reference,
            'designation'         => $article->designation,
            'marque'              => $article->marque,
            'unit'                => $article->unit,
            'short_description'   => $article->short_description,
            'sale_price_ttc'      => (float) $article->sale_price_ttc,
            'sale_price_ht'       => (float) $article->sale_price_ht,
            'purchase_price_ht'   => (float) $article->purchase_price_ht,
            'tax_rate'            => (float) $article->tax_rate,
            'profit_margin'       => (float) $article->profit_margin,
            'stock_min'           => $article->stock_min,
            'stock_max'           => $article->stock_max,
            'stock'               => $article->branchStocks->sum('quantity'),
            'category'            => $article->category?->name,
            'category_id'         => $article->category_id,
            'branch_stocks'       => $article->branchStocks->map(fn($bs) => [
                'branch_id'   => $bs->branch_id,
                'branch_name' => $bs->branch?->name,
                'quantity'    => $bs->quantity,
            ]),
        ]);
    }
}
