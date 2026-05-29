@extends('layouts.app')

@section('title', 'Créer une boutique')
@section('page-title', 'Créer une boutique')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
        <form method="POST" action="{{ route('commissioner.shops.store') }}" class="space-y-5">
            @csrf

            <h3 class="text-sm font-semibold text-gray-700 border-b border-gray-100 pb-2">Informations de la boutique</h3>

            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Nom de la boutique <span class="text-red-500">*</span></label>
                    <input type="text" name="shop_name" value="{{ old('shop_name') }}" required
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('shop_name') border-red-400 @enderror">
                    @error('shop_name')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Plan d'abonnement <span class="text-red-500">*</span></label>
                    <select name="subscription_plan_id" required
                            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('subscription_plan_id') border-red-400 @enderror">
                        <option value="">-- Choisir un plan --</option>
                        @foreach($plans as $plan)
                            <option value="{{ $plan->id }}" {{ old('subscription_plan_id') == $plan->id ? 'selected' : '' }}>
                                {{ $plan->name }} — {{ number_format($plan->monthly_price, 0, ',', ' ') }} XOF/mois
                            </option>
                        @endforeach
                    </select>
                    @error('subscription_plan_id')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Téléphone</label>
                    <input type="text" name="phone" value="{{ old('phone') }}"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Ville</label>
                    <input type="text" name="city" value="{{ old('city') }}"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Pays</label>
                    <input type="text" name="country" value="{{ old('country', 'Bénin') }}"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div class="col-span-2">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Adresse</label>
                    <input type="text" name="address" value="{{ old('address') }}"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            <h3 class="text-sm font-semibold text-gray-700 border-b border-gray-100 pb-2 pt-2">Compte propriétaire</h3>

            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Nom complet <span class="text-red-500">*</span></label>
                    <input type="text" name="owner_name" value="{{ old('owner_name') }}" required
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('owner_name') border-red-400 @enderror">
                    @error('owner_name')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Email <span class="text-red-500">*</span></label>
                    <input type="email" name="owner_email" value="{{ old('owner_email') }}" required
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('owner_email') border-red-400 @enderror">
                    @error('owner_email')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Mot de passe <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <input type="password" id="owner_password" name="owner_password" required
                               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 pr-9 @error('owner_password') border-red-400 @enderror">
                        <button type="button" onclick="togglePwd()" class="absolute right-3 top-2.5 text-gray-400 hover:text-gray-600">
                            <i id="eyeIcon" class="fas fa-eye text-sm"></i>
                        </button>
                    </div>
                    @error('owner_password')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                </div>
            </div>

            <p class="text-xs text-gray-400">
                <i class="fas fa-info-circle mr-1"></i>
                La boutique démarrera en période d'essai. Vous recevrez 3% du montant mensuel de l'abonnement comme commission.
            </p>

            <div class="flex gap-3 pt-2">
                <button type="submit"
                        class="bg-blue-600 text-white px-5 py-2 rounded-lg text-sm font-semibold hover:bg-blue-700 transition">
                    Créer la boutique
                </button>
                <a href="{{ route('commissioner.shops') }}"
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
    const input = document.getElementById('owner_password');
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
