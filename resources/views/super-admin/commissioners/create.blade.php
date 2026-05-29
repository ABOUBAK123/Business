@extends('layouts.app')

@section('title', 'Nouveau commissionnaire')
@section('page-title', 'Créer un commissionnaire')

@section('content')
<div class="max-w-lg mx-auto">
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
        <form method="POST" action="{{ route('super-admin.commissioners.store') }}" class="space-y-4">
            @csrf

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Nom complet <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ old('name') }}" required
                       class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('name') border-red-400 @enderror">
                @error('name')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Email <span class="text-red-500">*</span></label>
                <input type="email" name="email" value="{{ old('email') }}" required
                       class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('email') border-red-400 @enderror">
                @error('email')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Téléphone</label>
                <input type="text" name="phone" value="{{ old('phone') }}"
                       class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Mot de passe <span class="text-red-500">*</span></label>
                <div class="relative">
                    <input type="password" id="password" name="password" required
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 pr-9 @error('password') border-red-400 @enderror">
                    <button type="button" onclick="togglePwd()" class="absolute right-3 top-2.5 text-gray-400 hover:text-gray-600">
                        <i id="eyeIcon" class="fas fa-eye text-sm"></i>
                    </button>
                </div>
                @error('password')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
            </div>

            <p class="text-xs text-gray-400 bg-blue-50 rounded-lg px-3 py-2">
                <i class="fas fa-info-circle text-blue-400 mr-1"></i>
                Le commissionnaire pourra créer des boutiques depuis son espace et recevra 3% du montant des abonnements mensuels.
            </p>

            <div class="flex gap-3 pt-2">
                <button type="submit"
                        class="bg-blue-600 text-white px-5 py-2 rounded-lg text-sm font-semibold hover:bg-blue-700 transition">
                    Créer le compte
                </button>
                <a href="{{ route('super-admin.commissioners.index') }}"
                   class="border border-gray-200 text-gray-600 px-5 py-2 rounded-lg text-sm hover:bg-gray-50 transition">
                    Annuler
                </a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function togglePwd() {
    const input = document.getElementById('password');
    const icon  = document.getElementById('eyeIcon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}
</script>
@endpush
