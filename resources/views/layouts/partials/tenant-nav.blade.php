@php $r = request()->routeIs(...); @endphp

<a href="{{ route('dashboard') }}"
   class="flex items-center gap-3 px-3 py-2 rounded-lg text-blue-100 hover:bg-white/10 {{ request()->routeIs('dashboard') ? 'bg-white/20 font-semibold' : '' }}">
    <i class="fas fa-tachometer-alt w-4"></i> Tableau de bord
</a>

<div class="pt-3 pb-1 px-3 text-blue-400 text-xs uppercase tracking-wider">Ventes</div>

<a href="{{ route('sales.create') }}"
   class="flex items-center gap-3 px-3 py-2 rounded-lg text-blue-100 hover:bg-white/10 {{ request()->routeIs('sales.create') ? 'bg-white/20 font-semibold' : '' }}">
    <i class="fas fa-cash-register w-4"></i> Nouvelle vente
</a>
<a href="{{ route('sales.index') }}"
   class="flex items-center gap-3 px-3 py-2 rounded-lg text-blue-100 hover:bg-white/10 {{ request()->routeIs('sales.index','sales.show') ? 'bg-white/20 font-semibold' : '' }}">
    <i class="fas fa-receipt w-4"></i> Historique ventes
</a>

<div class="pt-3 pb-1 px-3 text-blue-400 text-xs uppercase tracking-wider">Catalogue</div>

<a href="{{ route('articles.index') }}"
   class="flex items-center gap-3 px-3 py-2 rounded-lg text-blue-100 hover:bg-white/10 {{ request()->routeIs('articles.*') ? 'bg-white/20 font-semibold' : '' }}">
    <i class="fas fa-boxes w-4"></i> Articles
</a>
<a href="{{ route('profile.edit', ['tab' => 'categories']) }}"
   class="flex items-center gap-3 px-3 py-2 rounded-lg text-blue-100 hover:bg-white/10 {{ request()->routeIs('profile.edit') && request()->tab === 'categories' ? 'bg-white/20 font-semibold' : '' }}">
    <i class="fas fa-tags w-4"></i> Catégories
</a>
<a href="{{ route('profile.edit', ['tab' => 'suppliers']) }}"
   class="flex items-center gap-3 px-3 py-2 rounded-lg text-blue-100 hover:bg-white/10 {{ request()->routeIs('profile.edit') && request()->tab === 'suppliers' ? 'bg-white/20 font-semibold' : '' }}">
    <i class="fas fa-truck w-4"></i> Fournisseurs
</a>
<a href="{{ route('stock.index') }}"
   class="flex items-center gap-3 px-3 py-2 rounded-lg text-blue-100 hover:bg-white/10 {{ request()->routeIs('stock.*') ? 'bg-white/20 font-semibold' : '' }}">
    <i class="fas fa-dolly w-4"></i>
    <span class="flex-1">Gestion des stocks</span>
    @if(!empty($lowStockAlerts) && $lowStockAlerts->isNotEmpty())
        <span class="bg-red-500 text-white text-xs font-bold rounded-full min-w-[18px] h-[18px] flex items-center justify-center px-1">
            {{ $lowStockAlerts->count() }}
        </span>
    @endif
</a>

<div class="pt-3 pb-1 px-3 text-blue-400 text-xs uppercase tracking-wider">Gestion</div>

<a href="{{ route('customers.index') }}"
   class="flex items-center gap-3 px-3 py-2 rounded-lg text-blue-100 hover:bg-white/10 {{ request()->routeIs('customers.*') ? 'bg-white/20 font-semibold' : '' }}">
    <i class="fas fa-users w-4"></i> Clients
</a>
<a href="{{ route('branches.index') }}"
   class="flex items-center gap-3 px-3 py-2 rounded-lg text-blue-100 hover:bg-white/10 {{ request()->routeIs('branches.*') ? 'bg-white/20 font-semibold' : '' }}">
    <i class="fas fa-building w-4"></i> Succursales
</a>
<a href="{{ route('users.index') }}"
   class="flex items-center gap-3 px-3 py-2 rounded-lg text-blue-100 hover:bg-white/10 {{ request()->routeIs('users.*') ? 'bg-white/20 font-semibold' : '' }}">
    <i class="fas fa-user-cog w-4"></i> Utilisateurs
</a>

<div class="pt-3 pb-1 px-3 text-blue-400 text-xs uppercase tracking-wider">Rapports</div>

<a href="{{ route('reports.sales') }}"
   class="flex items-center gap-3 px-3 py-2 rounded-lg text-blue-100 hover:bg-white/10 {{ request()->routeIs('reports.sales') ? 'bg-white/20 font-semibold' : '' }}">
    <i class="fas fa-chart-bar w-4"></i> Rapport ventes
</a>
<a href="{{ route('reports.stock') }}"
   class="flex items-center gap-3 px-3 py-2 rounded-lg text-blue-100 hover:bg-white/10 {{ request()->routeIs('reports.stock') ? 'bg-white/20 font-semibold' : '' }}">
    <i class="fas fa-warehouse w-4"></i> État des stocks
</a>
<a href="{{ route('reports.top-articles') }}"
   class="flex items-center gap-3 px-3 py-2 rounded-lg text-blue-100 hover:bg-white/10 {{ request()->routeIs('reports.top-articles') ? 'bg-white/20 font-semibold' : '' }}">
    <i class="fas fa-trophy w-4"></i> Top articles
</a>
<a href="{{ route('reports.financial') }}"
   class="flex items-center gap-3 px-3 py-2 rounded-lg text-blue-100 hover:bg-white/10 {{ request()->routeIs('reports.financial') ? 'bg-white/20 font-semibold' : '' }}">
    <i class="fas fa-chart-line w-4"></i> Rapport financier
</a>
