@extends('layouts.app')
@section('title', 'Articles')
@section('page-title', 'Catalogue Articles')

@section('content')
<div class="flex items-center justify-between mb-4">
    <form class="flex gap-2">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Rechercher..."
               class="border border-gray-200 rounded-lg px-3 py-2 text-sm w-64 focus:outline-none focus:ring-2 focus:ring-blue-500">
        <select name="category_id" class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="">Toutes catégories</option>
            @foreach($categories as $cat)
                <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
            @endforeach
        </select>
        <button class="bg-gray-100 text-gray-700 px-3 py-2 rounded-lg text-sm hover:bg-gray-200">
            <i class="fas fa-search"></i>
        </button>
    </form>
    <div class="flex gap-2">
        <a href="{{ route('articles.bulk-qr') }}" id="bulkQrBtn"
           class="hidden flex items-center gap-2 border border-gray-200 text-gray-700 px-3 py-2 rounded-lg text-sm hover:bg-gray-50">
            <i class="fas fa-qrcode"></i> QR en lot
        </a>
        <a href="{{ route('articles.create') }}"
           class="flex items-center gap-2 bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700">
            <i class="fas fa-plus"></i> Nouvel article
        </a>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
                <th class="w-8 px-4 py-3"><input type="checkbox" id="selectAll" class="rounded"></th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Référence</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Désignation</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Catégorie</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Prix TTC</th>
                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">QR Code</th>
                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Statut</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @forelse($articles as $article)
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-4 py-3">
                        <input type="checkbox" name="article_ids[]" value="{{ $article->id }}" class="article-cb rounded">
                    </td>
                    <td class="px-4 py-3 font-mono text-xs text-gray-500">{{ $article->reference }}</td>
                    <td class="px-4 py-3">
                        <p class="font-medium text-gray-800">{{ $article->designation }}</p>
                        @if($article->short_description)
                            <p class="text-xs text-gray-400 truncate max-w-xs">{{ $article->short_description }}</p>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-gray-500">{{ $article->category?->name ?? '—' }}</td>
                    <td class="px-4 py-3 text-right font-semibold text-gray-800">
                        {{ number_format($article->sale_price_ttc, 0, ',', ' ') }} FCFA
                    </td>
                    <td class="px-4 py-3 text-center">
                        @if($article->mainQrCode)
                            <a href="{{ route('articles.qr', $article) }}" target="_blank"
                               class="inline-flex items-center gap-1 text-blue-600 hover:text-blue-800 text-xs">
                                <i class="fas fa-qrcode"></i> Voir
                            </a>
                        @else
                            <span class="text-gray-400 text-xs">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-center">
                        @if($article->is_active)
                            <span class="bg-green-100 text-green-700 text-xs px-2 py-0.5 rounded-full">Actif</span>
                        @else
                            <span class="bg-gray-100 text-gray-500 text-xs px-2 py-0.5 rounded-full">Inactif</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center justify-end gap-2">
                            <a href="{{ route('articles.show', $article) }}" class="text-gray-400 hover:text-blue-600" title="Voir">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('articles.edit', $article) }}" class="text-gray-400 hover:text-yellow-600" title="Modifier">
                                <i class="fas fa-pen"></i>
                            </a>
                            <form method="POST" action="{{ route('articles.destroy', $article) }}"
                                  onsubmit="return confirm('Supprimer cet article ?')">
                                @csrf @method('DELETE')
                                <button class="text-gray-400 hover:text-red-600" title="Supprimer">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="px-4 py-12 text-center text-gray-400">
                    <i class="fas fa-boxes text-3xl mb-2 block"></i>
                    Aucun article. <a href="{{ route('articles.create') }}" class="text-blue-600">Créer le premier</a>
                </td></tr>
            @endforelse
        </tbody>
    </table>
    @if($articles->hasPages())
        <div class="px-4 py-3 border-t border-gray-100">{{ $articles->links() }}</div>
    @endif
</div>
@endsection

@push('scripts')
<script>
document.getElementById('selectAll').addEventListener('change', function() {
    document.querySelectorAll('.article-cb').forEach(cb => cb.checked = this.checked);
    updateBulkBtn();
});
document.querySelectorAll('.article-cb').forEach(cb => cb.addEventListener('change', updateBulkBtn));
function updateBulkBtn() {
    const checked = document.querySelectorAll('.article-cb:checked');
    const btn = document.getElementById('bulkQrBtn');
    if (checked.length > 0) {
        btn.classList.remove('hidden');
        btn.href = '{{ route("articles.bulk-qr") }}?ids[]=' + Array.from(checked).map(c => c.value).join('&ids[]=');
    } else {
        btn.classList.add('hidden');
    }
}
</script>
@endpush
