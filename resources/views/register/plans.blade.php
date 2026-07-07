<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Choisir un plan — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen">
    <div class="max-w-5xl mx-auto px-4 py-16">
        <div class="text-center mb-12">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-blue-600 rounded-2xl mb-4">
                <i class="fas fa-store text-white text-2xl"></i>
            </div>
            <h1 class="text-3xl font-bold text-gray-900">{{ config('app.name') }}</h1>
            <p class="text-gray-500 mt-2">Choisissez le plan qui correspond à votre activité</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            @foreach($plans as $plan)
            <div class="bg-white rounded-2xl shadow-sm border-2 {{ $plan->slug === 'pro' ? 'border-blue-500' : 'border-gray-100' }} overflow-hidden flex flex-col">
                @if($plan->slug === 'pro')
                <div class="bg-blue-500 text-white text-center text-xs font-bold py-1.5">POPULAIRE</div>
                @endif
                <div class="p-6 flex-1 flex flex-col">
                    <h3 class="text-lg font-bold text-gray-800">{{ $plan->name }}</h3>
                    <div class="mt-3 mb-4">
                        @if($plan->monthly_price == 0)
                            <span class="text-3xl font-bold text-gray-900">Gratuit</span>
                            @if($plan->trial_days)
                            <p class="text-xs text-green-600 font-medium mt-1">{{ $plan->trial_days }} jours d'essai</p>
                            @endif
                        @elseif($plan->monthly_price < 0)
                            <span class="text-2xl font-bold text-gray-900">Sur devis</span>
                        @else
                            <span class="text-3xl font-bold text-gray-900">{{ number_format($plan->monthly_price, 0, ',', ' ') }}</span>
                            <span class="text-sm text-gray-500"> FCFA/mois</span>
                        @endif
                    </div>

                    <ul class="space-y-2 flex-1 text-sm text-gray-600">
                        <li class="flex items-center gap-2">
                            <i class="fas fa-check text-green-500 w-4"></i>
                            <span>{{ $plan->max_branches == -1 ? 'Succursales illimitées' : $plan->max_branches . ' succursale(s)' }}</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <i class="fas fa-check text-green-500 w-4"></i>
                            <span>{{ $plan->max_articles == -1 ? 'Articles illimités' : number_format($plan->max_articles, 0) . ' articles' }}</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <i class="fas fa-check text-green-500 w-4"></i>
                            <span>{{ $plan->max_users == -1 ? 'Utilisateurs illimités' : $plan->max_users . ' utilisateur(s)' }}</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <i class="{{ $plan->has_advanced_reports ? 'fas fa-check text-green-500' : 'fas fa-times text-gray-300' }} w-4"></i>
                            <span class="{{ $plan->has_advanced_reports ? '' : 'text-gray-400' }}">Rapports avancés</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <i class="{{ $plan->has_api_access ? 'fas fa-check text-green-500' : 'fas fa-times text-gray-300' }} w-4"></i>
                            <span class="{{ $plan->has_api_access ? '' : 'text-gray-400' }}">Accès API</span>
                        </li>
                    </ul>

                    <a href="{{ route('register.form', ['plan' => $plan->slug]) }}"
                       class="mt-6 block text-center py-2.5 px-4 rounded-xl text-sm font-semibold transition
                              {{ $plan->slug === 'pro' ? 'bg-blue-600 text-white hover:bg-blue-700' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                        @if($plan->monthly_price < 0)
                            Nous contacter
                        @elseif($plan->monthly_price == 0)
                            Démarrer l'essai gratuit
                        @else
                            Commencer
                        @endif
                    </a>
                </div>
            </div>
            @endforeach
        </div>

        <!-- Support Contact Section -->
        <div class="bg-white rounded-2xl shadow-sm border border-blue-200 p-8 mt-12 text-center">
            <h3 class="text-lg font-bold text-gray-900 mb-4">💬 Besoin d'aide pour choisir votre plan?</h3>
            <p class="text-gray-600 text-sm mb-6">Notre équipe est disponible pour répondre à toutes vos questions</p>
            <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                <a href="tel:010142004609" class="inline-flex items-center gap-2 bg-blue-600 text-white px-6 py-2.5 rounded-lg text-sm font-semibold hover:bg-blue-700 transition">
                    <i class="fas fa-phone"></i> Appeler: 01 01 42 00 46 09
                </a>
                <a href="https://wa.me/22510142004609" target="_blank" class="inline-flex items-center gap-2 bg-green-600 text-white px-6 py-2.5 rounded-lg text-sm font-semibold hover:bg-green-700 transition">
                    <i class="fab fa-whatsapp"></i> WhatsApp
                </a>
            </div>
        </div>

        <p class="text-center text-sm text-gray-400 mt-8">
            Déjà inscrit ? <a href="{{ route('login') }}" class="text-blue-600 hover:underline">Se connecter</a>
        </p>
        <p class="text-center mt-3">
            <a href="{{ route('register.commissioner') }}" class="inline-flex items-center gap-2 bg-amber-500 text-white px-5 py-2 rounded-lg text-sm font-semibold hover:bg-amber-600 transition">
                <i class="fas fa-user-tie"></i> Espace commissionnaire
            </a>
        </p>
        <p class="text-center text-xs text-gray-400 mt-3">
            <a href="{{ route('register.plans') }}" class="hover:underline">Actualiser les plans</a>
        </p>
    </div>
</body>
</html>
