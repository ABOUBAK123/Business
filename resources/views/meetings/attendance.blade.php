<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Émargement - {{ $meeting->title }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-green-50 min-h-screen py-6 px-4">
<div class="max-w-lg mx-auto bg-green-50 rounded-2xl shadow-lg border-2 border-orange-400 p-6">

    {{-- En-tête centré : logo + entité sous tutelle --}}
    <div class="flex flex-col items-center text-center pb-4 border-b border-orange-200 mb-4 gap-2">
        @if(!empty($branding['logo_url']))
            <img src="{{ $branding['logo_url'] }}" alt="Logo administration"
                 class="h-20 w-20 object-contain rounded-xl bg-white border border-gray-200 p-1 shadow-sm">
        @endif
        @if(!empty($branding['tutelle_entity_name']))
            <div class="text-base font-bold text-gray-800 leading-tight">{{ $branding['tutelle_entity_name'] }}</div>
        @endif
        <div class="text-xs uppercase tracking-widest text-gray-400 font-medium mt-1">Formulaire d'émargement</div>
    </div>

    <h1 class="text-lg font-bold text-gray-800 text-center">{{ $meeting->title }}</h1>
    <p class="text-sm text-gray-500 text-center mt-1">{{ $meeting->starts_at?->format('d/m/Y H:i') }}</p>

    @if(session('success'))
    <div class="mt-4 p-3 rounded-lg bg-green-50 text-green-700 text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="mt-4 p-3 rounded-lg bg-red-50 text-red-700 text-sm">{{ session('error') }}</div>
    @endif

    <form method="POST" class="mt-4 space-y-4">
        @csrf
        <div>
            <label class="block text-xs font-semibold text-gray-700 mb-1">Matricule ou email</label>
            <input id="field-identifier" name="identifier" required
                   class="w-full border border-gray-300 rounded-lg px-3 py-3 text-base"
                   placeholder="email@organisation.com" autocomplete="off">
            <span id="autofill-badge" class="hidden text-xs text-green-700 font-medium mt-1 block">✓ Informations pré-remplies</span>
        </div>
        <div>
            <label class="block text-xs font-semibold text-gray-700 mb-1">Nom complet (si externe)</label>
            <input id="field-full_name" name="full_name" class="w-full border border-gray-300 rounded-lg px-3 py-3 text-base" placeholder="Nom et prénom">
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1">Email</label>
                <input id="field-email" type="email" name="email" class="w-full border border-gray-300 rounded-lg px-3 py-3 text-base">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1">Téléphone</label>
                <input id="field-phone" name="phone" class="w-full border border-gray-300 rounded-lg px-3 py-3 text-base">
            </div>
        </div>
        <div>
            <label class="block text-xs font-semibold text-gray-700 mb-1">Fonction</label>
            <input id="field-job_title" name="job_title" class="w-full border border-gray-300 rounded-lg px-3 py-3 text-base">
        </div>
        <div>
            <label class="block text-xs font-semibold text-gray-700 mb-1">Organisation</label>
            <input id="field-organization" name="organization" class="w-full border border-gray-300 rounded-lg px-3 py-3 text-base">
        </div>
        <button class="w-full px-3 py-4 rounded-xl bg-[#2453d6] text-white text-base font-semibold hover:bg-[#1f47bb] active:scale-95 transition-transform">Signer ma présence</button>
    </form>
</div>

<script>
(function () {
    const lookupUrl = '{{ route("meetings.qr.lookup", $meeting->qr_token) }}';
    const identifierInput = document.getElementById('field-identifier');
    const badge = document.getElementById('autofill-badge');
    const fields = ['full_name', 'email', 'phone', 'job_title', 'organization'];
    let debounceTimer = null;

    function fill(data) {
        fields.forEach(function (key) {
            const el = document.getElementById('field-' + key);
            if (el && data[key]) {
                el.value = data[key];
            }
        });
        badge.classList.remove('hidden');
    }

    function clear() {
        fields.forEach(function (key) {
            const el = document.getElementById('field-' + key);
            if (el) el.value = '';
        });
        badge.classList.add('hidden');
    }

    identifierInput.addEventListener('input', function () {
        clearTimeout(debounceTimer);
        badge.classList.add('hidden');
        const val = this.value.trim();
        if (val.length < 3) { return; }

        debounceTimer = setTimeout(function () {
            fetch(lookupUrl + '?identifier=' + encodeURIComponent(val))
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data) { fill(data); } else { clear(); }
                })
                .catch(function () {});
        }, 400);
    });
})();
</script>
</body>
</html>
