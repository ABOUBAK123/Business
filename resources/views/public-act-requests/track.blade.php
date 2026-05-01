<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Suivi de demande d'acte</title>
    @php
        $useVite = file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot'));
    @endphp
    @if($useVite)
        @vite(['resources/css/app.css'])
    @else
        <script src="{{ asset('vendor/tailwind/tailwind.js') }}"></script>
    @endif
</head>
<body class="min-h-screen bg-gradient-to-br from-cyan-50 via-sky-50 to-indigo-100 text-slate-800">
    @php
        $colors = [
            'pending' => 'bg-yellow-100 text-yellow-700 border-yellow-200',
            'in_progress' => 'bg-blue-100 text-blue-700 border-blue-200',
            'sent' => 'bg-indigo-100 text-indigo-700 border-indigo-200',
            'recu' => 'bg-purple-100 text-purple-700 border-purple-200',
            'treated' => 'bg-green-100 text-green-700 border-green-200',
            'rejected' => 'bg-red-100 text-red-700 border-red-200',
        ];

        $labels = [
            'pending' => 'En attente',
            'in_progress' => 'En cours de traitement',
            'sent' => 'Envoyee a l\'administration destinataire',
            'recu' => 'Recu par l\'administration destinataire',
            'treated' => 'Terminee',
            'rejected' => 'Refusee',
        ];

        $statusClass = $colors[$submission->status] ?? 'bg-gray-100 text-gray-700 border-gray-200';
        $statusLabel = $labels[$submission->status] ?? ucfirst((string) $submission->status);
    @endphp

    <main class="max-w-3xl mx-auto px-4 py-8 md:px-6">
        <section class="rounded-2xl border border-cyan-100 bg-gradient-to-r from-[#0ea5e9] via-[#2563eb] to-[#4f46e5] shadow-lg p-6 text-white">
            <p class="text-blue-100 text-xs mb-1">Portail public</p>
            <h1 class="text-2xl font-bold">Suivi de votre demande d'acte</h1>
            <p class="text-sm text-blue-50 mt-2">Numero de traitement: {{ $submission->tracking_number ?: '—' }}</p>
        </section>

        <div class="mt-5 bg-white/95 backdrop-blur rounded-2xl border border-gray-200 shadow-sm p-6 space-y-4">
            <div class="flex items-center justify-between flex-wrap gap-2">
                <p class="text-sm text-gray-500">Statut actuel</p>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold border {{ $statusClass }}">
                    {{ $statusLabel }}
                </span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                <div class="rounded-xl border border-gray-100 bg-gray-50 px-4 py-3">
                    <p class="text-xs text-gray-500">Demandeur</p>
                    <p class="font-semibold text-gray-800">{{ $submission->applicant_full_name }}</p>
                </div>
                <div class="rounded-xl border border-gray-100 bg-gray-50 px-4 py-3">
                    <p class="text-xs text-gray-500">Acte demande</p>
                    <p class="font-semibold text-gray-800">{{ $submission->requested_document_name }}</p>
                </div>
                <div class="rounded-xl border border-gray-100 bg-gray-50 px-4 py-3">
                    <p class="text-xs text-gray-500">Administration</p>
                    <p class="font-semibold text-gray-800">{{ $submission->administration?->name ?? '—' }}</p>
                </div>
                <div class="rounded-xl border border-gray-100 bg-gray-50 px-4 py-3">
                    <p class="text-xs text-gray-500">Date de depot</p>
                    <p class="font-semibold text-gray-800">{{ $submission->created_at?->format('d/m/Y H:i') }}</p>
                </div>
            </div>

            <div class="pt-2">
                <a href="{{ route('public.act-requests.index') }}" class="inline-flex items-center text-xs font-semibold text-blue-700 hover:underline">
                    ← Faire une autre demande
                </a>
            </div>
        </div>
    </main>
</body>
</html>
