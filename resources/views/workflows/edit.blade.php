@extends('layouts.app')
@section('title', 'Modifier le workflow')
@section('page-title', 'Modifier le workflow')
@section('content')

<div class="max-w-3xl mx-auto">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <form method="POST" action="{{ route('workflows.update', $workflow) }}" class="space-y-5">
            @csrf @method('PUT')
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nom</label>
                <input type="text" name="name" value="{{ old('name', $workflow->name) }}" required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <textarea name="description" rows="2"
                          class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">{{ old('description', $workflow->description) }}</textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Statut</label>
                <select name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    @foreach(['active'=>'Actif','inactive'=>'Inactif','archived'=>'Archivé'] as $v => $l)
                    <option value="{{ $v }}" {{ $workflow->status === $v ? 'selected' : '' }}>{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="bg-indigo-600 text-white px-6 py-2.5 rounded-lg text-sm font-medium hover:bg-indigo-700">
                    <i class="fas fa-save mr-2"></i>Enregistrer
                </button>
                <a href="{{ route('workflows.show', $workflow) }}" class="bg-gray-100 text-gray-700 px-6 py-2.5 rounded-lg text-sm hover:bg-gray-200">Annuler</a>
            </div>
        </form>
    </div>
</div>
@endsection
