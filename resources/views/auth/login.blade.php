<x-guest-layout>
    <h2 class="text-lg font-bold text-gray-800 mb-6 text-center">Connexion</h2>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        <div>
            <label for="email" class="block text-xs font-medium text-gray-600 mb-1">Adresse email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                   class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent
                          @error('email') border-red-400 @enderror">
            @error('email')
                <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password" class="block text-xs font-medium text-gray-600 mb-1">Mot de passe</label>
            <div class="relative">
                <input id="password" type="password" name="password" required autocomplete="current-password"
                       class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent pr-10
                              @error('password') border-red-400 @enderror">
                <button type="button" onclick="togglePwd()"
                        class="absolute right-3 top-2.5 text-gray-400 hover:text-gray-600">
                    <i id="eyeIcon" class="fas fa-eye text-sm"></i>
                </button>
            </div>
            @error('password')
                <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex items-center justify-between">
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="remember" class="rounded border-gray-300 text-blue-600 text-sm">
                <span class="text-xs text-gray-600">Se souvenir de moi</span>
            </label>
            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="text-xs text-blue-600 hover:text-blue-800">
                    Mot de passe oublié ?
                </a>
            @endif
        </div>

        <button type="submit"
                class="w-full bg-blue-600 text-white py-2.5 rounded-lg text-sm font-semibold hover:bg-blue-700 transition mt-2 flex items-center justify-center gap-2">
            <i class="fas fa-sign-in-alt"></i> Se connecter
        </button>
    </form>

    <p class="text-center text-xs text-gray-400 mt-6">
        Pas encore de boutique ?
        <a href="{{ route('register.plans') }}" class="text-blue-600 hover:underline font-medium">Créer un compte</a>
    </p>

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
</x-guest-layout>
