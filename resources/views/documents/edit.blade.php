@extends('layouts.app')
@section('title', 'Modifier le document')
@section('page-title', 'Modifier le document')
@section('content')

<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <form method="POST" action="{{ route('documents.update', $document) }}" class="space-y-5">
            @csrf @method('PUT')
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Titre</label>
                <input type="text" name="title" value="{{ old('title', $document->title) }}" required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <textarea name="description" rows="3"
                          class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">{{ old('description', $document->description) }}</textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Statut</label>
                <select name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    @foreach(['draft'=>'Brouillon','active'=>'Actif','archived'=>'Archivé'] as $val => $label)
                    <option value="{{ $val }}" {{ $document->status === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="bg-indigo-600 text-white px-6 py-2.5 rounded-lg text-sm font-medium hover:bg-indigo-700">
                    <i class="fas fa-save mr-2"></i>Enregistrer
                </button>
                <a href="{{ route('documents.show', $document) }}" class="bg-gray-100 text-gray-700 px-6 py-2.5 rounded-lg text-sm hover:bg-gray-200">Annuler</a>
            </div>
        </form>
    </div>
</div>
@endsection
