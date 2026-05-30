<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\ArticleBranchStock;
use App\Models\Branch;
use App\Models\Inventory;
use App\Models\InventoryLine;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    public function index()
    {
        $branchIds   = $this->getBranchIds();
        $inventories = Inventory::with(['branch', 'user'])
            ->whereIn('branch_id', $branchIds)
            ->withCount('lines')
            ->orderByDesc('date')->paginate(15);

        return view('inventory.index', compact('inventories'));
    }

    public function create()
    {
        $branchIds = $this->getBranchIds();
        $branches  = Branch::whereIn('id', $branchIds)->where('is_active', true)->get();
        return view('inventory.create', compact('branches'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'date'      => 'required|date',
            'notes'     => 'nullable|string|max:500',
        ]);

        $inventory = DB::transaction(function () use ($data) {
            $inv = Inventory::create([
                'tenant_id' => app('currentTenant')->id,
                'branch_id' => $data['branch_id'],
                'user_id'   => auth()->id(),
                'date'      => $data['date'],
                'status'    => 'draft',
                'notes'     => $data['notes'] ?? null,
            ]);

            // Pré-charger tous les articles actifs avec leur stock théorique
            $articles = Article::where('is_active', true)->get();
            foreach ($articles as $article) {
                $stock = ArticleBranchStock::where('article_id', $article->id)
                    ->where('branch_id', $data['branch_id'])
                    ->value('quantity') ?? 0;

                InventoryLine::create([
                    'inventory_id'   => $inv->id,
                    'article_id'     => $article->id,
                    'theoretical_qty'=> $stock,
                    'counted_qty'    => null,
                    'gap'            => 0,
                ]);
            }

            return $inv;
        });

        return redirect()->route('inventory.show', $inventory)
            ->with('success', 'Inventaire créé. Saisissez les quantités comptées.');
    }

    public function show(Inventory $inventory)
    {
        $inventory->load(['branch', 'user']);
        $lines = $inventory->lines()->with('article.category')
            ->orderBy('id')->paginate(50);
        return view('inventory.show', compact('inventory', 'lines'));
    }

    public function saveLine(Request $request, Inventory $inventory)
    {
        abort_if($inventory->status === 'completed', 403, 'Inventaire déjà clôturé.');

        $data = $request->validate([
            'lines'              => 'required|array',
            'lines.*.id'         => 'required|exists:inventory_lines,id',
            'lines.*.counted_qty'=> 'required|numeric|min:0',
        ]);

        foreach ($data['lines'] as $line) {
            $inventoryLine = InventoryLine::find($line['id']);
            if ($inventoryLine && $inventoryLine->inventory_id === $inventory->id) {
                $counted = (float) $line['counted_qty'];
                $inventoryLine->update([
                    'counted_qty' => $counted,
                    'gap'         => $counted - (float) $inventoryLine->theoretical_qty,
                ]);
            }
        }

        return back()->with('success', 'Quantités enregistrées.');
    }

    public function finalize(Inventory $inventory)
    {
        abort_if($inventory->status === 'completed', 403, 'Déjà clôturé.');

        $linesWithGap = $inventory->lines()->whereNotNull('counted_qty')->where('gap', '!=', 0)->get();

        if ($inventory->lines()->whereNull('counted_qty')->exists()) {
            return back()->with('error', 'Certaines lignes n\'ont pas de quantité comptée. Veuillez les remplir avant de clôturer.');
        }

        DB::transaction(function () use ($inventory, $linesWithGap) {
            foreach ($linesWithGap as $line) {
                // Ajuster le stock
                $stock = ArticleBranchStock::firstOrCreate(
                    ['article_id' => $line->article_id, 'branch_id' => $inventory->branch_id],
                    ['quantity' => 0]
                );
                $before = $stock->quantity;
                $stock->update(['quantity' => $line->counted_qty]);

                // Mouvement d'ajustement
                StockMovement::create([
                    'tenant_id'    => app('currentTenant')->id,
                    'branch_id'    => $inventory->branch_id,
                    'article_id'   => $line->article_id,
                    'user_id'      => auth()->id(),
                    'type'         => 'adjustment',
                    'quantity'     => $line->gap,
                    'stock_before' => $before,
                    'stock_after'  => $line->counted_qty,
                    'notes'        => "Inventaire physique #{$inventory->id} du {$inventory->date->format('d/m/Y')}",
                ]);
            }

            $inventory->update([
                'status'       => 'completed',
                'completed_at' => now(),
            ]);
        });

        return redirect()->route('inventory.show', $inventory)
            ->with('success', 'Inventaire clôturé. ' . $linesWithGap->count() . ' article(s) ajusté(s).');
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
