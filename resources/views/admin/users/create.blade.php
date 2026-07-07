@extends('layouts.app')
@section('title', 'Créer un utilisateur')
@section('page-title', 'Créer un Utilisateur')
@section('content')
<div class="max-w-lg mx-auto">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nom <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ old('name') }}" required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email <span class="text-red-500">*</span></label>
                <input type="email" name="email" value="{{ old('email') }}" required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Mot de passe <span class="text-red-500">*</span></label>
                <input type="password" name="password" required minlength="8"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Téléphone</label>
                <input type="tel" name="phone" value="{{ old('phone') }}" placeholder="+225 00 00 00 00"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Type <span class="text-red-500">*</span></label>
                <select name="administration_type" required class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">-- Sélectionner un type --</option>
                    <option value="emitter" {{ old('administration_type') === 'emitter' ? 'selected' : '' }}>Émettrice</option>
                    <option value="recipient" {{ old('administration_type') === 'recipient' ? 'selected' : '' }}>Destinataire</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Administration <span class="text-red-500">*</span></label>
                <select name="administration_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">-- Sélectionner une administration --</option>
                    @php
                        $issuingAdmins = App\Models\IssuingAdministration::orderBy('name')->get();
                        $recipientAdmins = App\Models\RecipientAdministration::orderBy('name')->get();
                    @endphp
                    <optgroup label="Administrations Émettrice">
                        @foreach($issuingAdmins as $admin)
                            <option value="{{ $admin->id }}" data-type="emitter" {{ old('administration_id') === $admin->id && old('administration_type') === 'emitter' ? 'selected' : '' }}>
                                {{ $admin->name }} ({{ $admin->code }})
                            </option>
                        @endforeach
                    </optgroup>
                    <optgroup label="Administrations Destinataire">
                        @foreach($recipientAdmins as $admin)
                            <option value="{{ $admin->id }}" data-type="recipient" {{ old('administration_id') === $admin->id && old('administration_type') === 'recipient' ? 'selected' : '' }}>
                                {{ $admin->name }} ({{ $admin->code }})
                            </option>
                        @endforeach
                    </optgroup>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Rôle système</label>
                <select name="role" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    @foreach(['user'=>'Utilisateur','manager'=>'Manager','signer'=>'Signataire','admin'=>'Administrateur'] as $v => $l)
                    <option value="{{ $v }}" {{ old('role') === $v ? 'selected' : '' }}>{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="bg-indigo-600 text-white px-6 py-2.5 rounded-lg text-sm font-medium hover:bg-indigo-700">
                    Créer l'utilisateur
                </button>
                <a href="{{ route('admin.users.index') }}" class="bg-gray-100 text-gray-700 px-6 py-2.5 rounded-lg text-sm hover:bg-gray-200">Annuler</a>
            </div>
        </form>
    </div>
</div>
@endsection
