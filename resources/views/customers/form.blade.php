@extends('layouts.app')
@section('title', isset($customer) ? 'Modifier client' : 'Nouveau client')
@section('page-title', isset($customer) ? 'Modifier : ' . $customer->name : 'Nouveau client')

@section('content')
<div class="max-w-xl">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        @if($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 rounded-lg p-3 mb-4 text-sm">
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
        @endif

        <form method="POST" action="{{ isset($customer) ? route('customers.update', $customer) : route('customers.store') }}">
            @csrf
            @if(isset($customer)) @method('PUT') @endif

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div class="col-span-2">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Nom complet *</label>
                    <input type="text" name="name" value="{{ old('name', $customer->name ?? '') }}" required
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Type *</label>
                    <select name="type" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                        <option value="individual" {{ old('type', $customer->type ?? '') === 'individual' ? 'selected' : '' }}>Particulier</option>
                        <option value="professional" {{ old('type', $customer->type ?? '') === 'professional' ? 'selected' : '' }}>Professionnel</option>
                        <option value="wholesale" {{ old('type', $customer->type ?? '') === 'wholesale' ? 'selected' : '' }}>Grossiste</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Téléphone</label>
                    <input type="text" name="phone" value="{{ old('phone', $customer->phone ?? '') }}"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Email</label>
                    <input type="email" name="email" value="{{ old('email', $customer->email ?? '') }}"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Entreprise</label>
                    <input type="text" name="company_name" value="{{ old('company_name', $customer->company_name ?? '') }}"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="col-span-2">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Adresse</label>
                    <input type="text" name="address" value="{{ old('address', $customer->address ?? '') }}"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Limite de crédit (FCFA)</label>
                    <input type="number" name="credit_limit" value="{{ old('credit_limit', $customer->credit_limit ?? 0) }}" min="0"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Remise par défaut (%)</label>
                    <input type="number" name="discount_rate" value="{{ old('discount_rate', $customer->discount_rate ?? 0) }}" min="0" max="100" step="0.1"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="col-span-2">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Notes</label>
                    <textarea name="notes" rows="2"
                              class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 resize-none"
                              placeholder="Notes internes...">{{ old('notes', $customer->notes ?? '') }}</textarea>
                </div>
            </div>

            <div class="flex gap-3">
                <button type="submit" class="bg-blue-600 text-white px-6 py-2.5 rounded-lg text-sm font-semibold hover:bg-blue-700">
                    {{ isset($customer) ? 'Enregistrer' : 'Créer le client' }}
                </button>
                <a href="{{ route('customers.index') }}" class="px-6 py-2.5 border border-gray-200 text-gray-600 rounded-lg text-sm hover:bg-gray-50">
                    Annuler
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
