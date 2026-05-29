@extends('layouts.app')
@section('title', isset($branch) ? 'Modifier succursale' : 'Nouvelle succursale')
@section('page-title', isset($branch) ? 'Modifier : ' . $branch->name : 'Nouvelle succursale')

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

        <form method="POST" action="{{ isset($branch) ? route('branches.update', $branch) : route('branches.store') }}">
            @csrf
            @if(isset($branch)) @method('PUT') @endif

            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Nom de la succursale *</label>
                    <input type="text" name="name" value="{{ old('name', $branch->name ?? '') }}" required
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500"
                           placeholder="Succursale Centre-Ville">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Téléphone</label>
                        <input type="text" name="phone" value="{{ old('phone', $branch->phone ?? '') }}"
                               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Email</label>
                        <input type="email" name="email" value="{{ old('email', $branch->email ?? '') }}"
                               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Adresse</label>
                    <input type="text" name="address" value="{{ old('address', $branch->address ?? '') }}"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Responsable</label>
                    <select name="manager_id" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                        <option value="">— Aucun —</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ old('manager_id', $branch->manager_id ?? '') == $user->id ? 'selected' : '' }}>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="space-y-2">
                    <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                        <input type="checkbox" name="is_active" value="1"
                               {{ old('is_active', $branch->is_active ?? true) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-blue-600">
                        Succursale active
                    </label>
                    <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                        <input type="checkbox" name="use_shared_stock" value="1"
                               {{ old('use_shared_stock', $branch->use_shared_stock ?? false) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-blue-600">
                        Utiliser le stock partagé
                    </label>
                </div>
            </div>

            <div class="flex gap-3 mt-6">
                <button type="submit" class="bg-blue-600 text-white px-6 py-2.5 rounded-lg text-sm font-semibold hover:bg-blue-700">
                    {{ isset($branch) ? 'Enregistrer' : 'Créer la succursale' }}
                </button>
                <a href="{{ route('branches.index') }}" class="px-6 py-2.5 border border-gray-200 text-gray-600 rounded-lg text-sm hover:bg-gray-50">
                    Annuler
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
