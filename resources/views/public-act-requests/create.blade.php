<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nouvelle demande d'acte</title>
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
        <section class="rounded-2xl border border-cyan-100 bg-gradient-to-r from-[#0ea5e9] via-[#2563eb] to-[#4f46e5] shadow-lg p-6 text-white">
            <p class="text-blue-100 text-xs mb-1">Étape 3 sur 3</p>
            <h1 class="text-2xl font-bold">Formulaire de demande</h1>
            <p class="text-sm text-blue-50 mt-2">{{ $requestedAct->document_name }} · {{ $administration->name }}</p>
        </section>

        <a href="{{ route('public.act-requests.by-admin', $administration->id) }}"
           class="inline-flex items-center text-xs font-semibold text-blue-700 hover:underline">
            ← Retour à la liste des actes
        </a>

        <div class="bg-white/95 backdrop-blur rounded-2xl border border-emerald-100 shadow-sm p-6">
            <div class="flex items-center justify-between gap-3 mb-3">
                <h2 class="text-base font-semibold text-gray-800">
                    3. Formulaire de demande – {{ $requestedAct->document_name }}
                </h2>
            </div>

            @if(session('success'))
                <div class="mt-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="mt-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    <ul class="list-disc list-inside space-y-0.5">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form class="mt-4 space-y-4" method="POST" enctype="multipart/form-data"
                  action="{{ route('public.act-requests.store', [$administration->id, $requestedAct->id]) }}">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Nom complet *</label>
                        <input type="text" name="applicant_full_name" value="{{ old('applicant_full_name') }}" required
                               placeholder="Votre nom et prénom"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-200">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Email *</label>
                        <input type="email" name="applicant_email" value="{{ old('applicant_email') }}" required
                               placeholder="votre.email@exemple.com"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-200">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Téléphone</label>
                        <input type="text" name="applicant_phone" value="{{ old('applicant_phone') }}"
                               placeholder="Votre téléphone"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-200">
                    </div>
                    @if(!$requestedAct->direction_code && $directions->isNotEmpty())
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Direction</label>
                        <select name="direction_code" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-200">
                            <option value="">Aucune</option>
                            @foreach($directions as $dir)
                                <option value="{{ $dir->code }}" {{ old('direction_code') === $dir->code ? 'selected' : '' }}>
                                    {{ $dir->code }} – {{ $dir->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @elseif($requestedAct->direction_code)
                        @php
                            $fixedDir = $directions->firstWhere('code', $requestedAct->direction_code);
                            $fixedDirLabel = $fixedDir ? ($fixedDir->code . ' – ' . $fixedDir->name) : $requestedAct->direction_code;
                        @endphp
                        <input type="hidden" name="direction_code" value="{{ $requestedAct->direction_code }}">
                    @endif
                </div>

                @php
                    $fields = is_array($requestedAct->applicant_fields) ? $requestedAct->applicant_fields : [];
                @endphp
                @if(!empty($fields))
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        @foreach($fields as $field)
                            @php
                                $label = trim((string)($field['label'] ?? ''));
                                $type  = (string)($field['inputType'] ?? 'text');
                                $key   = \Illuminate\Support\Str::of($label)->ascii()->lower()->replace("'", '_')->replaceMatches('/[^a-z0-9]+/', '_')->trim('_')->toString();
                            @endphp
                            @if($label !== '' && $key !== '')
                                <div class="{{ $type === 'textarea' ? 'md:col-span-2' : '' }}">
                                    <label class="block text-xs font-medium text-gray-700 mb-1">{{ $label }} *</label>
                                    @if($type === 'textarea')
                                        <textarea name="extra[{{ $key }}]" rows="3" required
                                                  class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-200 min-h-[88px]">{{ old('extra.' . $key) }}</textarea>
                                    @else
                                        @php $htmlType = match($type) { 'date'=>'date','number'=>'number','email'=>'email','phone'=>'tel', default=>'text' }; @endphp
                                        <input type="{{ $htmlType }}" name="extra[{{ $key }}]" value="{{ old('extra.' . $key) }}" required
                                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-200">
                                    @endif
                                </div>
                            @endif
                        @endforeach
                    </div>
                @endif

                {{-- Note --}}
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Note</label>
                    <textarea name="note" rows="3"
                              placeholder="Informations complémentaires"
                              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-200 min-h-[88px]">{{ old('note') }}</textarea>
                </div>

                @php
                    $requiredDocs = is_array($requestedAct->required_documents) ? $requestedAct->required_documents : [];
                @endphp
                @if(!empty($requiredDocs))
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-2">Documents à fournir (obligatoires)</label>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            @foreach($requiredDocs as $doc)
                                @php
                                    $docLabel = trim((string) $doc);
                                    $docKey = \Illuminate\Support\Str::of($docLabel)->ascii()->lower()->replace("'", '_')->replaceMatches('/[^a-z0-9]+/', '_')->trim('_')->toString();
                                @endphp
                                @if($docLabel !== '' && $docKey !== '')
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">{{ $docLabel }} *</label>
                                        <input type="file" name="required_files[{{ $docKey }}]" accept="application/pdf,.pdf" required
                                               class="block w-full text-xs border border-gray-300 rounded-lg px-3 py-2">
                                        <p class="text-[11px] text-red-600 mt-0.5">PDF uniquement</p>
                                        @error('required_files.' . $docKey)
                                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endif

                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Pièces jointes supplémentaires</label>
                    <input type="file" name="attachments[]" multiple accept="application/pdf,.pdf"
                           class="block w-full text-xs border border-gray-300 rounded-lg px-3 py-2">
                    <p class="text-[11px] text-gray-500 mt-0.5">PDF uniquement · 10 Mo max par fichier.</p>
                </div>

                <div class="pt-1">
                    <button type="submit"
                            class="rounded-lg bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 text-sm font-semibold transition">
                        Envoyer la demande
                    </button>
                </div>
            </form>
        </div>
    </main>
</body>
</html>
