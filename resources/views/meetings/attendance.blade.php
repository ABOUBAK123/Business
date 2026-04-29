<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Émargement - {{ $meeting->title }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen py-8 px-4">
<div class="max-w-xl mx-auto bg-white rounded-2xl shadow p-6">
    <h1 class="text-xl font-bold text-gray-800">Émargement</h1>
    <p class="text-sm text-gray-500 mt-1">{{ $meeting->title }} · {{ $meeting->starts_at?->format('d/m/Y H:i') }}</p>

    @if(session('success'))
    <div class="mt-4 p-3 rounded-lg bg-green-50 text-green-700 text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="mt-4 p-3 rounded-lg bg-red-50 text-red-700 text-sm">{{ session('error') }}</div>
    @endif

    <form method="POST" class="mt-4 space-y-3">
        @csrf
        <div>
            <label class="block text-xs font-semibold text-gray-700 mb-1">Matricule ou email</label>
            <input name="identifier" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="email@organisation.com">
        </div>
        <div>
            <label class="block text-xs font-semibold text-gray-700 mb-1">Nom complet (si externe)</label>
            <input name="full_name" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="Nom et prénom">
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1">Email</label>
                <input type="email" name="email" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1">Téléphone</label>
                <input name="phone" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
        </div>
        <div>
            <label class="block text-xs font-semibold text-gray-700 mb-1">Fonction</label>
            <input name="job_title" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
        </div>
        <div>
            <label class="block text-xs font-semibold text-gray-700 mb-1">Organisation</label>
            <input name="organization" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
        </div>
        <button class="w-full px-3 py-2 rounded-lg bg-[#2453d6] text-white text-sm font-semibold hover:bg-[#1f47bb]">Signer ma présence</button>
    </form>
</div>
</body>
</html>
