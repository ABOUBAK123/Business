@extends('layouts.app')
@section('title', 'Modifier l\'utilisateur')
@section('page-title', 'Modifier l\'utilisateur')
@section('content')
<div class="max-w-lg mx-auto">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <form method="POST" action="{{ route('admin.users.update', $user) }}" class="space-y-4">
            @csrf @method('PUT')
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nom</label>
                <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Rôle</label>
                <select name="role" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    @foreach(['user'=>'Utilisateur','manager'=>'Manager','signer'=>'Signataire','admin'=>'Administrateur'] as $v => $l)
                    <option value="{{ $v }}" {{ $user->role === $v ? 'selected' : '' }}>{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Statut</label>
                <select name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    @foreach(['active'=>'Actif','inactive'=>'Inactif','suspended'=>'Suspendu'] as $v => $l)
                    <option value="{{ $v }}" {{ $user->status === $v ? 'selected' : '' }}>{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="bg-indigo-600 text-white px-6 py-2.5 rounded-lg text-sm font-medium hover:bg-indigo-700">
                    <i class="fas fa-save mr-2"></i>Enregistrer
                </button>
                <a href="{{ route('admin.users.index') }}" class="bg-gray-100 text-gray-700 px-6 py-2.5 rounded-lg text-sm hover:bg-gray-200">Annuler</a>
            </div>
        </form>
    </div>
</div>
@endsection
