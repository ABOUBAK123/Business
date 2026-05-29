<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Tableau de bord') — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>[x-cloak]{display:none!important}</style>
    @stack('styles')
</head>
<body class="bg-gray-50 min-h-screen font-sans">
<div class="flex h-screen overflow-hidden">

    {{-- SIDEBAR --}}
    <aside class="w-64 flex-shrink-0 bg-blue-900 text-white flex flex-col">
        <div class="h-16 flex items-center px-5 border-b border-blue-800 gap-3">
            <div class="w-9 h-9 bg-yellow-400 rounded-lg flex items-center justify-center flex-shrink-0">
                <i class="fas fa-store text-blue-900"></i>
            </div>
            <div class="min-w-0">
                @if(isset($currentTenant))
                    <p class="font-bold text-sm truncate">{{ $currentTenant->shop_name }}</p>
                    <p class="text-blue-300 text-xs">{{ $currentTenant->plan?->name ?? '' }}</p>
                @elseif(auth()->user()->isCommissioner())
                    <p class="font-bold text-sm">{{ config('app.name') }}</p>
                    <p class="text-blue-300 text-xs">Commissionnaire</p>
                @else
                    <p class="font-bold text-sm">{{ config('app.name') }}</p>
                    <p class="text-blue-300 text-xs">Super Admin</p>
                @endif
            </div>
        </div>

        <nav class="flex-1 px-3 py-4 overflow-y-auto space-y-0.5 text-sm">
            @if(auth()->user()->is_super_admin)
                @include('layouts.partials.super-admin-nav')
            @elseif(auth()->user()->isCommissioner())
                @include('layouts.partials.commissioner-nav')
            @else
                @include('layouts.partials.tenant-nav')
            @endif
        </nav>

        <div class="border-t border-blue-800 px-4 py-3 flex items-center gap-3">
            <a href="{{ route('profile.edit') }}" class="flex items-center gap-3 flex-1 min-w-0 hover:opacity-80 transition">
                @if(auth()->user()->avatar)
                    <img src="{{ Storage::url(auth()->user()->avatar) }}" alt="avatar"
                         class="w-8 h-8 rounded-full object-cover border border-blue-600 flex-shrink-0">
                @else
                    <div class="w-8 h-8 bg-blue-700 rounded-full flex items-center justify-center font-bold text-sm flex-shrink-0">
                        {{ strtoupper(substr(auth()->user()->name,0,1)) }}
                    </div>
                @endif
                <div class="min-w-0">
                    <p class="text-sm font-medium truncate">{{ auth()->user()->name }}</p>
                    <p class="text-blue-300 text-xs truncate">{{ auth()->user()->getRoleNames()->first() ?? 'Admin' }}</p>
                </div>
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-blue-300 hover:text-white flex-shrink-0" title="Déconnexion">
                    <i class="fas fa-sign-out-alt"></i>
                </button>
            </form>
        </div>
    </aside>

    {{-- MAIN AREA --}}
    <div class="flex-1 flex flex-col overflow-hidden">
        <header class="h-16 bg-white border-b border-gray-200 flex items-center px-6 gap-4 flex-shrink-0">
            <h1 class="text-base font-semibold text-gray-800 flex-1">@yield('page-title', 'Tableau de bord')</h1>
            @if(isset($currentTenant))
                @if($currentTenant->status === 'trial')
                    <span class="bg-yellow-100 text-yellow-800 text-xs font-medium px-2.5 py-1 rounded-full">
                        <i class="fas fa-clock mr-1"></i>Essai — {{ $currentTenant->trial_ends_at?->diffInDays(now()) }}j restants
                    </span>
                @elseif($currentTenant->status === 'grace')
                    <span class="bg-red-100 text-red-800 text-xs font-medium px-2.5 py-1 rounded-full">
                        <i class="fas fa-exclamation-triangle mr-1"></i>Période de grâce
                    </span>
                @endif
            @endif
            <span class="text-xs text-gray-400">{{ now()->format('d/m/Y') }}</span>
        </header>

        <main class="flex-1 overflow-y-auto p-6">
            @if(session('success'))
                <div class="mb-4 bg-green-50 border border-green-200 text-green-700 rounded-lg px-4 py-3 flex items-center gap-2 text-sm">
                    <i class="fas fa-check-circle"></i> {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="mb-4 bg-red-50 border border-red-200 text-red-700 rounded-lg px-4 py-3 flex items-center gap-2 text-sm">
                    <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
                </div>
            @endif
            @if($errors->any())
                <div class="mb-4 bg-red-50 border border-red-200 text-red-700 rounded-lg px-4 py-3 text-sm">
                    <ul class="list-disc list-inside space-y-1">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                </div>
            @endif
            {{-- Alertes stock bas --}}
            @if(!empty($lowStockAlerts) && $lowStockAlerts->isNotEmpty())
            <div id="stockAlertBanner" class="mb-4 bg-amber-50 border border-amber-300 rounded-xl overflow-hidden">
                <div class="flex items-center justify-between px-4 py-3 cursor-pointer select-none"
                     onclick="toggleStockAlert()">
                    <div class="flex items-center gap-2 text-amber-800">
                        <i class="fas fa-exclamation-triangle text-amber-500"></i>
                        <span class="text-sm font-semibold">
                            {{ $lowStockAlerts->count() }} article{{ $lowStockAlerts->count() > 1 ? 's' : '' }}
                            {{ $lowStockAlerts->count() > 1 ? 'ont atteint' : 'a atteint' }}
                            le seuil d'alerte de stock
                        </span>
                    </div>
                    <div class="flex items-center gap-3">
                        <a href="{{ route('stock.index', ['status' => 'low']) }}"
                           onclick="event.stopPropagation()"
                           class="text-xs bg-amber-600 text-white px-3 py-1 rounded-lg hover:bg-amber-700">
                            Approvisionner
                        </a>
                        <i id="stockAlertIcon" class="fas fa-chevron-down text-amber-500 text-xs transition-transform"></i>
                    </div>
                </div>
                <div id="stockAlertList" class="hidden border-t border-amber-200 px-4 py-2">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2 py-1">
                        @foreach($lowStockAlerts->take(9) as $alert)
                        <div class="flex items-center justify-between bg-white rounded-lg px-3 py-2 border border-amber-100">
                            <div class="min-w-0">
                                <p class="text-xs font-semibold text-gray-800 truncate">{{ $alert->designation }}</p>
                                <p class="text-xs text-gray-400 font-mono">{{ $alert->reference }}</p>
                            </div>
                            <div class="text-right flex-shrink-0 ml-2">
                                <span class="text-sm font-bold {{ ($alert->total_stock ?? 0) == 0 ? 'text-red-600' : 'text-amber-600' }}">
                                    {{ $alert->total_stock ?? 0 }}
                                </span>
                                <span class="text-xs text-gray-400"> / {{ $alert->stock_min }} min</span>
                            </div>
                        </div>
                        @endforeach
                        @if($lowStockAlerts->count() > 9)
                        <div class="flex items-center justify-center bg-amber-50 rounded-lg px-3 py-2 border border-amber-100">
                            <a href="{{ route('stock.index', ['status' => 'low']) }}"
                               class="text-xs text-amber-700 font-medium hover:underline">
                                + {{ $lowStockAlerts->count() - 9 }} autres articles →
                            </a>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            @endif

            @yield('content')
        </main>
    </div>
</div>
@stack('scripts')
<script>
function toggleStockAlert() {
    const list = document.getElementById('stockAlertList');
    const icon = document.getElementById('stockAlertIcon');
    const hidden = list.classList.contains('hidden');
    list.classList.toggle('hidden', !hidden);
    icon.classList.toggle('rotate-180', hidden);
}
</script>
</body>
</html>
