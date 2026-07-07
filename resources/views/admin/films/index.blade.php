@extends('layouts.app')
@section('title', 'Catalogue Films')
@section('page-title', 'Catalogue Films — CineAfrik')
@section('page-subtitle', 'Gérez les films disponibles sur la plateforme')

@section('content')

@if(session('success'))
<div class="mb-4 px-4 py-3 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm flex items-center gap-2">
    <i class="fas fa-check-circle"></i> {{ session('success') }}
</div>
@endif

{{-- Barre de recherche + bouton --}}
<div class="flex flex-wrap justify-between items-center gap-3 mb-5">
    <form method="GET" class="flex gap-2 flex-wrap">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Titre, genre…"
               class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 w-52">
        <select name="status" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
            <option value="">Tous les statuts</option>
            <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Actif</option>
            <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Inactif</option>
        </select>
        <button class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg text-sm hover:bg-gray-200">
            <i class="fas fa-search mr-1"></i>Filtrer
        </button>
        @if(request()->hasAny(['search','status']))
        <a href="{{ route('admin.films.index') }}" class="text-gray-500 px-3 py-2 text-sm hover:text-gray-700">
            <i class="fas fa-times"></i> Réinitialiser
        </a>
        @endif
    </form>
    <a href="{{ route('admin.films.create') }}"
       class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-indigo-700 flex items-center gap-2">
        <i class="fas fa-plus"></i> Ajouter un film
    </a>
</div>

{{-- Tableau --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
                <th class="text-left text-xs font-semibold text-gray-500 uppercase px-5 py-3">Film</th>
                <th class="text-left text-xs font-semibold text-gray-500 uppercase px-5 py-3">Genre</th>
                <th class="text-left text-xs font-semibold text-gray-500 uppercase px-5 py-3">Durée</th>
                <th class="text-left text-xs font-semibold text-gray-500 uppercase px-5 py-3">Prix</th>
                <th class="text-left text-xs font-semibold text-gray-500 uppercase px-5 py-3">Statut</th>
                <th class="text-left text-xs font-semibold text-gray-500 uppercase px-5 py-3">Ajouté le</th>
                <th class="text-left text-xs font-semibold text-gray-500 uppercase px-5 py-3">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @forelse($films as $film)
            <tr class="hover:bg-gray-50">
                {{-- Affiche + titre --}}
                <td class="px-5 py-3">
                    <div class="flex items-center gap-3">
                        @if($film->poster_path)
                            <img src="{{ Storage::url($film->poster_path) }}" alt="{{ $film->title }}"
                                 class="w-10 h-14 object-cover rounded shadow-sm flex-shrink-0">
                        @else
                            <div class="w-10 h-14 bg-gray-200 rounded flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-film text-gray-400 text-xs"></i>
                            </div>
                        @endif
                        <div>
                            <p class="text-sm font-medium text-gray-800">{{ $film->title }}</p>
                            @if($film->original_title && $film->original_title !== $film->title)
                            <p class="text-xs text-gray-400 italic">{{ $film->original_title }}</p>
                            @endif
                            @if($film->release_year)
                            <p class="text-xs text-gray-400">{{ $film->release_year }}</p>
                            @endif
                        </div>
                    </div>
                </td>
                <td class="px-5 py-3">
                    <span class="text-xs px-2 py-1 rounded-full bg-purple-50 text-purple-700">
                        {{ $film->genre ?? '—' }}
                    </span>
                </td>
                <td class="px-5 py-3 text-sm text-gray-600">{{ $film->duration_formatted }}</td>
                <td class="px-5 py-3 text-sm font-medium text-gray-800">
                    {{ number_format($film->price, 0, ',', ' ') }} {{ $film->currency }}
                </td>
                <td class="px-5 py-3">
                    <form method="POST" action="{{ route('admin.films.toggle', $film) }}">
                        @csrf @method('PATCH')
                        <button type="submit"
                            class="text-xs px-2.5 py-1 rounded-full font-medium transition
                                {{ $film->is_active
                                    ? 'bg-green-100 text-green-700 hover:bg-green-200'
                                    : 'bg-gray-100 text-gray-500 hover:bg-gray-200' }}">
                            {{ $film->is_active ? 'Actif' : 'Inactif' }}
                        </button>
                    </form>
                </td>
                <td class="px-5 py-3 text-xs text-gray-500">{{ $film->created_at->format('d/m/Y') }}</td>
                <td class="px-5 py-3">
                    <div class="flex gap-2">
                        <a href="{{ route('admin.films.edit', $film) }}"
                           class="text-yellow-600 hover:text-yellow-800 text-sm p-1" title="Modifier">
                            <i class="fas fa-edit"></i>
                        </a>
                        <form method="POST" action="{{ route('admin.films.destroy', $film) }}"
                              onsubmit="return confirm('Supprimer « {{ addslashes($film->title) }} » ?')">
                            @csrf @method('DELETE')
                            <button class="text-red-500 hover:text-red-700 text-sm p-1" title="Supprimer">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="px-5 py-12 text-center">
                    <i class="fas fa-film text-gray-300 text-4xl mb-3 block"></i>
                    <p class="text-gray-400 text-sm">Aucun film dans le catalogue</p>
                    <a href="{{ route('admin.films.create') }}"
                       class="mt-3 inline-block text-indigo-600 text-sm hover:underline">
                        Ajouter le premier film
                    </a>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    @if($films->hasPages())
    <div class="px-5 py-4 border-t border-gray-100">
        {{ $films->withQueryString()->links() }}
    </div>
    @endif
</div>

{{-- Stats rapides --}}
<div class="mt-4 text-xs text-gray-400 text-right">
    {{ $films->total() }} film(s) au total ·
    {{ \App\Models\Film::where('is_active', true)->count() }} actif(s)
</div>

@endsection
