<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Abonnement expiré — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center">
    <div class="max-w-md mx-auto px-4 text-center">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
            <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-lock text-red-500 text-2xl"></i>
            </div>
            <h1 class="text-xl font-bold text-gray-900 mb-2">Accès suspendu</h1>
            <p class="text-gray-500 text-sm mb-6">
                Votre abonnement a expiré ou a été suspendu. Renouvelez votre plan pour accéder à nouveau à votre boutique.
            </p>

            @auth
            <div class="bg-gray-50 rounded-xl p-4 mb-6 text-left text-sm space-y-2">
                <div class="flex justify-between text-gray-600">
                    <span>Boutique</span>
                    <span class="font-medium">{{ auth()->user()->tenant?->shop_name }}</span>
                </div>
                <div class="flex justify-between text-gray-600">
                    <span>Statut</span>
                    <span class="text-red-600 font-medium capitalize">{{ auth()->user()->tenant?->status }}</span>
                </div>
            </div>

            @if(auth()->user()->isOwner())
            <a href="{{ route('account.activation.index') }}"
               class="block w-full bg-blue-600 text-white py-2.5 rounded-xl text-sm font-semibold hover:bg-blue-700 mb-3">
                Ouvrir l’activation de compte
            </a>
            @endif
            @endauth

            <div class="space-y-2">
                <a href="{{ route('account.activation.index') }}"
                   class="block w-full bg-blue-600 text-white py-2.5 rounded-xl text-sm font-semibold hover:bg-blue-700">
                    Réactiver mon compte
                </a>
                <a href="{{ route('plans') }}"
                   class="block w-full bg-gray-100 text-gray-700 py-2.5 rounded-xl text-sm font-semibold hover:bg-gray-200">
                    Voir les plans publics
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="block w-full text-gray-500 text-sm py-2 hover:text-gray-700">
                        Se déconnecter
                    </button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
