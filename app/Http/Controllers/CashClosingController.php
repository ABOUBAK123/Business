<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\CashClosing;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CashClosingController extends Controller
{
    public function index(Request $request)
    {
        $branchIds = $this->getBranchIds();
        $closings  = CashClosing::with(['branch', 'user'])
            ->whereIn('branch_id', $branchIds)
            ->orderByDesc('date')
            ->paginate(20)->withQueryString();

        $branches = Branch::whereIn('id', $branchIds)->get();
        return view('cash.index', compact('closings', 'branches'));
    }

    public function create(Request $request)
    {
        $branchIds = $this->getBranchIds();
        $branches  = Branch::whereIn('id', $branchIds)->where('is_active', true)->get();

        $branchId = $request->branch_id ?? auth()->user()->branch_id ?? $branches->first()?->id;
        $date     = $request->date ?? today()->toDateString();

        // Vérifier si déjà clôturée
        $existing = CashClosing::where('branch_id', $branchId)->whereDate('date', $date)->first();

        // Calculer le résumé des ventes du jour
        $salesSummary = $this->computeSalesSummary($branchId, $date);

        return view('cash.create', compact('branches', 'branchId', 'date', 'salesSummary', 'existing'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'branch_id'    => 'required|exists:branches,id',
            'date'         => 'required|date|before_or_equal:today',
            'opening_cash' => 'required|numeric|min:0',
            'closing_cash' => 'required|numeric|min:0',
            'notes'        => 'nullable|string|max:500',
        ]);

        $summary = $this->computeSalesSummary($data['branch_id'], $data['date']);

        CashClosing::updateOrCreate(
            ['tenant_id' => app('currentTenant')->id, 'branch_id' => $data['branch_id'], 'date' => $data['date']],
            [
                'user_id'          => auth()->id(),
                'opening_cash'     => $data['opening_cash'],
                'closing_cash'     => $data['closing_cash'],
                'theoretical_cash' => $summary['theoretical_cash'],
                'cash_gap'         => $data['closing_cash'] - $summary['theoretical_cash'],
                'total_sales'      => $summary['total_sales'],
                'sales_count'      => $summary['sales_count'],
                'payment_summary'  => $summary['by_method'],
                'notes'            => $data['notes'],
            ]
        );

        return redirect()->route('cash.index')->with('success', 'Clôture de caisse enregistrée pour le ' . \Carbon\Carbon::parse($data['date'])->format('d/m/Y') . '.');
    }

    public function show(CashClosing $cash)
    {
        $cash->load(['branch', 'user']);
        return view('cash.show', compact('cash'));
    }

    private function computeSalesSummary(int $branchId, string $date): array
    {
        $sales = Sale::where('branch_id', $branchId)
            ->whereDate('created_at', $date)
            ->get();

        $totalSales   = $sales->sum('total_ttc');
        $salesCount   = $sales->count();

        // Grouper par mode de paiement (premier mode du JSON)
        $byMethod = [];
        foreach ($sales as $sale) {
            $methods = $sale->payment_methods ?? [];
            foreach ($methods as $pm) {
                $method = $pm['method'] ?? 'cash';
                $amount = $pm['amount'] ?? $sale->total_ttc;
                $byMethod[$method] = ($byMethod[$method] ?? 0) + $amount;
            }
        }

        $theoreticalCash = $byMethod['cash'] ?? 0;

        return [
            'total_sales'      => $totalSales,
            'sales_count'      => $salesCount,
            'by_method'        => $byMethod,
            'theoretical_cash' => $theoreticalCash,
        ];
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
