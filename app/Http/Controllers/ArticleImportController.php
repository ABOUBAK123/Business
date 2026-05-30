<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\ArticleBranchStock;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ArticleImportController extends Controller
{
    public function showForm()
    {
        $categories = Category::orderBy('name')->get();
        $branches   = Branch::where('is_active', true)->get();
        return view('articles.import', compact('categories', 'branches'));
    }

    public function downloadTemplate()
    {
        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="modele_import_articles.csv"',
        ];

        $rows = [
            ['reference', 'designation', 'marque', 'categorie', 'unite', 'prix_achat_ht', 'prix_vente_ttc', 'tva', 'stock_initial', 'stock_min'],
            ['REF-001', 'Clou 100mm', 'STANLEY', 'Visserie', 'kg', '500', '750', '18', '50', '10'],
            ['REF-002', 'Marteau 500g', 'FACOM', 'Outillage', 'pce', '3000', '5000', '18', '20', '5'],
            ['REF-003', 'Ciment 50kg', '', 'Matériaux', 'sac', '8000', '12000', '0', '100', '20'],
        ];

        $callback = function () use ($rows) {
            $file = fopen('php://output', 'w');
            // BOM UTF-8 pour Excel
            fputs($file, "\xEF\xBB\xBF");
            foreach ($rows as $row) {
                fputcsv($file, $row, ';');
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function preview(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        $rows   = $this->parseCsv($request->file('csv_file'));
        $errors = [];
        $preview = [];

        foreach ($rows as $i => $row) {
            $lineNum = $i + 2;
            if (empty(trim($row['designation'] ?? ''))) {
                $errors[] = "Ligne {$lineNum} : désignation manquante";
                continue;
            }
            $preview[] = [
                'line'          => $lineNum,
                'reference'     => trim($row['reference'] ?? ''),
                'designation'   => trim($row['designation']),
                'marque'        => trim($row['marque'] ?? ''),
                'categorie'     => trim($row['categorie'] ?? ''),
                'unite'         => trim($row['unite'] ?? 'pce'),
                'prix_achat_ht' => (float) str_replace(',', '.', $row['prix_achat_ht'] ?? 0),
                'prix_vente_ttc'=> (float) str_replace(',', '.', $row['prix_vente_ttc'] ?? 0),
                'tva'           => (float) str_replace(',', '.', $row['tva'] ?? 18),
                'stock_initial' => (int) ($row['stock_initial'] ?? 0),
                'stock_min'     => (int) ($row['stock_min'] ?? 0),
            ];
        }

        session(['import_preview' => $preview, 'import_errors' => $errors]);

        return redirect()->back()
            ->with('preview', $preview)
            ->with('preview_errors', $errors);
    }

    public function import(Request $request)
    {
        $request->validate([
            'csv_file'  => 'required|file|mimes:csv,txt|max:5120',
            'branch_id' => 'required|exists:branches,id',
        ]);

        $rows    = $this->parseCsv($request->file('csv_file'));
        $tenant  = app('currentTenant');
        $branchId = $request->branch_id;

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors  = [];

        foreach ($rows as $i => $row) {
            $lineNum = $i + 2;
            $designation = trim($row['designation'] ?? '');
            if (empty($designation)) { $skipped++; continue; }

            try {
                // Catégorie : auto-créer si inexistante
                $category = null;
                if (!empty(trim($row['categorie'] ?? ''))) {
                    $category = Category::firstOrCreate(
                        ['tenant_id' => $tenant->id, 'name' => trim($row['categorie'])],
                        ['is_active' => true]
                    );
                }

                $prixVenteTtc = (float) str_replace(',', '.', $row['prix_vente_ttc'] ?? 0);
                $tva          = (float) str_replace(',', '.', $row['tva'] ?? 18);
                $prixVenteHt  = $tva > 0 ? round($prixVenteTtc / (1 + $tva / 100), 2) : $prixVenteTtc;
                $prixAchatHt  = (float) str_replace(',', '.', $row['prix_achat_ht'] ?? 0);
                $marge        = $prixVenteHt > 0 && $prixAchatHt > 0
                    ? round((($prixVenteHt - $prixAchatHt) / $prixVenteHt) * 100, 2)
                    : 0;

                $ref = trim($row['reference'] ?? '');
                if (empty($ref)) {
                    $ref = 'ART-' . strtoupper(Str::random(6));
                }

                $article = Article::where('tenant_id', $tenant->id)
                    ->where('reference', $ref)->first();

                $data = [
                    'tenant_id'       => $tenant->id,
                    'category_id'     => $category?->id,
                    'reference'       => $ref,
                    'designation'     => $designation,
                    'marque'          => trim($row['marque'] ?? ''),
                    'unit'            => trim($row['unite'] ?? 'pce') ?: 'pce',
                    'purchase_price_ht'=> $prixAchatHt,
                    'sale_price_ht'   => $prixVenteHt,
                    'sale_price_ttc'  => $prixVenteTtc,
                    'tax_rate'        => $tva,
                    'profit_margin'   => $marge,
                    'stock_min'       => (int) ($row['stock_min'] ?? 0),
                    'is_active'       => true,
                ];

                if ($article) {
                    $article->update($data);
                    $updated++;
                } else {
                    $article = Article::create($data);
                    $created++;
                }

                // Stock initial
                $stockQty = (int) ($row['stock_initial'] ?? 0);
                if ($stockQty > 0) {
                    ArticleBranchStock::updateOrCreate(
                        ['article_id' => $article->id, 'branch_id' => $branchId],
                        ['quantity' => $stockQty]
                    );
                }
            } catch (\Exception $e) {
                $errors[] = "Ligne {$lineNum} ({$designation}) : " . $e->getMessage();
            }
        }

        $msg = "Import terminé — {$created} créé(s), {$updated} mis à jour, {$skipped} ignoré(s).";
        if ($errors) {
            $msg .= ' ' . count($errors) . ' erreur(s).';
        }

        return redirect()->route('articles.index')->with('success', $msg);
    }

    private function parseCsv(\Illuminate\Http\UploadedFile $file): array
    {
        $rows    = [];
        $handle  = fopen($file->getRealPath(), 'r');

        // Supprimer BOM UTF-8 si présent
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        // Détecter délimiteur (;  ou ,)
        $firstLine = fgets($handle);
        rewind($handle);
        if ($bom === "\xEF\xBB\xBF") fread($handle, 3);
        $delimiter = substr_count($firstLine, ';') >= substr_count($firstLine, ',') ? ';' : ',';

        $headers = fgetcsv($handle, 0, $delimiter);
        $headers = array_map(fn($h) => mb_strtolower(trim(str_replace(["\xEF\xBB\xBF", '"'], '', $h))), $headers);

        while (($line = fgetcsv($handle, 0, $delimiter)) !== false) {
            if (empty(array_filter($line))) continue;
            $rows[] = array_combine($headers, array_pad($line, count($headers), ''));
        }

        fclose($handle);
        return $rows;
    }
}
