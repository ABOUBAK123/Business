<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Émargement - {{ $meeting->title }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body.qr-attendance-body {
            margin: 0;
            background: #f0fdf4;
            min-height: 100vh;
            padding: 24px 16px;
            font-family: Arial, Helvetica, sans-serif;
            color: #1f2937;
        }
        .qr-card {
            max-width: 42rem;
            margin: 0 auto;
            background: #f0fdf4;
            border: 2px solid #fb923c;
            border-radius: 24px;
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.12);
            padding: 24px;
        }
        .qr-header {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            gap: 8px;
            padding-bottom: 16px;
            margin-bottom: 16px;
            border-bottom: 1px solid #fed7aa;
        }
        .qr-logo {
            width: 80px;
            height: 80px;
            object-fit: contain;
            border-radius: 14px;
            background: #fff;
            border: 1px solid #e5e7eb;
            padding: 4px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }
        .qr-title {
            margin: 0;
            text-align: center;
            font-size: 1.25rem;
            line-height: 1.4;
            font-weight: 700;
        }
        .qr-subtitle {
            margin: 4px 0 0;
            text-align: center;
            color: #6b7280;
            font-size: 0.95rem;
        }
        .qr-alert {
            margin-top: 16px;
            padding: 12px 14px;
            border-radius: 12px;
            font-size: 0.95rem;
        }
        .qr-alert-success {
            background: #dcfce7;
            color: #166534;
        }
        .qr-alert-error {
            background: #fef2f2;
            color: #b91c1c;
        }
        .qr-form {
            margin-top: 16px;
            display: grid;
            gap: 16px;
        }
        .qr-field {
            display: grid;
            gap: 6px;
        }
        .qr-field label {
            font-size: 0.8rem;
            font-weight: 700;
            color: #374151;
        }
        .qr-field input {
            width: 100%;
            box-sizing: border-box;
            border: 1px solid #d1d5db;
            border-radius: 12px;
            padding: 14px 14px;
            font-size: 16px;
            background: #fff;
            color: #111827;
        }
        .qr-field input:focus {
            outline: none;
            border-color: #2453d6;
            box-shadow: 0 0 0 3px rgba(36, 83, 214, 0.15);
        }
        .qr-grid-2 {
            display: grid;
            grid-template-columns: 1fr;
            gap: 12px;
        }
        .qr-submit {
            width: 100%;
            border: 0;
            border-radius: 16px;
            padding: 16px;
            background: #2453d6;
            color: #fff;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: transform .12s ease, background-color .12s ease;
        }
        .qr-submit:hover {
            background: #1f47bb;
        }
        .qr-submit:active {
            transform: scale(0.98);
        }
        .qr-badge {
            display: block;
            margin-top: 6px;
            font-size: 0.75rem;
            font-weight: 600;
            color: #166534;
        }
        .qr-hidden {
            display: none;
        }
        @media (min-width: 640px) {
            .qr-grid-2 {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
        @media (max-width: 639px) {
            body.qr-attendance-body {
                padding: 12px;
            }
            .qr-card {
                padding: 16px;
                border-radius: 18px;
            }
            .qr-logo {
                width: 68px;
                height: 68px;
            }
            .qr-title {
                font-size: 1.05rem;
            }
            .qr-subtitle {
                font-size: 0.88rem;
            }
        }
    </style>
</head>
<body class="bg-green-50 min-h-screen py-6 px-4 qr-attendance-body">
<div class="max-w-lg mx-auto bg-green-50 rounded-2xl shadow-lg border-2 border-orange-400 p-6 qr-card">

    {{-- En-tête centré : logo + entité sous tutelle --}}
    <div class="flex flex-col items-center text-center pb-4 border-b border-orange-200 mb-4 gap-2 qr-header">
        @if(!empty($branding['logo_url']))
            <img src="{{ $branding['logo_url'] }}" alt="Logo administration"
                 class="h-20 w-20 object-contain rounded-xl bg-white border border-gray-200 p-1 shadow-sm qr-logo">
        @endif
        @if(!empty($branding['tutelle_entity_name']))
            <div class="text-base font-bold text-gray-800 leading-tight">{{ $branding['tutelle_entity_name'] }}</div>
        @endif
        <div class="text-xs uppercase tracking-widest text-gray-400 font-medium mt-1">Formulaire d'émargement</div>
    </div>

    <h1 class="text-lg font-bold text-gray-800 text-center qr-title">{{ $meeting->title }}</h1>
    <p class="text-sm text-gray-500 text-center mt-1 qr-subtitle">{{ $meeting->starts_at?->format('d/m/Y H:i') }}</p>

    @if(session('success'))
    <div class="mt-4 p-3 rounded-lg bg-green-50 text-green-700 text-sm qr-alert qr-alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="mt-4 p-3 rounded-lg bg-red-50 text-red-700 text-sm qr-alert qr-alert-error">{{ session('error') }}</div>
    @endif

    <form method="POST" class="mt-4 space-y-4 qr-form">
        @csrf
        <div class="qr-field">
            <label class="block text-xs font-semibold text-gray-700 mb-1">Matricule ou email</label>
            <input id="field-identifier" name="identifier" required
                   class="w-full border border-gray-300 rounded-lg px-3 py-3 text-base"
                   placeholder="email@organisation.com" autocomplete="off">
            <span id="autofill-badge" class="hidden text-xs text-green-700 font-medium mt-1 block qr-badge qr-hidden">✓ Informations pré-remplies</span>
        </div>
        <div class="qr-field">
            <label class="block text-xs font-semibold text-gray-700 mb-1">Nom complet (si externe)</label>
            <input id="field-full_name" name="full_name" class="w-full border border-gray-300 rounded-lg px-3 py-3 text-base" placeholder="Nom et prénom">
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 qr-grid-2">
            <div class="qr-field">
                <label class="block text-xs font-semibold text-gray-700 mb-1">Email</label>
                <input id="field-email" type="email" name="email" class="w-full border border-gray-300 rounded-lg px-3 py-3 text-base">
            </div>
            <div class="qr-field">
                <label class="block text-xs font-semibold text-gray-700 mb-1">Téléphone</label>
                <input id="field-phone" name="phone" class="w-full border border-gray-300 rounded-lg px-3 py-3 text-base">
            </div>
        </div>
        <div class="qr-field">
            <label class="block text-xs font-semibold text-gray-700 mb-1">Fonction</label>
            <input id="field-job_title" name="job_title" class="w-full border border-gray-300 rounded-lg px-3 py-3 text-base">
        </div>
        <div class="qr-field">
            <label class="block text-xs font-semibold text-gray-700 mb-1">Organisation</label>
            <input id="field-organization" name="organization" class="w-full border border-gray-300 rounded-lg px-3 py-3 text-base">
        </div>
        <button class="w-full px-3 py-4 rounded-xl bg-[#2453d6] text-white text-base font-semibold hover:bg-[#1f47bb] active:scale-95 transition-transform qr-submit">Signer ma présence</button>
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
        badge.classList.remove('hidden', 'qr-hidden');
    }

    function clear() {
        fields.forEach(function (key) {
            const el = document.getElementById('field-' + key);
            if (el) el.value = '';
        });
        badge.classList.add('hidden', 'qr-hidden');
    }

    identifierInput.addEventListener('input', function () {
        clearTimeout(debounceTimer);
        badge.classList.add('hidden', 'qr-hidden');
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
