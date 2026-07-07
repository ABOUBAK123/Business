@extends('layouts.app')
@section('title', 'Ajouter un film')
@section('page-title', 'Ajouter un film')
@section('page-subtitle', 'Renseignez les informations du nouveau film')

@section('content')

<div class="max-w-3xl">
    <form method="POST" action="{{ route('admin.films.store') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf

        {{-- Informations générales --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="text-sm font-semibold text-gray-700 mb-4 flex items-center gap-2">
                <i class="fas fa-info-circle text-indigo-500"></i> Informations générales
            </h3>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Titre <span class="text-red-500">*</span></label>
                    <input type="text" name="title" value="{{ old('title') }}" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('title') border-red-400 @enderror">
                    @error('title')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Titre original</label>
                    <input type="text" name="original_title" value="{{ old('original_title') }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Description</label>
                    <textarea name="description" rows="4"
                              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-none">{{ old('description') }}</textarea>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Genre</label>
                    <input type="text" name="genre" value="{{ old('genre') }}" placeholder="Drame, Action, Comédie…"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Année de sortie</label>
                    <input type="number" name="release_year" value="{{ old('release_year') }}" min="1900" max="2100"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Durée (minutes)</label>
                    <input type="number" name="duration" value="{{ old('duration') }}" min="1" max="999"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Statut</label>
                    <div class="flex items-center gap-2 mt-2">
                        <input type="checkbox" name="is_active" id="is_active" value="1"
                               {{ old('is_active', true) ? 'checked' : '' }}
                               class="w-4 h-4 text-indigo-600 rounded border-gray-300">
                        <label for="is_active" class="text-sm text-gray-600">Film actif (visible dans l'app)</label>
                    </div>
                </div>
            </div>
        </div>

        {{-- Affiche & média --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="text-sm font-semibold text-gray-700 mb-4 flex items-center gap-2">
                <i class="fas fa-photo-film text-indigo-500"></i> Affiche & médias
            </h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Affiche (image)</label>
                    <input type="file" name="poster" accept="image/*"
                           class="w-full text-sm text-gray-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                    <p class="text-xs text-gray-400 mt-1">JPG, PNG, WEBP — max 4 Mo</p>
                    @error('poster')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">URL Trailer (YouTube, Vimeo…)</label>
                    <input type="url" name="trailer_url" value="{{ old('trailer_url') }}"
                           placeholder="https://www.youtube.com/watch?v=…"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('trailer_url') border-red-400 @enderror">
                    @error('trailer_url')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">URL flux HLS (manifest .m3u8)</label>
                    <input type="text" name="hls_manifest_url" value="{{ old('hls_manifest_url') }}"
                           placeholder="https://cdn.example.com/films/xyz/index.m3u8"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <p class="text-xs text-gray-400 mt-1">URL utilisée par l'application mobile pour le streaming HLS.</p>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Clé DRM (optionnel)</label>
                    <input type="text" name="drm_key" value="{{ old('drm_key') }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
            </div>
        </div>

        {{-- Prix --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="text-sm font-semibold text-gray-700 mb-4 flex items-center gap-2">
                <i class="fas fa-tag text-indigo-500"></i> Tarification (TVOD)
            </h3>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Prix <span class="text-red-500">*</span></label>
                    <input type="number" name="price" value="{{ old('price', 0) }}" min="0" step="1" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('price') border-red-400 @enderror">
                    @error('price')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Devise</label>
                    <select name="currency" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        @foreach(['XOF'=>'XOF (FCFA)','XAF'=>'XAF (FCFA)','EUR'=>'EUR (€)','USD'=>'USD ($)','GHS'=>'GHS (₵)','NGN'=>'NGN (₦)'] as $code => $label)
                        <option value="{{ $code }}" {{ old('currency', 'XOF') === $code ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="flex gap-3">
            <button type="submit"
                    class="bg-indigo-600 text-white px-6 py-2.5 rounded-lg text-sm font-medium hover:bg-indigo-700">
                <i class="fas fa-save mr-2"></i>Enregistrer
            </button>
            <a href="{{ route('admin.films.index') }}"
               class="bg-gray-100 text-gray-700 px-6 py-2.5 rounded-lg text-sm font-medium hover:bg-gray-200">
                Annuler
            </a>
        </div>
    </form>
</div>

@endsection
