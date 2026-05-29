<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\ArticleBranchStock;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Setting;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class ArticleController extends Controller
{
    public function index(Request $request)
    {
        $query = Article::with(['category', 'mainQrCode']);

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('designation', 'like', "%{$request->search}%")
                  ->orWhere('reference', 'like', "%{$request->search}%");
            });
        }

        if ($request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        $articles = $query->latest()->paginate(20)->withQueryString();
        $categories = Category::where('is_active', true)->get();

        return view('articles.index', compact('articles', 'categories'));
    }

    public function create()
    {
        $categories    = Category::where('is_active', true)->get();
        $suppliers     = Supplier::where('is_active', true)->get();
        $branches      = Branch::where('is_active', true)->get();
        $generatedCode = $this->generateArticleCode();
        return view('articles.form', compact('categories', 'suppliers', 'branches', 'generatedCode'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'designation' => 'required|string|max:191',
            'marque' => 'nullable|string|max:150',
            'reference' => 'nullable|string|max:100',
            'category_id' => 'nullable|exists:categories,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'unit' => 'required|string|max:50',
            'purchase_price_ht' => 'required|numeric|min:0',
            'sale_price_ht' => 'required|numeric|min:0',
            'tax_rate' => 'required|numeric|min:0|max:100',
            'stock_min' => 'integer|min:0',
            'initial_stock' => 'nullable|integer|min:0',
            'short_description' => 'nullable|string',
            'photos.*' => 'nullable|image|max:2048',
        ]);

        $initialStock = (int) ($data['initial_stock'] ?? 0);
        unset($data['initial_stock']);

        $photos = [];
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $photo) {
                $photos[] = $photo->store('articles', 'public');
            }
        }
        $data['photos'] = $photos;

        if (empty($data['reference'])) {
            $data['reference'] = $this->generateArticleCode();
        }

        $article = Article::create($data);

        // Ensure at least one branch exists (create main boutique branch if needed)
        if (Branch::where('is_active', true)->doesntExist()) {
            $tenant = app('currentTenant');
            Branch::create([
                'tenant_id' => $tenant->id,
                'name'      => $tenant->shop_name ?? 'Boutique principale',
                'is_main'   => true,
                'is_active' => true,
            ]);
        }

        // Initialize stock for each branch
        foreach (Branch::where('is_active', true)->get() as $branch) {
            ArticleBranchStock::firstOrCreate([
                'article_id' => $article->id,
                'branch_id' => $branch->id,
            ], ['quantity' => $initialStock]);

            if ($initialStock > 0) {
                \App\Models\StockMovement::create([
                    'tenant_id'    => $article->tenant_id,
                    'branch_id'    => $branch->id,
                    'article_id'   => $article->id,
                    'user_id'      => auth()->id(),
                    'type'         => 'in',
                    'quantity'     => $initialStock,
                    'stock_before' => 0,
                    'stock_after'  => $initialStock,
                    'notes'        => 'Stock initial à la création',
                ]);
            }
        }

        return redirect()->route('articles.index')->with('success', 'Article créé avec succès. QR Code généré.');
    }

    public function show(Article $article)
    {
        $article->load(['category', 'supplier', 'qrCodes', 'branchStocks.branch']);
        $qrImage = QrCode::size(200)->generate($article->mainQrCode?->code ?? $article->id);
        return view('articles.show', compact('article', 'qrImage'));
    }

    public function edit(Article $article)
    {
        $categories = Category::where('is_active', true)->get();
        $suppliers = Supplier::where('is_active', true)->get();
        $branches = Branch::where('is_active', true)->get();
        $article->load('branchStocks');
        return view('articles.form', compact('article', 'categories', 'suppliers', 'branches'));
    }

    public function update(Request $request, Article $article)
    {
        $data = $request->validate([
            'designation' => 'required|string|max:191',
            'marque' => 'nullable|string|max:150',
            'reference' => 'nullable|string|max:100',
            'category_id' => 'nullable|exists:categories,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'unit' => 'required|string|max:50',
            'purchase_price_ht' => 'required|numeric|min:0',
            'sale_price_ht' => 'required|numeric|min:0',
            'tax_rate' => 'required|numeric|min:0|max:100',
            'stock_min' => 'integer|min:0',
            'short_description' => 'nullable|string',
        ]);

        $article->update($data);
        $article->generateQrCode();

        return redirect()->route('articles.index')->with('success', 'Article modifié. QR Code régénéré.');
    }

    public function destroy(Article $article)
    {
        $article->delete();
        return redirect()->route('articles.index')->with('success', 'Article supprimé.');
    }

    public function printQr(Article $article)
    {
        $qrCode = $article->mainQrCode ?? $article->generateQrCode();
        $qrImage = QrCode::size(200)->generate($qrCode->code);
        return view('articles.qr-print', compact('article', 'qrImage'));
    }

    public function bulkQr(Request $request)
    {
        $ids = $request->validate(['ids' => 'required|array', 'ids.*' => 'exists:articles,id'])['ids'];
        $articles = Article::with('mainQrCode')->whereIn('id', $ids)->get();
        $qrCodes = $articles->map(function ($a) {
            $code = $a->mainQrCode ?? $a->generateQrCode();
            return [
                'article' => $a,
                'qr' => QrCode::size(150)->generate($code->code),
            ];
        });
        return view('articles.qr-bulk', compact('qrCodes'));
    }

    public function updateStock(Request $request, Article $article)
    {
        $data = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'quantity' => 'required|integer',
            'notes' => 'nullable|string',
        ]);

        $stock = ArticleBranchStock::firstOrCreate(
            ['article_id' => $article->id, 'branch_id' => $data['branch_id']],
            ['quantity' => 0]
        );

        $stockBefore = $stock->quantity;
        $stock->update(['quantity' => $data['quantity']]);

        \App\Models\StockMovement::create([
            'tenant_id' => $article->tenant_id,
            'branch_id' => $data['branch_id'],
            'article_id' => $article->id,
            'user_id' => auth()->id(),
            'type' => 'adjustment',
            'quantity' => $data['quantity'] - $stockBefore,
            'stock_before' => $stockBefore,
            'stock_after' => $data['quantity'],
            'notes' => $data['notes'],
        ]);

        return back()->with('success', 'Stock ajusté.');
    }

    public function generateCode(): \Illuminate\Http\JsonResponse
    {
        return response()->json(['code' => $this->generateArticleCode()]);
    }

    private function generateArticleCode(): string
    {
        $tenantId = app()->bound('currentTenant') ? app('currentTenant')->id : null;
        $suffix   = $tenantId ? "_t{$tenantId}" : '';

        $prefix = Setting::get("article_code_prefix{$suffix}")
               ?? Setting::get('article_code_prefix', 'ART-');
        $length = max(3, min(12, (int) (
            Setting::get("article_code_length{$suffix}")
            ?? Setting::get('article_code_length', 6)
        )));
        $type = Setting::get("article_code_type{$suffix}")
             ?? Setting::get('article_code_type', 'alphanumeric');

        $random = match ($type) {
            'numeric' => str_pad(random_int(0, (int) str_repeat('9', $length)), $length, '0', STR_PAD_LEFT),
            'alpha'   => strtoupper(substr(str_shuffle(str_repeat('ABCDEFGHIJKLMNOPQRSTUVWXYZ', 3)), 0, $length)),
            default   => strtoupper(Str::random($length)),
        };

        return $prefix . $random;
    }
}
