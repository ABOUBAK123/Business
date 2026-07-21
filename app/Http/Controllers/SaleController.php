<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
    public function history(Request $request)
    {
        $search = trim((string) $request->query('q', ''));

        $sales = Sale::query()
            ->with(['items.article', 'user'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery->where('sale_number', 'like', '%' . $search . '%')
                        ->orWhere('customer_name', 'like', '%' . $search . '%')
                        ->orWhereHas('items.article', function ($articleQuery) use ($search) {
                            $articleQuery->where('name', 'like', '%' . $search . '%')
                                ->orWhere('reference', 'like', '%' . $search . '%');
                        });
                });
            })
            ->orderByDesc('sold_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('sales.history', compact('sales', 'search'));
    }

    public function create(Request $request)
    {
        $search = trim((string) $request->query('q', ''));

        $articles = Article::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery->where('reference', 'like', '%' . $search . '%')
                        ->orWhere('name', 'like', '%' . $search . '%');
                });
            })
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $selectedArticle = null;
        $selectedArticleId = old('article_id', $request->query('article_id'));
        if ($selectedArticleId) {
            $selectedArticle = $articles->firstWhere('id', (int) $selectedArticleId) ?? Article::find($selectedArticleId);
        }

        return view('sales.create', compact('articles', 'search', 'selectedArticle'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'article_id' => ['required', 'integer', 'exists:articles,id'],
            'quantity' => ['required', 'numeric', 'min:0.001'],
            'customer_name' => ['nullable', 'string', 'max:150'],
        ]);

        $sale = DB::transaction(function () use ($validated) {
            $article = Article::query()->lockForUpdate()->findOrFail($validated['article_id']);
            $quantity = (float) $validated['quantity'];

            if ((float) $article->stock < $quantity) {
                abort(422, 'Stock insuffisant pour cet article.');
            }

            $unitPrice = (float) $article->price;
            $subtotal = round($unitPrice * $quantity, 2);

            $sale = Sale::create([
                'sale_number' => $this->generateSaleNumber(),
                'user_id' => auth()->id(),
                'customer_name' => $validated['customer_name'] ?? null,
                'total_amount' => $subtotal,
                'sold_at' => now(),
                'status' => 'completed',
            ]);

            $sale->items()->create([
                'article_id' => $article->id,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'subtotal' => $subtotal,
            ]);

            $article->decrement('stock', $quantity);

            return $sale;
        });

        return redirect()->route('sales.create')
            ->with('success', 'Vente enregistrée avec succès: ' . $sale->sale_number);
    }

    private function generateSaleNumber(): string
    {
        return 'V-' . now()->format('YmdHis') . '-' . str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);
    }
}
