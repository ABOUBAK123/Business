@extends('layouts.app')
@section('title', isset($user) ? 'Modifier utilisateur' : 'Nouvel utilisateur')
@section('page-title', isset($user) ? 'Modifier : ' . $user->name : 'Nouvel utilisateur')

@section('content')
<div class="max-w-lg">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        @if($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 rounded-lg p-3 mb-4 text-sm">
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
        @endif

        <form method="POST" action="{{ isset($user) ? route('users.update', $user) : route('users.store') }}">
            @csrf
            @if(isset($user)) @method('PUT') @endif

            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Nom complet *</label>
                    <input type="text" name="name" value="{{ old('name', $user->name ?? '') }}" required
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Email *</label>
                    <input type="email" name="email" value="{{ old('email', $user->email ?? '') }}" required
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Téléphone</label>
                    <input type="text" name="phone" value="{{ old('phone', $user->phone ?? '') }}"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                </div>

                @if(!isset($user))
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Mot de passe *</label>
                        <input type="password" name="password" required
                               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Confirmer *</label>
                        <input type="password" name="password_confirmation" required
                               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
                @else
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Nouveau mot de passe</label>
                        <input type="password" name="password"
                               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500"
                               placeholder="Laisser vide = inchangé">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Confirmer</label>
                        <input type="password" name="password_confirmation"
                               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
                @endif

                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Rôle *</label>
                    <select name="role" required class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                        <option value="">— Sélectionner —</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->name }}" {{ old('role', isset($user) ? $user->roles->first()?->name : '') === $role->name ? 'selected' : '' }}>
                                {{ ucfirst(str_replace('_', ' ', $role->name)) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Succursale assignée</label>
                    <select name="branch_id" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                        <option value="">— Toutes les succursales —</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" {{ old('branch_id', $user->branch_id ?? '') == $branch->id ? 'selected' : '' }}>
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                @if(isset($user) && $user->id !== auth()->id())
                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                    <input type="checkbox" name="is_active" value="1"
                           {{ old('is_active', $user->is_active ?? true) ? 'checked' : '' }}
                           class="rounded border-gray-300 text-blue-600">
                    Compte actif
                </label>
                @endif
            </div>

            <div class="flex gap-3 mt-6">
                <button type="submit" class="bg-blue-600 text-white px-6 py-2.5 rounded-lg text-sm font-semibold hover:bg-blue-700">
                    {{ isset($user) ? 'Enregistrer' : 'Créer l\'utilisateur' }}
                </button>
                <a href="{{ route('users.index') }}" class="px-6 py-2.5 border border-gray-200 text-gray-600 rounded-lg text-sm hover:bg-gray-50">
                    Annuler
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
