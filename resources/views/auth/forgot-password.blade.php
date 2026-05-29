<x-guest-layout>
    <h2 class="text-lg font-bold text-gray-800 mb-2 text-center">Mot de passe oublié</h2>
    <p class="text-xs text-gray-500 text-center mb-6">
        Entrez votre email pour recevoir un lien de réinitialisation.
    </p>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
        @csrf

        <div>
            <label for="email" class="block text-xs font-medium text-gray-600 mb-1">Adresse email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                   class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            @error('email')
                <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit"
                class="w-full bg-blue-600 text-white py-2.5 rounded-lg text-sm font-semibold hover:bg-blue-700 transition">
            Envoyer le lien
        </button>
    </form>

    <p class="text-center text-xs text-gray-400 mt-6">
        <a href="{{ route('login') }}" class="text-blue-600 hover:underline">← Retour à la connexion</a>
    </p>
</x-guest-layout>
