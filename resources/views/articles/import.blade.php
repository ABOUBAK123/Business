@extends('layouts.app')
@section('title', 'Import articles')
@section('page-title', 'Import articles par CSV/Excel')

@section('content')

@if(session('success'))
<div class="bg-green-50 border border-green-200 text-green-700 rounded-lg p-3 mb-4 text-sm flex items-center gap-2">
    <i class="fas fa-check-circle"></i> {{ session('success') }}
</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

    {{-- Panneau gauche : formulaire --}}
    <div class="lg:col-span-2 space-y-4">

        {{-- Étape 1 : Télécharger le modèle --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-7 h-7 bg-blue-600 rounded-full flex items-center justify-center text-white text-xs font-bold">1</div>
                <h3 class="text-sm font-semibold text-gray-700">Télécharger le modèle CSV</h3>
            </div>
            <p class="text-sm text-gray-500 mb-3">
                Commencez par télécharger le modèle, remplissez-le dans Excel ou LibreOffice,
                puis sauvegardez en format <strong>CSV (séparateur point-virgule)</strong>.
            </p>
            <a href="{{ route('articles.import.template') }}"
               class="inline-flex items-center gap-2 bg-green-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-green-700">
                <i class="fas fa-download"></i> Télécharger modele_import_articles.csv
            </a>

            <div class="mt-3 bg-gray-50 rounded-lg p-3 text-xs text-gray-600">
                <strong>Colonnes du modèle :</strong>
                <div class="grid grid-cols-2 gap-1 mt-1">
                    <span><code class="bg-white px-1 rounded">reference</code> — Référence unique</span>
                    <span><code class="bg-white px-1 rounded">designation</code> — Nom de l'article <span class="text-red-500">*</span></span>
                    <span><code class="bg-white px-1 rounded">marque</code> — Marque</span>
                    <span><code class="bg-white px-1 rounded">categorie</code> — Catégorie (créée auto)</span>
                    <span><code class="bg-white px-1 rounded">unite</code> — Unité (pce, kg, m…)</span>
                    <span><code class="bg-white px-1 rounded">prix_achat_ht</code> — Prix achat HT</span>
                    <span><code class="bg-white px-1 rounded">prix_vente_ttc</code> — Prix vente TTC</span>
                    <span><code class="bg-white px-1 rounded">tva</code> — Taux TVA % (ex: 18)</span>
                    <span><code class="bg-white px-1 rounded">stock_initial</code> — Qté en stock</span>
                    <span><code class="bg-white px-1 rounded">stock_min</code> — Seuil d'alerte</span>
                </div>
            </div>
        </div>

        {{-- Étape 2 : Prévisualiser --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-7 h-7 bg-blue-600 rounded-full flex items-center justify-center text-white text-xs font-bold">2</div>
                <h3 class="text-sm font-semibold text-gray-700">Prévisualiser (optionnel)</h3>
            </div>
            <form method="POST" action="{{ route('articles.import.preview') }}" enctype="multipart/form-data">
                @csrf
                <div class="flex gap-3 items-end">
                    <div class="flex-1">
                        <label class="block text-xs text-gray-500 mb-1">Fichier CSV</label>
                        <input type="file" name="csv_file" accept=".csv,.txt" required
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    </div>
                    <button type="submit"
                            class="bg-gray-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-700">
                        <i class="fas fa-eye mr-1"></i> Prévisualiser
                    </button>
                </div>
            </form>

            @if(session('preview_errors') && count(session('preview_errors')) > 0)
            <div class="mt-3 bg-red-50 border border-red-200 rounded-lg p-3">
                <p class="text-xs font-semibold text-red-700 mb-1">Erreurs détectées :</p>
                @foreach(session('preview_errors') as $err)
                <p class="text-xs text-red-600">• {{ $err }}</p>
                @endforeach
            </div>
            @endif

            @if(session('preview') && count(session('preview')) > 0)
            <div class="mt-3 overflow-x-auto">
                <p class="text-xs text-gray-500 mb-2">{{ count(session('preview')) }} article(s) trouvé(s) :</p>
                <table class="w-full text-xs border-collapse">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-2 py-1.5 text-left border border-gray-200">Réf.</th>
                            <th class="px-2 py-1.5 text-left border border-gray-200">Désignation</th>
                            <th class="px-2 py-1.5 text-left border border-gray-200">Catégorie</th>
                            <th class="px-2 py-1.5 text-right border border-gray-200">P. Achat HT</th>
                            <th class="px-2 py-1.5 text-right border border-gray-200">P. Vente TTC</th>
                            <th class="px-2 py-1.5 text-right border border-gray-200">Stock init.</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach(array_slice(session('preview'), 0, 10) as $row)
                        <tr class="hover:bg-gray-50">
                            <td class="px-2 py-1 border border-gray-100 font-mono">{{ $row['reference'] }}</td>
                            <td class="px-2 py-1 border border-gray-100 font-medium">{{ $row['designation'] }}</td>
                            <td class="px-2 py-1 border border-gray-100 text-gray-500">{{ $row['categorie'] ?: '—' }}</td>
                            <td class="px-2 py-1 border border-gray-100 text-right">{{ number_format($row['prix_achat_ht'], 0) }}</td>
                            <td class="px-2 py-1 border border-gray-100 text-right font-semibold">{{ number_format($row['prix_vente_ttc'], 0) }}</td>
                            <td class="px-2 py-1 border border-gray-100 text-right">{{ $row['stock_initial'] }}</td>
                        </tr>
                        @endforeach
                        @if(count(session('preview')) > 10)
                        <tr><td colspan="6" class="px-2 py-1 text-center text-gray-400 italic">
                            ... et {{ count(session('preview')) - 10 }} autre(s)
                        </td></tr>
                        @endif
                    </tbody>
                </table>
            </div>
            @endif
        </div>

        {{-- Étape 3 : Importer --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-7 h-7 bg-green-600 rounded-full flex items-center justify-center text-white text-xs font-bold">3</div>
                <h3 class="text-sm font-semibold text-gray-700">Importer dans la base</h3>
            </div>
            <form method="POST" action="{{ route('articles.import.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="space-y-3">
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Fichier CSV *</label>
                        <input type="file" name="csv_file" accept=".csv,.txt" required
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Boutique pour le stock initial *</label>
                        <select name="branch_id" required
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            @foreach($branches as $b)
                            <option value="{{ $b->id }}">{{ $b->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 text-xs text-amber-700">
                        <i class="fas fa-exclamation-triangle mr-1"></i>
                        Si un article avec la même référence existe déjà, il sera <strong>mis à jour</strong> (pas dupliqué).
                        Les catégories inexistantes seront créées automatiquement.
                    </div>
                    <button type="submit"
                            class="w-full bg-blue-700 text-white py-2.5 rounded-lg font-semibold hover:bg-blue-800 flex items-center justify-center gap-2">
                        <i class="fas fa-upload"></i> Lancer l'import
                    </button>
                </div>
            </form>
        </div>

    </div>

    {{-- Panneau droit : aide --}}
    <div class="space-y-4">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <h3 class="text-sm font-semibold text-gray-700 mb-3">Conseils</h3>
            <div class="space-y-3 text-xs text-gray-500">
                <div class="flex items-start gap-2">
                    <i class="fas fa-check-circle text-green-500 mt-0.5"></i>
                    <p>Sauvegardez votre fichier Excel en <strong>CSV (séparateur : point-virgule)</strong> depuis Fichier → Enregistrer sous.</p>
                </div>
                <div class="flex items-start gap-2">
                    <i class="fas fa-check-circle text-green-500 mt-0.5"></i>
                    <p>Le fichier doit être encodé en <strong>UTF-8</strong> pour les accents.</p>
                </div>
                <div class="flex items-start gap-2">
                    <i class="fas fa-check-circle text-green-500 mt-0.5"></i>
                    <p>Utilisez <code class="bg-gray-100 px-1 rounded">.</code> ou <code class="bg-gray-100 px-1 rounded">,</code> comme séparateur décimal (les deux sont acceptés).</p>
                </div>
                <div class="flex items-start gap-2">
                    <i class="fas fa-info-circle text-blue-500 mt-0.5"></i>
                    <p>Maximum <strong>5 000 articles</strong> par import. Pour plus, découpez en plusieurs fichiers.</p>
                </div>
                <div class="flex items-start gap-2">
                    <i class="fas fa-info-circle text-blue-500 mt-0.5"></i>
                    <p>Si <code class="bg-gray-100 px-1 rounded">tva</code> est vide, 18% est appliqué par défaut.</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <h3 class="text-sm font-semibold text-gray-700 mb-2">Exemple de fichier</h3>
            <pre class="text-xs bg-gray-50 rounded p-3 overflow-x-auto text-gray-600">reference;designation;marque;...
REF-001;Clou 100mm;STANLEY;...
REF-002;Marteau 500g;FACOM;...</pre>
        </div>
    </div>
</div>
@endsection
