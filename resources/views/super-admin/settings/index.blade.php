@extends('layouts.app')

@section('title', 'Configuration')
@section('page-title', 'Configuration système')

@section('content')

{{-- ── Onglets ──────────────────────────────────────────────────────────── --}}
<div class="flex gap-1 mb-5 bg-white border border-gray-100 rounded-xl p-1.5 shadow-sm w-fit">
    @foreach([
        'sms'          => ['fas fa-sms',         'SMS'],
        'whatsapp'     => ['fab fa-whatsapp',     'WhatsApp'],
        'email'        => ['fas fa-envelope',     'Email'],
        'mobile_money' => ['fas fa-mobile-alt',   'Mobile Money'],
        'code_article' => ['fas fa-barcode',      'Code article'],
    ] as $key => [$icon, $label])
    <a href="{{ route('super-admin.settings.index', ['tab' => $key]) }}"
       class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition
              {{ $tab === $key
                  ? 'bg-blue-600 text-white shadow-sm'
                  : 'text-gray-600 hover:bg-gray-100' }}">
        <i class="{{ $icon }}"></i> {{ $label }}
    </a>
    @endforeach
</div>

{{-- ── Contenu de l'onglet actif ────────────────────────────────────────── --}}
<div class="max-w-2xl">
    @includeIf("super-admin.settings.tabs.{$tab}", [
        'settings'       => $settings,
        'tenants'        => $tenants        ?? collect(),
        'selectedTenant' => $selectedTenant ?? null,
        'tenantConfigs'  => $tenantConfigs  ?? [],
    ])
</div>

@endsection

@push('scripts')
<script>
function toggleSecret(btn, inputId) {
    const input = document.getElementById(inputId);
    const icon  = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}
</script>
@endpush
