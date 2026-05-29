<a href="{{ route('super-admin.dashboard') }}"
   class="flex items-center gap-3 px-3 py-2 rounded-lg text-blue-100 hover:bg-white/10 {{ request()->routeIs('super-admin.dashboard') ? 'bg-white/20 font-semibold' : '' }}">
    <i class="fas fa-tachometer-alt w-4"></i> Tableau de bord
</a>
<a href="{{ route('super-admin.tenants.index') }}"
   class="flex items-center gap-3 px-3 py-2 rounded-lg text-blue-100 hover:bg-white/10 {{ request()->routeIs('super-admin.tenants.*') ? 'bg-white/20 font-semibold' : '' }}">
    <i class="fas fa-store w-4"></i> Boutiques
</a>
<a href="{{ route('super-admin.plans.index') }}"
   class="flex items-center gap-3 px-3 py-2 rounded-lg text-blue-100 hover:bg-white/10 {{ request()->routeIs('super-admin.plans.*') ? 'bg-white/20 font-semibold' : '' }}">
    <i class="fas fa-tags w-4"></i> Plans d'abonnement
</a>
<a href="{{ route('super-admin.commissioners.index') }}"
   class="flex items-center gap-3 px-3 py-2 rounded-lg text-blue-100 hover:bg-white/10 {{ request()->routeIs('super-admin.commissioners.*') ? 'bg-white/20 font-semibold' : '' }}">
    <i class="fas fa-user-tie w-4"></i> Commissionnaires
</a>
<a href="{{ route('super-admin.settings.index') }}"
   class="flex items-center gap-3 px-3 py-2 rounded-lg text-blue-100 hover:bg-white/10 {{ request()->routeIs('super-admin.settings.*') ? 'bg-white/20 font-semibold' : '' }}">
    <i class="fas fa-cog w-4"></i> Configuration
</a>
