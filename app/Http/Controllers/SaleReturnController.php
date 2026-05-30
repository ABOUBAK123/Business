<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\ArticleBranchStock;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaleReturnController extends Controller
{
    public function index()
    {
        $branchIds = $this->getBranchIds();
        $returns   = SaleReturn::with(['sale', 'customer', 'branch', 'user'])
            ->withCount('items')
            ->whereIn('branch_id', $branchIds)
            ->latest()->paginate(20);

        return view('returns.index', compact('returns'));
    }

    public function create(Request $request)
    {
        $branchIds = $this->getBranchIds();
        $branches  = Branch::whereIn('id', $branchIds)->where('is_active', true)->get();
        $customers = Customer::orderBy('name')->get();

        $sale = null;
        if ($request->sale_id) {
            $sale = Sale::with('items.article')->findOrFail($request->sale_id);
        }

        $articles = Article::where('is_active', true)->orderBy('designation')->get();

        return view('returns.create', compact('branches', 'customers', 'sale', 'articles'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'branch_id'     => 'required|exists:branches,id',
            'sale_id'       => 'nullable|exists:sales,id',
            'customer_id'   => 'nullable|exists:customers,id',
            'reason'        => 'required|string|max:255',
            'refund_method' => 'required|in:cash,credit,exchange',
            'notes'         => 'nullable|string|max:500',
            'items'         => 'required|array|min:1',
            'items.*.article_id' => 'required|exists:articles,id',
            'items.*.quantity'   => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.restock'    => 'nullable|boolean',
        ]);

        DB::transaction(function () use ($data) {
            $total = collect($data['items'])->sum(fn($i) => $i['quantity'] * $i['unit_price']);

            $return = SaleReturn::create([
                'tenant_id'     => app('currentTenant')->id,
                'branch_id'     => $data['branch_id'],
                'user_id'       => auth()->id(),
                'sale_id'       => $data['sale_id'] ?? null,
                'customer_id'   => $data['customer_id'] ?? null,
                'return_number' => $this->generateNumber(),
                'reason'        => $data['reason'],
                'refund_method' => $data['refund_method'],
                'total_amount'  => $total,
                'notes'         => $data['notes'] ?? null,
            ]);

            foreach ($data['items'] as $item) {
                $article = Article::find($item['article_id']);
                $lineTotal = $item['quantity'] * $item['unit_price'];
                $restock   = isset($item['restock']) && $item['restock'];

                SaleReturnItem::create([
                    'sale_return_id' => $return->id,
                    'article_id'     => $item['article_id'],
                    'designation'    => $article->designation,
                    'unit'           => $article->unit,
                    'quantity'       => $item['quantity'],
                    'unit_price'     => $item['unit_price'],
                    'total'          => $lineTotal,
                    'restock'        => $restock,
                ]);

                if ($restock) {
                    $stock = ArticleBranchStock::firstOrCreate(
                        ['article_id' => $item['article_id'], 'branch_id' => $data['branch_id']],
                        ['quantity' => 0]
                    );
                    $before = $stock->quantity;
                    $stock->increment('quantity', $item['quantity']);

                    StockMovement::create([
                        'tenant_id'    => app('currentTenant')->id,
                        'branch_id'    => $data['branch_id'],
                        'article_id'   => $item['article_id'],
                        'user_id'      => auth()->id(),
                        'type'         => 'in',
                        'quantity'     => $item['quantity'],
                        'stock_before' => $before,
                        'stock_after'  => $before + $item['quantity'],
                        'notes'        => "Retour #{$return->return_number}",
                    ]);
                }

                // Si avoir client : incrémenter credit_balance
                if ($data['refund_method'] === 'credit' && $data['customer_id']) {
                    \App\Models\Customer::find($data['customer_id'])
                        ?->decrement('credit_balance', $lineTotal);
                }
            }
        });

        return redirect()->route('returns.index')
            ->with('success', 'Retour enregistré avec succès.');
    }

    public function show(SaleReturn $return)
    {
        $return->load(['sale', 'customer', 'branch', 'user', 'items.article']);
        return view('returns.show', compact('return'));
    }

    private function generateNumber(): string
    {
        $last = SaleReturn::whereDate('created_at', today())->count() + 1;
        return 'RET-' . now()->format('Ymd') . '-' . str_pad($last, 3, '0', STR_PAD_LEFT);
    }

    private function getBranchIds(): array
    {
        $user = auth()->user();
        if ($user->hasRole(['proprietaire', 'admin_boutique', 'comptable'])) {
            return Branch::pluck('id')->toArray();
        }
        return $user->branch_id ? [$user->branch_id] : [];
    }
}
