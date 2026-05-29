<a href="{{ route('commissioner.dashboard') }}"
   class="flex items-center gap-3 px-3 py-2 rounded-lg text-blue-100 hover:bg-white/10 {{ request()->routeIs('commissioner.dashboard') ? 'bg-white/20 font-semibold' : '' }}">
    <i class="fas fa-tachometer-alt w-4"></i> Tableau de bord
</a>
<a href="{{ route('commissioner.shops') }}"
   class="flex items-center gap-3 px-3 py-2 rounded-lg text-blue-100 hover:bg-white/10 {{ request()->routeIs('commissioner.shops*') ? 'bg-white/20 font-semibold' : '' }}">
    <i class="fas fa-store w-4"></i> Mes boutiques
</a>
<a href="{{ route('commissioner.commissions') }}"
   class="flex items-center gap-3 px-3 py-2 rounded-lg text-blue-100 hover:bg-white/10 {{ request()->routeIs('commissioner.commissions') ? 'bg-white/20 font-semibold' : '' }}">
    <i class="fas fa-hand-holding-usd w-4"></i> Commissions
</a>
