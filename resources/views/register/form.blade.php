<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer votre boutique — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen py-10">
    <div class="max-w-xl mx-auto px-4">
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-12 h-12 bg-blue-600 rounded-xl mb-3">
                <i class="fas fa-store text-white text-lg"></i>
            </div>
            <h1 class="text-2xl font-bold text-gray-900">Créer votre boutique</h1>
            <p class="text-sm text-gray-500 mt-1">Plan choisi : <span class="font-semibold text-blue-600">{{ $plan->name }}</span></p>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            @if($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 rounded-lg p-3 mb-4 text-sm">
                <ul class="list-disc list-inside space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            <form method="POST" action="{{ route('register.shop') }}">
                @csrf
                <input type="hidden" name="plan_slug" value="{{ $plan->slug }}">

                <h3 class="text-sm font-semibold text-gray-700 mb-3 uppercase tracking-wide">Informations de la boutique</h3>
                <div class="space-y-3 mb-5">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Nom de la boutique *</label>
                        <input type="text" name="shop_name" value="{{ old('shop_name') }}" required
                               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Ex: Quincaillerie du Centre">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Ville</label>
                            <input type="text" name="city" value="{{ old('city') }}"
                                   class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500"
                                   placeholder="Abidjan">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Téléphone</label>
                            <input type="text" name="shop_phone" value="{{ old('shop_phone') }}"
                                   class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500"
                                   placeholder="+225 07 00 00 00">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Adresse</label>
                        <input type="text" name="address" value="{{ old('address') }}"
                               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500"
                               placeholder="Rue, quartier...">
                    </div>
                </div>

                <div class="border-t border-gray-100 pt-5">
                    <h3 class="text-sm font-semibold text-gray-700 mb-3 uppercase tracking-wide">Compte propriétaire</h3>
                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Nom complet *</label>
                            <input type="text" name="name" value="{{ old('name') }}" required
                                   class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500"
                                   placeholder="Jean Kouassi">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Adresse email *</label>
                            <input type="email" name="email" value="{{ old('email') }}" required
                                   class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500"
                                   placeholder="jean@exemple.com">
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Mot de passe *</label>
                                <input type="password" name="password" required
                                       class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500"
                                       placeholder="Min. 8 caractères">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Confirmer *</label>
                                <input type="password" name="password_confirmation" required
                                       class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500"
                                       placeholder="Répéter">
                            </div>
                        </div>
                    </div>
                </div>

                <button type="submit"
                        class="w-full mt-6 bg-blue-600 text-white py-3 rounded-xl text-sm font-bold hover:bg-blue-700 flex items-center justify-center gap-2">
                    <i class="fas fa-rocket"></i>
                    Créer ma boutique
                </button>

                <p class="text-center text-xs text-gray-400 mt-3">
                    En vous inscrivant, vous acceptez nos conditions d'utilisation.
                </p>
            </form>
        </div>

        <p class="text-center text-sm text-gray-400 mt-4">
            <a href="{{ route('register.plans') }}" class="text-blue-600 hover:underline">← Changer de plan</a>
        </p>
    </div>
</body>
</html>
