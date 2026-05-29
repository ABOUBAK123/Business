<x-guest-layout>
    <div class="flex items-center gap-2 mb-1">
        <a href="{{ route('register.plans') }}" class="text-blue-300 hover:text-white text-xs">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h2 class="text-lg font-bold text-gray-800">Créer un compte commissionnaire</h2>
    </div>
    <p class="text-xs text-gray-500 text-center mb-6">
        Rejoignez le réseau — créez des boutiques et percevez 3% sur chaque abonnement.
    </p>

    <form method="POST" action="{{ route('register.commissioner.store') }}" class="space-y-4">
        @csrf

        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Nom complet <span class="text-red-500">*</span></label>
            <input type="text" name="name" value="{{ old('name') }}" required autofocus
                   class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('name') border-red-400 @enderror">
            @error('name')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Adresse email <span class="text-red-500">*</span></label>
            <input type="email" name="email" value="{{ old('email') }}" required
                   class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('email') border-red-400 @enderror">
            @error('email')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Téléphone</label>
            <input type="text" name="phone" value="{{ old('phone') }}"
                   placeholder="+229 97 00 00 00"
                   class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Mot de passe <span class="text-red-500">*</span></label>
            <div class="relative">
                <input type="password" id="password" name="password" required
                       class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 pr-10 @error('password') border-red-400 @enderror"
                       oninput="checkStrength(this.value)">
                <button type="button" onclick="togglePwd('password', this)"
                        class="absolute right-3 top-2.5 text-gray-400 hover:text-gray-600">
                    <i class="fas fa-eye text-sm"></i>
                </button>
            </div>
            <div class="mt-1.5 h-1 w-full bg-gray-100 rounded-full overflow-hidden">
                <div id="strengthBar" class="h-full rounded-full transition-all duration-300 w-0"></div>
            </div>
            @error('password')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Confirmer le mot de passe <span class="text-red-500">*</span></label>
            <div class="relative">
                <input type="password" id="password_confirmation" name="password_confirmation" required
                       class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 pr-10">
                <button type="button" onclick="togglePwd('password_confirmation', this)"
                        class="absolute right-3 top-2.5 text-gray-400 hover:text-gray-600">
                    <i class="fas fa-eye text-sm"></i>
                </button>
            </div>
        </div>

        <button type="submit"
                class="w-full bg-amber-500 text-white py-2.5 rounded-lg text-sm font-semibold hover:bg-amber-600 transition flex items-center justify-center gap-2 mt-2">
            <i class="fas fa-user-tie"></i> Créer mon compte
        </button>
    </form>

    <p class="text-center text-xs text-gray-400 mt-6">
        Déjà un compte ? <a href="{{ route('login') }}" class="text-blue-600 hover:underline">Se connecter</a>
    </p>

    <script>
    function togglePwd(id, btn) {
        const input = document.getElementById(id);
        const icon  = btn.querySelector('i');
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.replace('fa-eye', 'fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.replace('fa-eye-slash', 'fa-eye');
        }
    }
    function checkStrength(pwd) {
        const bar = document.getElementById('strengthBar');
        let score = 0;
        if (pwd.length >= 8)         score++;
        if (/[A-Z]/.test(pwd))       score++;
        if (/[0-9]/.test(pwd))       score++;
        if (/[^A-Za-z0-9]/.test(pwd)) score++;
        const colors = ['', 'bg-red-500', 'bg-yellow-500', 'bg-blue-500', 'bg-green-500'];
        const widths = ['0%', '25%', '50%', '75%', '100%'];
        bar.style.width = widths[score];
        bar.className = `h-full rounded-full transition-all duration-300 ${colors[score]}`;
    }
    </script>
</x-guest-layout>
