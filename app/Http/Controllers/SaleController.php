<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\ArticleBranchStock;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $branchIds = $this->getBranchIds($user);

        $query = Sale::with(['branch', 'user', 'customer'])
            ->whereIn('branch_id', $branchIds);

        if ($request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($request->search) {
            $query->where('invoice_number', 'like', "%{$request->search}%");
        }

        $totalAmount = (clone $query)->sum('total_ttc');
        $totalCount  = (clone $query)->count();

        $sales = $query->latest()->paginate(25)->withQueryString();
        $branches = Branch::whereIn('id', $branchIds)->get();

        return view('sales.index', compact('sales', 'branches', 'totalAmount', 'totalCount'));
    }

    public function create(Request $request)
    {
        $user = $request->user();
        $branchId = $request->branch_id ?? $user->branch_id;
        $branches = Branch::whereIn('id', $this->getBranchIds($user))->where('is_active', true)->get();
        $customers = Customer::orderBy('name')->get();

        return view('sales.create', compact('branches', 'customers', 'branchId'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'items' => 'required|array|min:1',
            'items.*.article_id' => 'required|exists:articles,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price_ttc' => 'required|numeric|min:0',
            'items.*.discount_amount' => 'nullable|numeric|min:0',
            'payment_methods' => 'required|array',
            'payment_methods.*.method' => 'required|string',
            'payment_methods.*.amount' => 'required|numeric|min:0',
            'customer_id' => 'nullable|exists:customers,id',
            'notes' => 'nullable|string',
        ]);

        DB::transaction(function () use ($request) {
            $subtotalHt = 0;
            $taxAmount = 0;
            $discountAmount = 0;

            foreach ($request->items as $item) {
                $article = Article::findOrFail($item['article_id']);
                $itemTotal = ($item['unit_price_ttc'] * $item['quantity']) - ($item['discount_amount'] ?? 0);
                $subtotalHt += $itemTotal / (1 + $article->tax_rate / 100);
                $taxAmount += $itemTotal - ($itemTotal / (1 + $article->tax_rate / 100));
                $discountAmount += $item['discount_amount'] ?? 0;
            }

            $totalTtc = $subtotalHt + $taxAmount;
            $amountPaid = collect($request->payment_methods)->sum('amount');

            $paymentStatus = 'paid';
            foreach ($request->payment_methods as $pm) {
                if ($pm['method'] === 'credit') {
                    $paymentStatus = $amountPaid >= $totalTtc ? 'partial' : 'credit';
                    break;
                }
            }

            $sale = Sale::create([
                'tenant_id' => app('currentTenant')->id,
                'branch_id' => $request->branch_id,
                'user_id' => auth()->id(),
                'customer_id' => $request->customer_id,
                'subtotal_ht' => $subtotalHt,
                'tax_amount' => $taxAmount,
                'discount_amount' => $discountAmount,
                'total_ttc' => $totalTtc,
                'amount_paid' => $amountPaid,
                'change_given' => max(0, $amountPaid - $totalTtc),
                'payment_status' => $paymentStatus,
                'payment_methods' => $request->payment_methods,
                'notes' => $request->notes,
            ]);

            foreach ($request->items as $item) {
                $article = Article::find($item['article_id']);
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'article_id' => $item['article_id'],
                    'designation' => $article->designation,
                    'unit' => $article->unit,
                    'quantity' => $item['quantity'],
                    'unit_price_ttc' => $item['unit_price_ttc'],
                    'discount_amount' => $item['discount_amount'] ?? 0,
                    'total_ttc' => ($item['unit_price_ttc'] * $item['quantity']) - ($item['discount_amount'] ?? 0),
                ]);

                $stock = ArticleBranchStock::firstOrCreate(
                    ['article_id' => $item['article_id'], 'branch_id' => $request->branch_id],
                    ['quantity' => 0]
                );
                $before = $stock->quantity;
                $stock->decrement('quantity', $item['quantity']);

                StockMovement::create([
                    'tenant_id'    => app('currentTenant')->id,
                    'branch_id'    => $request->branch_id,
                    'article_id'   => $item['article_id'],
                    'user_id'      => auth()->id(),
                    'type'         => 'out',
                    'quantity'     => -$item['quantity'],
                    'stock_before' => $before,
                    'stock_after'  => $before - $item['quantity'],
                    'notes'        => "Vente #{$sale->id}",
                ]);
            }

            // Update customer credit if needed
            if ($request->customer_id && $paymentStatus !== 'paid') {
                $customer = Customer::find($request->customer_id);
                $customer->increment('credit_balance', max(0, $totalTtc - $amountPaid));
            }

            session(['last_sale_id' => $sale->id]);
        });

        return redirect()->route('sales.receipt', session('last_sale_id'))
            ->with('success', 'Vente enregistrée avec succès.');
    }

    public function show(Sale $sale)
    {
        $sale->load(['branch', 'user', 'customer', 'items.article']);
        return view('sales.show', compact('sale'));
    }

    public function receipt(Sale $sale)
    {
        $sale->load(['branch', 'user', 'customer', 'items.article', 'branch.tenant']);
        return view('sales.receipt', compact('sale'));
    }

    public function invoice(Sale $sale)
    {
        $sale->load(['branch', 'user', 'customer', 'items.article', 'branch.tenant']);
        $tenant = $sale->branch?->tenant ?? app('currentTenant');
        return view('sales.invoice', compact('sale', 'tenant'));
    }

    public function searchArticle(Request $request)
    {
        $branchId = $request->branch_id;
        $search = $request->q;

        $articles = Article::with(['branchStocks' => fn($q) => $q->where('branch_id', $branchId)])
            ->where(function ($q) use ($search) {
                $q->where('designation', 'like', "%$search%")
                  ->orWhere('reference', 'like', "%$search%");
            })
            ->where('is_active', true)
            ->take(10)
            ->get()
            ->map(fn($a) => [
                'id' => $a->id,
                'reference' => $a->reference,
                'designation' => $a->designation,
                'unit' => $a->unit,
                'sale_price_ttc' => $a->sale_price_ttc,
                'stock' => $a->branchStocks->first()?->quantity ?? 0,
            ]);

        return response()->json($articles);
    }

    public function scanQr(Request $request)
    {
        $code = $request->validate(['code' => 'required|string'])['code'];
        $qr = \App\Models\QrCode::where('code', $code)->first();

        if (!$qr) {
            $data = json_decode($code, true);
            $articleId = $data['id'] ?? null;
            $article = $articleId ? Article::find($articleId) : null;
        } else {
            $article = $qr->article;
        }

        if (!$article) {
            return response()->json(['error' => 'Article non trouvé'], 404);
        }

        $branchId = $request->branch_id ?? auth()->user()->branch_id;
        $stock = ArticleBranchStock::where('article_id', $article->id)
            ->where('branch_id', $branchId)
            ->first();

        return response()->json([
            'id' => $article->id,
            'reference' => $article->reference,
            'designation' => $article->designation,
            'unit' => $article->unit,
            'sale_price_ttc' => $article->sale_price_ttc,
            'stock' => $stock?->quantity ?? 0,
        ]);
    }

    private function getBranchIds($user): array
    {
        if ($user->hasRole(['proprietaire', 'admin_boutique', 'comptable'])) {
            return Branch::pluck('id')->toArray();
        }
        return [$user->branch_id];
    }
}
