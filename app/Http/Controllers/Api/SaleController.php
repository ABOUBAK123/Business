<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
        $user      = $request->user();
        $branchIds = $this->getBranchIds($user);

        $sales = Sale::with(['branch', 'user', 'customer'])
            ->withCount('items')
            ->whereIn('branch_id', $branchIds)
            ->latest()
            ->take(20)
            ->get()
            ->map(fn($s) => [
                'id'             => $s->id,
                'invoice_number' => $s->invoice_number,
                'total_ttc'      => (float) $s->total_ttc,
                'subtotal_ht'    => (float) $s->subtotal_ht,
                'tax_amount'     => (float) $s->tax_amount,
                'discount_amount'=> (float) $s->discount_amount,
                'amount_paid'    => (float) $s->amount_paid,
                'change_given'   => (float) $s->change_given,
                'payment_status' => $s->payment_status,
                'payment_methods'=> $s->payment_methods,
                'items_count'    => $s->items_count,
                'branch'         => $s->branch?->name,
                'branch_id'      => $s->branch_id,
                'cashier'        => $s->user?->name,
                'customer'       => $s->customer?->name,
                'notes'          => $s->notes,
                'created_at'     => $s->created_at->toIso8601String(),
            ]);

        return response()->json(['data' => $sales]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'branch_id'                        => 'required|exists:branches,id',
            'items'                            => 'required|array|min:1',
            'items.*.article_id'               => 'required|exists:articles,id',
            'items.*.quantity'                 => 'required|integer|min:1',
            'items.*.unit_price_ttc'           => 'required|numeric|min:0',
            'items.*.discount_amount'          => 'nullable|numeric|min:0',
            'payment_methods'                  => 'required|array|min:1',
            'payment_methods.*.method'         => 'required|string',
            'payment_methods.*.amount'         => 'required|numeric|min:0',
            'customer_id'                      => 'nullable|exists:customers,id',
            'notes'                            => 'nullable|string',
        ]);

        $saleId = null;

        DB::transaction(function () use ($request, &$saleId) {
            $subtotalHt    = 0;
            $taxAmount     = 0;
            $discountAmount = 0;

            foreach ($request->items as $item) {
                $article    = Article::findOrFail($item['article_id']);
                $lineTotal  = ($item['unit_price_ttc'] * $item['quantity']) - ($item['discount_amount'] ?? 0);
                $lineHt     = $lineTotal / (1 + $article->tax_rate / 100);
                $subtotalHt += $lineHt;
                $taxAmount  += $lineTotal - $lineHt;
                $discountAmount += $item['discount_amount'] ?? 0;
            }

            $totalTtc   = $subtotalHt + $taxAmount;
            $amountPaid = collect($request->payment_methods)->sum('amount');

            $paymentStatus = 'paid';
            foreach ($request->payment_methods as $pm) {
                if ($pm['method'] === 'credit') {
                    $paymentStatus = $amountPaid >= $totalTtc ? 'partial' : 'credit';
                    break;
                }
            }

            $sale = Sale::create([
                'tenant_id'       => app('currentTenant')->id,
                'branch_id'       => $request->branch_id,
                'user_id'         => auth()->id(),
                'customer_id'     => $request->customer_id,
                'subtotal_ht'     => $subtotalHt,
                'tax_amount'      => $taxAmount,
                'discount_amount' => $discountAmount,
                'total_ttc'       => $totalTtc,
                'amount_paid'     => $amountPaid,
                'change_given'    => max(0, $amountPaid - $totalTtc),
                'payment_status'  => $paymentStatus,
                'payment_methods' => $request->payment_methods,
                'notes'           => $request->notes,
            ]);

            foreach ($request->items as $item) {
                $article = Article::find($item['article_id']);
                SaleItem::create([
                    'sale_id'         => $sale->id,
                    'article_id'      => $item['article_id'],
                    'designation'     => $article->designation,
                    'unit'            => $article->unit,
                    'quantity'        => $item['quantity'],
                    'unit_price_ttc'  => $item['unit_price_ttc'],
                    'discount_amount' => $item['discount_amount'] ?? 0,
                    'total_ttc'       => ($item['unit_price_ttc'] * $item['quantity']) - ($item['discount_amount'] ?? 0),
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
                    'notes'        => "Vente mobile #{$sale->id}",
                ]);
            }

            if ($request->customer_id && $paymentStatus !== 'paid') {
                $customer = Customer::find($request->customer_id);
                if ($customer) {
                    $customer->increment('credit_balance', max(0, $totalTtc - $amountPaid));
                }
            }

            $saleId = $sale->id;
        });

        $sale = Sale::find($saleId);

        return response()->json([
            'message'        => 'Vente enregistrée avec succès.',
            'sale_id'        => $saleId,
            'invoice_number' => $sale->invoice_number,
            'total_ttc'      => (float) $sale->total_ttc,
            'payment_status' => $sale->payment_status,
            'change_given'   => (float) $sale->change_given,
        ], 201);
    }

    private function getBranchIds($user): array
    {
        if ($user->hasRole(['proprietaire', 'admin_boutique', 'comptable'])) {
            return Branch::pluck('id')->toArray();
        }
        return array_filter([$user->branch_id]);
    }
}
