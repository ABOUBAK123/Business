<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ in_array(app()->getLocale(), ['ar']) ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('messages.welcome') }}</title>
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
    <main class="max-w-5xl mx-auto px-4 py-8 md:px-6 space-y-5">

        {{-- Hero --}}
        <section class="rounded-2xl border border-cyan-100 bg-gradient-to-r from-[#0ea5e9] via-[#2563eb] to-[#4f46e5] shadow-lg p-6 text-white">
            <h1 class="text-2xl font-bold">Application Demande d'actes</h1>
            <p class="text-sm text-blue-50 mt-2">
                Parcours en 3 pages : administrations, actes disponibles, puis formulaire de demande.
            </p>
        </section>

        <section class="bg-white/95 backdrop-blur rounded-2xl border border-blue-100 shadow-sm p-6">
            <h2 class="text-base font-semibold text-gray-800 mb-3">Rechercher ma demande</h2>
            <p class="text-sm text-gray-500 mb-3">Saisissez votre numero de traitement pour suivre l'etat de votre demande.</p>

            @if(session('tracking_error'))
                <div class="mb-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    {{ session('tracking_error') }}
                </div>
            @endif

            <form method="POST" action="{{ route('public.act-requests.search') }}" class="flex flex-col sm:flex-row gap-2">
                @csrf
                <input
                    type="text"
                    name="tracking_number"
                    value="{{ old('tracking_number') }}"
                    placeholder="Ex: DACT-202604-123456"
                    class="flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-200"
                    required
                >
                <button type="submit" class="rounded-lg bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 text-sm font-semibold transition">
                    Suivre ma demande
                </button>
            </form>
        </section>

        @if(!$selectedAdministration)
        {{-- Ecran 1 : Administrations --}}
        <section class="bg-white/95 backdrop-blur rounded-2xl border border-cyan-100 shadow-sm p-6">
            <h2 class="text-base font-semibold text-gray-800 mb-3">1. Administrations émettrices</h2>

            <div class="mb-3">
                <input
                    id="admin-search"
                    type="text"
                    placeholder="Rechercher une administration (nom, code)..."
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-200"
                >
            </div>

            @if($administrations->isEmpty())
                <p class="text-sm text-gray-500">Aucune administration émettrice n'est disponible pour les demandes d'actes.</p>
            @else
                <div id="admin-grid" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                    @foreach($administrations as $admin)
                        @php
                            $logoRaw = (string) ($admin->logo ?? '');
                            $origin = rtrim(request()->getSchemeAndHttpHost(), '/');
                            $basePath = rtrim(request()->getBaseUrl(), '/');
                            $appBase = $origin . $basePath;
                            if ($logoRaw !== '' && \Illuminate\Support\Str::startsWith($logoRaw, ['http://', 'https://'])) {
                                $logoUrl = $logoRaw;
                            } elseif ($logoRaw !== '' && \Illuminate\Support\Str::startsWith($logoRaw, ['/images/', '/storage/'])) {
                                $logoUrl = $appBase . $logoRaw;
                            } elseif ($logoRaw !== '' && \Illuminate\Support\Str::startsWith($logoRaw, ['images/', 'storage/'])) {
                                $logoUrl = $appBase . '/' . ltrim($logoRaw, '/');
                            } elseif ($logoRaw !== '' && \Illuminate\Support\Str::startsWith($logoRaw, ['logos/'])) {
                                $logoUrl = $appBase . '/images/' . ltrim($logoRaw, '/');
                            } elseif ($logoRaw !== '') {
                                $logoUrl = $appBase . '/images/logos/' . ltrim($logoRaw, '/');
                            } else {
                                $logoUrl = null;
                            }
                            $cardSearch = mb_strtolower(trim(($admin->name ?? '') . ' ' . ($admin->code ?? '')));
                            $isSelected = $selectedAdministration && $selectedAdministration->id === $admin->id;
                        @endphp

                        <a
                            href="{{ route('public.act-requests.by-admin', $admin->id) }}"
                            class="admin-card aspect-square rounded-xl border p-3 flex flex-col items-center justify-center text-center transition hover:scale-[1.02] hover:shadow-md
                                {{ $isSelected ? 'border-blue-400 bg-blue-50/70' : 'border-orange-300 bg-emerald-50 hover:bg-emerald-100 hover:border-orange-400' }}"
                            data-search="{{ $cardSearch }}"
                        >
                            <div class="h-12 w-12 rounded-lg border border-orange-200 bg-white overflow-hidden flex items-center justify-center shrink-0 mb-2">
                                @if($logoUrl)
                                    <img src="{{ $logoUrl }}" alt="Logo {{ $admin->name }}" class="h-full w-full object-contain"
                                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                    <span style="display:none" class="h-full w-full items-center justify-center text-[10px] font-semibold text-orange-600">
                                        {{ mb_strtoupper(mb_substr($admin->code ?? $admin->name ?? '?', 0, 3)) }}
                                    </span>
                                @else
                                    <span class="text-[10px] font-semibold text-orange-600">
                                        {{ mb_strtoupper(mb_substr($admin->code ?? $admin->name ?? '?', 0, 3)) }}
                                    </span>
                                @endif
                            </div>
                            <p class="text-xs font-semibold text-gray-800 line-clamp-3 leading-tight">{{ $admin->name }}</p>
                            <p class="text-[11px] text-orange-700 mt-1">{{ $admin->code ?: '---' }}</p>
                        </a>
                    @endforeach
                </div>
            @endif
        </section>
        @else
        {{-- Ecran 2 : Actes disponibles --}}
            <section class="bg-white/95 backdrop-blur rounded-2xl border border-indigo-100 shadow-sm p-6">
                <div class="flex items-center justify-between gap-3 mb-3">
                    <h2 class="text-base font-semibold text-gray-800">
                        2. Actes fournis par {{ $selectedAdministration->name }}
                    </h2>
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-gray-400">{{ $acts->count() }} acte(s)</span>
                        <a href="{{ route('public.act-requests.index') }}"
                           class="text-xs rounded-md border border-green-200 bg-green-100 text-green-800 px-3 py-1.5 hover:bg-green-200 transition">
                            Retour administrations
                        </a>
                    </div>
                </div>

                <div class="mb-3">
                    <input
                        id="act-search"
                        type="text"
                        placeholder="Rechercher un acte (nom, direction, code, pièce exigée)..."
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                    >
                </div>

                @if($acts->isEmpty())
                    <p class="text-sm text-gray-500">Aucun acte disponible pour cette administration.</p>
                @else
                    <div id="acts-grid" class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        @foreach($acts as $act)
                            @php
                                $requiredDocs = is_array($act->required_documents) ? $act->required_documents : [];
                                $previewDocs  = array_slice($requiredDocs, 0, 2);
                                $remaining    = count($requiredDocs) - count($previewDocs);
                                $code         = trim((string)($act->direction_code ?? ''));
                                $dirName      = $code !== '' ? (string)(($subEntityByCode[$code] ?? null) ?: '') : '';
                                $dirLabel     = $code !== '' ? ($dirName !== '' ? "$dirName ($code)" : $code) : '';
                                $actSearch    = mb_strtolower(
                                    ($act->document_name ?? '') . ' ' .
                                    $dirLabel . ' ' . $code . ' ' .
                                    implode(' ', $requiredDocs)
                                );
                            @endphp
                            <div class="act-item rounded-lg border border-indigo-100 bg-gradient-to-r from-indigo-50 to-sky-50 p-3 flex flex-col h-full"
                                 data-search="{{ $actSearch }}">

                                <p class="text-sm font-semibold text-gray-800 line-clamp-2">{{ $act->document_name }}</p>

                                @if($dirLabel !== '')
                                    <p class="text-xs text-indigo-700 mt-1 line-clamp-2">
                                        Direction : {{ $dirLabel }}
                                    </p>
                                @endif

                                <div class="mt-2 flex-1">
                                    <p class="text-xs font-semibold text-gray-700">Pièces exigées :</p>
                                    <ul class="mt-1 list-disc pl-4 text-xs text-gray-600 space-y-0.5">
                                        @forelse($previewDocs as $doc)
                                            <li class="line-clamp-1">{{ $doc }}</li>
                                        @empty
                                            <li>Aucune pièce configurée.</li>
                                        @endforelse
                                    </ul>
                                    @if($remaining > 0)
                                        <p class="text-[11px] text-indigo-700 mt-1">+ {{ $remaining }} autre(s) pièce(s)</p>
                                    @endif
                                </div>

                                <div class="mt-3">
                                    <a href="{{ route('public.act-requests.create', [$selectedAdministration->id, $act->id]) }}"
                                       class="text-xs rounded-md bg-orange-500 hover:bg-orange-600 text-white px-3 py-2 w-full block text-center transition">
                                        Continuer vers le formulaire
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>
        @endif

    </main>

    <script>
        // Recherche administrations
        const adminSearch = document.getElementById('admin-search');
        const adminCards  = Array.from(document.querySelectorAll('.admin-card'));
        adminSearch?.addEventListener('input', function () {
            const q = (this.value || '').toLowerCase().trim();
            adminCards.forEach(card => {
                card.style.display = !q || (card.dataset.search || '').includes(q) ? '' : 'none';
            });
        });

        // Recherche actes
        const actSearchInput = document.getElementById('act-search');
        const actItems = Array.from(document.querySelectorAll('.act-item'));
        actSearchInput?.addEventListener('input', function () {
            const q = (this.value || '').toLowerCase().trim();
            actItems.forEach(item => {
                item.style.display = !q || (item.dataset.search || '').includes(q) ? '' : 'none';
            });
        });
    </script>
</body>
</html>
