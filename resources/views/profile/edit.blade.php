@extends('layouts.app')
@section('title', 'Mon Profil')
@section('page-title', 'Mon profil')
@section('page-subtitle', 'Modifiez vos informations personnelles et votre mot de passe')
@section('content')

<div class="max-w-2xl mx-auto space-y-6">

    {{-- Carte profil --}}
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">

        {{-- En-tete gradient avec photo --}}
        <div class="px-6 py-6 border-b border-gray-100 bg-gradient-to-r from-[#2453d6] to-blue-500 flex items-center gap-5">
            <div class="relative group flex-shrink-0">
                <div class="h-20 w-20 rounded-full overflow-hidden border-4 border-white/40 flex items-center justify-center font-black text-3xl text-white bg-white/20">
                    @if($user->avatar && file_exists(public_path($user->avatar)))
                        <img src="{{ asset($user->avatar) }}" alt="avatar" class="h-full w-full object-cover">
                    @else
                        {{ strtoupper(substr($user->name ?? 'U', 0, 2)) }}
                    @endif
                </div>
                <label class="absolute inset-0 rounded-full bg-black/40 flex flex-col items-center justify-center opacity-0 group-hover:opacity-100 cursor-pointer transition text-white text-[10px] font-medium">
                    <i class="fas fa-camera text-base mb-0.5"></i>Photo
                    <form method="POST" action="{{ route('profile.avatar') }}" enctype="multipart/form-data" id="avatar-form-page">
                        @csrf
                        <input type="file" name="avatar" accept="image/*" class="hidden" onchange="document.getElementById('avatar-form-page').submit()">
                    </form>
                </label>
            </div>
            <div>
                <h2 class="text-xl font-bold text-white">{{ $user->name }}</h2>
                <p class="text-blue-200 text-sm">{{ $user->email }}</p>
                <span class="inline-block mt-1 px-2.5 py-0.5 bg-white/20 rounded-full text-xs text-white font-medium">{{ ucfirst($user->role ?? 'user') }}</span>
            </div>
        </div>

        {{-- Formulaire infos principales --}}
        <form method="POST" action="{{ route('profile.update') }}" class="p-6 space-y-5">
            @csrf @method('PUT')

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Nom d'affichage</label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}"
                        class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#2453d6]">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Adresse e-mail</label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}"
                        class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#2453d6]">
                </div>
            </div>

            <div class="flex justify-end pt-2">
                <button type="submit"
                    class="px-6 py-2.5 bg-[#2453d6] hover:bg-[#1f47bb] text-white font-semibold rounded-xl text-sm transition flex items-center gap-2">
                    <i class="fas fa-save"></i> Enregistrer
                </button>
            </div>
        </form>
    </div>

    {{-- Changer mot de passe --}}
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
        <h3 class="text-sm font-bold text-gray-700 mb-4 flex items-center gap-2">
            <i class="fas fa-lock text-blue-400"></i> Changer le mot de passe
        </h3>
        <form method="POST" action="{{ route('profile.password') }}" class="space-y-4">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Mot de passe actuel</label>
                    <input type="password" name="current_password"
                        class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#2453d6]">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Nouveau mot de passe</label>
                    <input type="password" name="password"
                        class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#2453d6]">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Confirmation</label>
                    <input type="password" name="password_confirmation"
                        class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#2453d6]">
                </div>
            </div>
            <div class="flex justify-end">
                <button type="submit"
                    class="px-6 py-2.5 bg-[#2453d6] hover:bg-[#1f47bb] text-white font-semibold rounded-xl text-sm transition flex items-center gap-2">
                    <i class="fas fa-key"></i> Changer le mot de passe
                </button>
            </div>
        </form>
    </div>

    {{-- Langue --}}
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
        <h3 class="text-sm font-bold text-gray-700 mb-4 flex items-center gap-2">
            <i class="fas fa-globe text-blue-400"></i> Langue de l'interface
        </h3>
        <form method="POST" action="{{ route('profile.language') }}" class="flex items-center gap-4">
            @csrf
            @php $currentLang = session('locale', config('app.locale', 'fr')); @endphp
            <select name="locale" class="border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#2453d6]">
                <option value="fr" {{ $currentLang === 'fr' ? 'selected' : '' }}>&#127467;&#127479; Fran&ccedil;ais</option>
                <option value="en" {{ $currentLang === 'en' ? 'selected' : '' }}>&#127468;&#127463; English</option>
                <option value="ar" {{ $currentLang === 'ar' ? 'selected' : '' }}>&#127462;&#127466; Arabe</option>
            </select>
            <button type="submit"
                class="px-5 py-2.5 bg-[#2453d6] hover:bg-[#1f47bb] text-white font-semibold rounded-xl text-sm transition flex items-center gap-2">
                <i class="fas fa-check"></i> Appliquer
            </button>
        </form>
    </div>

    {{-- Infos lecture seule --}}
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
        <h3 class="text-sm font-bold text-gray-700 mb-4 flex items-center gap-2">
            <i class="fas fa-info-circle text-gray-400"></i> Informations du compte
        </h3>
        <dl class="grid grid-cols-2 gap-x-6 gap-y-3 text-sm">
            <dt class="text-gray-500 font-medium">R&ocirc;le</dt>
            <dd class="text-gray-800 font-semibold">{{ ucfirst($user->role ?? 'user') }}</dd>
            <dt class="text-gray-500 font-medium">Statut</dt>
            <dd>
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold
                    {{ ($user->status ?? 'active') === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                    <span class="w-1.5 h-1.5 rounded-full {{ ($user->status ?? 'active') === 'active' ? 'bg-green-500' : 'bg-gray-400' }}"></span>
                    {{ ucfirst($user->status ?? 'active') }}
                </span>
            </dd>
            <dt class="text-gray-500 font-medium">Membre depuis</dt>
            <dd class="text-gray-800">{{ $user->created_at?->format('d/m/Y') ?? '&mdash;' }}</dd>
        </dl>
    </div>

    {{-- Deconnexion --}}
    <div class="bg-white rounded-2xl border border-red-100 shadow-sm p-6 flex items-center justify-between">
        <div>
            <p class="text-sm font-semibold text-gray-700">Se d&eacute;connecter</p>
            <p class="text-xs text-gray-400 mt-0.5">Fermer votre session en cours</p>
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit"
                class="px-5 py-2 bg-red-500 hover:bg-red-600 text-white font-semibold rounded-xl text-sm transition flex items-center gap-2">
                <i class="fas fa-sign-out-alt"></i> D&eacute;connexion
            </button>
        </form>
    </div>

</div>
@endsection
