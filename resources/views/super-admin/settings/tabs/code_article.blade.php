@php
    // Valeurs actives : tenant si sélectionné, sinon global
    $active = $selectedTenant && count($tenantConfigs)
        ? $tenantConfigs
        : [
            'article_code_prefix' => $settings['article_code_prefix'] ?? 'ART-',
            'article_code_length' => $settings['article_code_length'] ?? 6,
            'article_code_type'   => $settings['article_code_type']   ?? 'alphanumeric',
          ];
@endphp

<div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
    <div class="flex items-center gap-3 mb-5">
        <div class="w-9 h-9 bg-gray-100 rounded-lg flex items-center justify-center">
            <i class="fas fa-barcode text-gray-600"></i>
        </div>
        <div>
            <h3 class="text-sm font-semibold text-gray-800">Paramètres du code article</h3>
            <p class="text-xs text-gray-400">Format du code généré automatiquement à la création d'un article</p>
        </div>
    </div>

    {{-- ── Sélecteur boutique ─────────────────────────────────────────────── --}}
    <div class="mb-5 p-4 bg-blue-50 rounded-xl border border-blue-100">
        <label class="block text-xs font-semibold text-blue-700 mb-2">
            <i class="fas fa-store mr-1"></i> Appliquer à
        </label>
        <form method="GET" action="{{ route('super-admin.settings.index') }}" class="flex gap-2 items-center">
            <input type="hidden" name="tab" value="code_article">
            <select name="tenant_id"
                    class="flex-1 border border-blue-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white"
                    onchange="this.form.submit()">
                <option value="">— Paramètres globaux (toutes boutiques) —</option>
                @foreach($tenants as $tenant)
                    <option value="{{ $tenant->id }}" {{ $selectedTenant == $tenant->id ? 'selected' : '' }}>
                        {{ $tenant->shop_name }}
                    </option>
                @endforeach
            </select>
            <noscript>
                <button type="submit" class="bg-blue-600 text-white px-3 py-2 rounded-lg text-sm">Charger</button>
            </noscript>
        </form>

        @if($selectedTenant)
        <p class="text-xs text-blue-600 mt-2">
            <i class="fas fa-info-circle mr-1"></i>
            Les paramètres ci-dessous s'appliquent uniquement à
            <strong>{{ $tenants->firstWhere('id', $selectedTenant)?->shop_name }}</strong>.
            Les autres boutiques utilisent les paramètres globaux sauf si configurés individuellement.
        </p>
        @else
        <p class="text-xs text-blue-500 mt-2">
            <i class="fas fa-globe mr-1"></i>
            Paramètres globaux — appliqués à toutes les boutiques sans configuration spécifique.
        </p>
        @endif
    </div>

    {{-- ── Formulaire paramètres ──────────────────────────────────────────── --}}
    <form method="POST" action="{{ route('super-admin.settings.update', 'code_article') }}" class="space-y-5">
        @csrf
        @if($selectedTenant)
            <input type="hidden" name="tenant_id" value="{{ $selectedTenant }}">
        @endif

        {{-- Préfixe --}}
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Préfixe</label>
            <input type="text" name="article_code_prefix"
                   value="{{ $active['article_code_prefix'] }}"
                   maxlength="10"
                   class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                   placeholder="ART-"
                   oninput="updatePreview()">
            <p class="text-xs text-gray-400 mt-0.5">Texte fixe avant le code (max 10 car.). Laissez vide pour aucun préfixe.</p>
        </div>

        {{-- Longueur --}}
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">
                Nombre de caractères aléatoires :
                <span class="text-blue-600 font-bold" id="lengthDisplay">{{ $active['article_code_length'] }}</span>
            </label>
            <input type="range" name="article_code_length" id="codeLengthRange"
                   min="3" max="12" value="{{ $active['article_code_length'] }}"
                   class="w-full accent-blue-600"
                   oninput="document.getElementById('lengthDisplay').textContent=this.value; document.getElementById('codeLengthNumber').value=this.value; updatePreview()">
            <div class="flex justify-between text-xs text-gray-400 mt-0.5"><span>3</span><span>12</span></div>
            <input type="number" id="codeLengthNumber" min="3" max="12"
                   value="{{ $active['article_code_length'] }}"
                   class="mt-2 w-24 border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 text-center"
                   oninput="document.getElementById('codeLengthRange').value=this.value; document.getElementById('lengthDisplay').textContent=this.value; updatePreview()"
                   onblur="this.form.querySelector('[name=article_code_length]').value=this.value">
        </div>

        {{-- Type --}}
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-2">Type de caractères</label>
            <div class="space-y-2">
                @foreach([
                    'alphanumeric' => ['Alphanumérique (lettres + chiffres)', 'Ex : ART-A3FK72'],
                    'numeric'      => ['Numérique uniquement',                'Ex : ART-847291'],
                    'alpha'        => ['Lettres uniquement (majuscules)',      'Ex : ART-XKMPQZ'],
                ] as $val => [$lbl, $example])
                <label class="flex items-start gap-3 p-3 border rounded-lg cursor-pointer transition
                              {{ $active['article_code_type'] === $val ? 'border-blue-400 bg-blue-50' : 'border-gray-200 hover:bg-gray-50' }}"
                       onclick="highlightType(this)">
                    <input type="radio" name="article_code_type" value="{{ $val }}"
                           {{ $active['article_code_type'] === $val ? 'checked' : '' }}
                           class="mt-0.5 accent-blue-600" onchange="updatePreview()">
                    <div>
                        <p class="text-sm font-medium text-gray-700">{{ $lbl }}</p>
                        <p class="text-xs text-gray-400">{{ $example }}</p>
                    </div>
                </label>
                @endforeach
            </div>
        </div>

        {{-- Aperçu --}}
        <div class="bg-gray-50 rounded-xl p-4 border border-gray-200">
            <p class="text-xs text-gray-500 mb-2 font-medium uppercase tracking-wide">Aperçu du code généré</p>
            <div class="flex items-center gap-3">
                <code id="codePreview"
                      class="text-lg font-bold text-blue-700 bg-white border border-blue-200 px-4 py-2 rounded-lg tracking-widest">
                    {{ $active['article_code_prefix'] }}XXXXXX
                </code>
                <button type="button" onclick="updatePreview()"
                        class="text-xs text-blue-600 hover:text-blue-800 flex items-center gap-1">
                    <i class="fas fa-sync-alt"></i> Exemple
                </button>
            </div>
        </div>

        <div class="pt-3 border-t border-gray-100 flex items-center justify-between">
            @if($selectedTenant)
            <p class="text-xs text-amber-600">
                <i class="fas fa-store mr-1"></i>
                Appliqué à : <strong>{{ $tenants->firstWhere('id', $selectedTenant)?->shop_name }}</strong>
            </p>
            @else
            <p class="text-xs text-gray-400">Paramètres globaux</p>
            @endif
            <button type="submit"
                    class="bg-blue-600 text-white px-5 py-2 rounded-lg text-sm font-semibold hover:bg-blue-700 transition">
                <i class="fas fa-save mr-1.5"></i> Enregistrer
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
function randomCode(type, length) {
    const alpha = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    const num   = '0123456789';
    const chars = type === 'numeric' ? num : type === 'alpha' ? alpha : alpha + num;
    return Array.from({length}, () => chars[Math.floor(Math.random() * chars.length)]).join('');
}

function updatePreview() {
    const prefix = document.querySelector('[name=article_code_prefix]')?.value ?? 'ART-';
    const length = parseInt(document.getElementById('codeLengthRange')?.value ?? 6);
    const type   = document.querySelector('[name=article_code_type]:checked')?.value ?? 'alphanumeric';
    document.getElementById('codePreview').textContent = prefix + randomCode(type, length);
}

function highlightType(label) {
    label.closest('.space-y-2').querySelectorAll('label').forEach(l => {
        l.classList.remove('border-blue-400', 'bg-blue-50');
        l.classList.add('border-gray-200');
    });
    label.classList.add('border-blue-400', 'bg-blue-50');
    label.classList.remove('border-gray-200');
    updatePreview();
}

document.addEventListener('DOMContentLoaded', updatePreview);
</script>
@endpush
