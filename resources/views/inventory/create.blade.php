@extends('layouts.app')
@section('title', 'Nouvel inventaire')
@section('page-title', 'Nouvel inventaire physique')

@section('content')
<div class="max-w-lg mx-auto">
    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-4 text-sm text-blue-700">
        <i class="fas fa-info-circle mr-1"></i>
        L'inventaire sera pré-rempli avec le stock théorique de tous vos articles.
        Vous n'aurez qu'à saisir les quantités réellement comptées.
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <form method="POST" action="{{ route('inventory.store') }}">
            @csrf
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Boutique *</label>
                    <select name="branch_id" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                        @foreach($branches as $b)
                        <option value="{{ $b->id }}" {{ old('branch_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date de l'inventaire *</label>
                    <input type="date" name="date" value="{{ old('date', today()->toDateString()) }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                    <textarea name="notes" rows="3" placeholder="Inventaire fin de mois, audit..."
                              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">{{ old('notes') }}</textarea>
                </div>
                <button type="submit"
                        class="w-full bg-blue-700 text-white py-2.5 rounded-lg font-semibold hover:bg-blue-800 flex items-center justify-center gap-2">
                    <i class="fas fa-play"></i> Démarrer l'inventaire
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
