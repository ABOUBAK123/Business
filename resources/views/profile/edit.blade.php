@extends('layouts.app')
@php
    $pageTitle = match($tab) {
        'categories' => 'Catégories',
        'suppliers'  => 'Fournisseurs',
        'password'   => 'Mot de passe',
        default      => 'Mon profil',
    };
@endphp
@section('title', $pageTitle)
@section('page-title', $pageTitle)

@section('content')

{{-- ── Onglets (uniquement sur profil/password) ────────────────────────── --}}
@if(in_array($tab, ['profil', 'password']))
<div class="flex gap-1 mb-5 bg-white border border-gray-100 rounded-xl p-1.5 shadow-sm w-fit">
    @foreach(['profil' => ['fas fa-user', 'Profil'], 'password' => ['fas fa-lock', 'Mot de passe']] as $key => [$icon, $label])
    <a href="{{ route('profile.edit', ['tab' => $key]) }}"
       class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition
              {{ $tab === $key ? 'bg-blue-600 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100' }}">
        <i class="{{ $icon }}"></i> {{ $label }}
    </a>
    @endforeach
</div>
@endif

{{-- ── Messages flash ───────────────────────────────────────────────────── --}}
@foreach(['success','success_password','error'] as $msg)
    @if(session($msg))
    <div class="mb-4 rounded-lg px-4 py-3 text-sm flex items-center gap-2
                {{ $msg === 'error' ? 'bg-red-50 border border-red-200 text-red-700' : 'bg-green-50 border border-green-200 text-green-700' }}">
        <i class="fas {{ $msg === 'error' ? 'fa-exclamation-circle' : 'fa-check-circle' }}"></i>
        {{ session($msg) }}
    </div>
    @endif
@endforeach

{{-- ════════════════════════════════════════════════════════════════════════
     ONGLET : PROFIL
═══════════════════════════════════════════════════════════════════════════ --}}
@if($tab === 'profil')
<div class="max-w-2xl">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h3 class="text-sm font-semibold text-gray-700 mb-5">Informations personnelles</h3>

        @if($errors->hasAny(['name','email','phone','avatar']))
        <div class="bg-red-50 border border-red-200 text-red-700 rounded-lg p-3 mb-4 text-sm">
            <ul class="list-disc list-inside space-y-1">
                @foreach(['name','email','phone','avatar'] as $f)
                    @error($f)<li>{{ $message }}</li>@enderror
                @endforeach
            </ul>
        </div>
        @endif

        <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
            @csrf

            <div class="flex items-center gap-5 mb-6">
                <div class="relative group">
                    @if($user->avatar)
                        <img src="{{ Storage::url($user->avatar) }}" id="avatarPreview"
                             class="w-20 h-20 rounded-full object-cover border-2 border-gray-200">
                    @else
                        <div id="avatarPreview"
                             class="w-20 h-20 rounded-full bg-blue-100 flex items-center justify-center border-2 border-gray-200">
                            <span class="text-blue-700 font-bold text-2xl">{{ strtoupper(substr($user->name,0,2)) }}</span>
                        </div>
                    @endif
                    <label for="avatarInput"
                           class="absolute inset-0 flex items-center justify-center rounded-full bg-black/40 opacity-0 group-hover:opacity-100 cursor-pointer transition">
                        <i class="fas fa-camera text-white text-lg"></i>
                    </label>
                </div>
                <div>
                    <label for="avatarInput" class="cursor-pointer text-sm text-blue-600 hover:text-blue-800 font-medium">Changer la photo</label>
                    <p class="text-xs text-gray-400 mt-0.5">JPG, PNG — max 2 Mo</p>
                    <input type="file" id="avatarInput" name="avatar" accept="image/*" class="hidden" onchange="previewAvatar(this)">
                </div>
            </div>

            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Nom complet *</label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Adresse email *</label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Téléphone</label>
                    <input type="text" name="phone" value="{{ old('phone', $user->phone) }}"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="+229 97 00 00 00">
                </div>
            </div>

            <div class="mt-5 pt-4 border-t border-gray-100 flex items-center justify-between">
                <p class="text-xs text-gray-400">
                    Compte depuis {{ $user->created_at->format('d/m/Y') }}
                    @if($user->roles->count())· <span class="text-indigo-600">{{ $user->roles->first()?->name }}</span>@endif
                </p>
                <button type="submit" class="bg-blue-600 text-white px-5 py-2 rounded-lg text-sm font-semibold hover:bg-blue-700">
                    Enregistrer
                </button>
            </div>
        </form>
    </div>
</div>
@endif

{{-- ════════════════════════════════════════════════════════════════════════
     ONGLET : MOT DE PASSE
═══════════════════════════════════════════════════════════════════════════ --}}
@if($tab === 'password')
<div class="max-w-2xl">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h3 class="text-sm font-semibold text-gray-700 mb-5">Changer le mot de passe</h3>

        @if($errors->hasAny(['current_password','password']))
        <div class="bg-red-50 border border-red-200 text-red-700 rounded-lg p-3 mb-4 text-sm">
            @error('current_password')<p>{{ $message }}</p>@enderror
            @error('password')<p>{{ $message }}</p>@enderror
        </div>
        @endif

        <form method="POST" action="{{ route('profile.password') }}">
            @csrf @method('PUT')
            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Mot de passe actuel *</label>
                    <div class="relative">
                        <input type="password" name="current_password" id="currentPwd" required
                               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 pr-10">
                        <button type="button" onclick="togglePwd('currentPwd',this)" class="absolute right-3 top-2.5 text-gray-400 hover:text-gray-600"><i class="fas fa-eye text-sm"></i></button>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Nouveau mot de passe *</label>
                    <div class="relative">
                        <input type="password" name="password" id="newPwd" required oninput="checkStrength(this.value)"
                               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 pr-10">
                        <button type="button" onclick="togglePwd('newPwd',this)" class="absolute right-3 top-2.5 text-gray-400 hover:text-gray-600"><i class="fas fa-eye text-sm"></i></button>
                    </div>
                    <div class="mt-1.5 h-1.5 w-full bg-gray-100 rounded-full overflow-hidden">
                        <div id="strengthBar" class="h-full rounded-full transition-all duration-300 w-0"></div>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Confirmer *</label>
                    <div class="relative">
                        <input type="password" name="password_confirmation" id="confirmPwd" required
                               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 pr-10">
                        <button type="button" onclick="togglePwd('confirmPwd',this)" class="absolute right-3 top-2.5 text-gray-400 hover:text-gray-600"><i class="fas fa-eye text-sm"></i></button>
                    </div>
                </div>
            </div>
            <div class="mt-5 pt-4 border-t border-gray-100 flex justify-end">
                <button type="submit" class="bg-green-600 text-white px-5 py-2 rounded-lg text-sm font-semibold hover:bg-green-700">
                    Changer le mot de passe
                </button>
            </div>
        </form>
    </div>
</div>
@endif

{{-- ════════════════════════════════════════════════════════════════════════
     ONGLET : CATÉGORIES
═══════════════════════════════════════════════════════════════════════════ --}}
@if($tab === 'categories')
<div class="max-w-3xl space-y-4">

    {{-- Formulaire ajout --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <h3 class="text-sm font-semibold text-gray-700 mb-4">
            <i class="fas fa-plus-circle text-blue-500 mr-1"></i> Nouvelle catégorie
        </h3>
        <form method="POST" action="{{ route('categories.store') }}">
            @csrf
            <div class="grid grid-cols-2 gap-3">
                <div class="col-span-2 md:col-span-1">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Nom *</label>
                    <input type="text" name="name" value="{{ old('name') }}" required
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('name') border-red-400 @enderror"
                           placeholder="Ex : Quincaillerie, Plomberie…">
                    @error('name')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Catégorie parente</label>
                    <select name="parent_id" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">— Aucune (principale) —</option>
                        @foreach($categories->whereNull('parent_id') as $cat)
                            <option value="{{ $cat->id }}" {{ old('parent_id') == $cat->id ? 'selected' : '' }}>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Icône <span class="text-gray-400">(classe Font Awesome)</span></label>
                    <input type="text" name="icon" value="{{ old('icon') }}"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                           placeholder="fas fa-tools">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Ordre d'affichage</label>
                    <input type="number" name="sort_order" value="{{ old('sort_order', 0) }}" min="0"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
            <div class="mt-3 flex justify-end">
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-blue-700 transition flex items-center gap-2">
                    <i class="fas fa-plus"></i> Ajouter
                </button>
            </div>
        </form>
    </div>

    {{-- Liste des catégories --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-gray-700">
                <i class="fas fa-list text-gray-400 mr-1"></i> {{ $categories->count() }} catégorie(s)
            </h3>
        </div>
        @forelse($categories as $cat)
        <div class="flex items-center gap-3 px-5 py-3 border-b border-gray-50 last:border-0 hover:bg-gray-50 group" id="cat-row-{{ $cat->id }}">

            {{-- Vue normale --}}
            <div class="flex items-center gap-3 flex-1 min-w-0" id="cat-view-{{ $cat->id }}">
                @if($cat->icon)
                    <div class="w-8 h-8 bg-blue-50 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i class="{{ $cat->icon }} text-blue-500 text-sm"></i>
                    </div>
                @else
                    <div class="w-8 h-8 bg-gray-100 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-tag text-gray-400 text-sm"></i>
                    </div>
                @endif
                <div class="min-w-0">
                    <p class="text-sm font-medium text-gray-800 truncate">
                        @if($cat->parent_id)
                            <span class="text-gray-400 text-xs">↳ </span>
                        @endif
                        {{ $cat->name }}
                    </p>
                    <p class="text-xs text-gray-400">
                        {{ $cat->articles()->count() }} article(s)
                        @if($cat->parent) · sous {{ $cat->parent->name }}@endif
                    </p>
                </div>
                <span class="ml-auto text-xs px-2 py-0.5 rounded-full {{ $cat->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                    {{ $cat->is_active ? 'Actif' : 'Inactif' }}
                </span>
            </div>

            {{-- Formulaire édition inline (caché par défaut) --}}
            <form method="POST" action="{{ route('categories.update', $cat) }}"
                  id="cat-form-{{ $cat->id }}" class="hidden flex-1 items-center gap-2 flex-wrap">
                @csrf @method('PUT')
                <input type="text" name="name" value="{{ $cat->name }}" required
                       class="border border-gray-200 rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 w-36">
                <select name="parent_id" class="border border-gray-200 rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">— Principale —</option>
                    @foreach($categories->whereNull('parent_id')->where('id','!=',$cat->id) as $p)
                        <option value="{{ $p->id }}" {{ $cat->parent_id == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                    @endforeach
                </select>
                <input type="text" name="icon" value="{{ $cat->icon }}" placeholder="fas fa-tools"
                       class="border border-gray-200 rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 w-28">
                <label class="flex items-center gap-1 text-xs text-gray-600 cursor-pointer">
                    <input type="checkbox" name="is_active" value="1" {{ $cat->is_active ? 'checked' : '' }}
                           class="rounded border-gray-300"> Actif
                </label>
                <button type="submit" class="bg-blue-600 text-white px-3 py-1.5 rounded-lg text-xs font-semibold hover:bg-blue-700">
                    <i class="fas fa-check"></i>
                </button>
                <button type="button" onclick="cancelEdit('cat', {{ $cat->id }})"
                        class="border border-gray-200 text-gray-500 px-3 py-1.5 rounded-lg text-xs hover:bg-gray-50">
                    <i class="fas fa-times"></i>
                </button>
            </form>

            {{-- Actions --}}
            <div class="flex items-center gap-1 flex-shrink-0" id="cat-actions-{{ $cat->id }}">
                <button type="button" onclick="startEdit('cat', {{ $cat->id }})"
                        class="opacity-0 group-hover:opacity-100 text-blue-500 hover:text-blue-700 p-1.5 rounded transition" title="Modifier">
                    <i class="fas fa-pen text-xs"></i>
                </button>
                <form method="POST" action="{{ route('categories.destroy', $cat) }}"
                      onsubmit="return confirm('Supprimer « {{ $cat->name }} » ? Les articles liés seront désassociés.')">
                    @csrf @method('DELETE')
                    <button type="submit"
                            class="opacity-0 group-hover:opacity-100 text-red-400 hover:text-red-600 p-1.5 rounded transition" title="Supprimer">
                        <i class="fas fa-trash text-xs"></i>
                    </button>
                </form>
            </div>
        </div>
        @empty
        <div class="px-5 py-8 text-center text-sm text-gray-400">
            <i class="fas fa-tags text-2xl mb-2 block text-gray-200"></i>
            Aucune catégorie. Créez-en une ci-dessus.
        </div>
        @endforelse
    </div>
</div>
@endif

{{-- ════════════════════════════════════════════════════════════════════════
     ONGLET : FOURNISSEURS
═══════════════════════════════════════════════════════════════════════════ --}}
@if($tab === 'suppliers')
<div class="max-w-3xl space-y-4">

    {{-- Formulaire ajout --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <h3 class="text-sm font-semibold text-gray-700 mb-4">
            <i class="fas fa-plus-circle text-blue-500 mr-1"></i> Nouveau fournisseur
        </h3>
        <form method="POST" action="{{ route('suppliers.store') }}">
            @csrf
            <div class="grid grid-cols-2 gap-3">
                <div class="col-span-2">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Nom du fournisseur *</label>
                    <input type="text" name="name" value="{{ old('name') }}" required
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('name') border-red-400 @enderror"
                           placeholder="Ex : Acier Pro SARL">
                    @error('name')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Contact</label>
                    <input type="text" name="contact_name" value="{{ old('contact_name') }}"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                           placeholder="Nom du responsable">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Téléphone</label>
                    <input type="text" name="phone" value="{{ old('phone') }}"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                           placeholder="+229 97 00 00 00">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Ville</label>
                    <input type="text" name="city" value="{{ old('city') }}"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="col-span-2">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Adresse</label>
                    <input type="text" name="address" value="{{ old('address') }}"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Conditions de paiement</label>
                    <input type="text" name="payment_terms" value="{{ old('payment_terms') }}"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                           placeholder="Ex : 30 jours net">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Pays</label>
                    <input type="text" name="country" value="{{ old('country', 'Bénin') }}"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="col-span-2">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Notes</label>
                    <textarea name="notes" rows="2"
                              class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                              placeholder="Informations complémentaires…">{{ old('notes') }}</textarea>
                </div>
            </div>
            <div class="mt-3 flex justify-end">
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-blue-700 transition flex items-center gap-2">
                    <i class="fas fa-plus"></i> Ajouter
                </button>
            </div>
        </form>
    </div>

    {{-- Liste des fournisseurs --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100">
            <h3 class="text-sm font-semibold text-gray-700">
                <i class="fas fa-list text-gray-400 mr-1"></i> {{ $suppliers->count() }} fournisseur(s)
            </h3>
        </div>
        @forelse($suppliers as $sup)
        <div class="flex items-start gap-3 px-5 py-3 border-b border-gray-50 last:border-0 hover:bg-gray-50 group" id="sup-row-{{ $sup->id }}">

            {{-- Vue normale --}}
            <div class="flex items-start gap-3 flex-1 min-w-0" id="sup-view-{{ $sup->id }}">
                <div class="w-9 h-9 bg-indigo-50 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5">
                    <i class="fas fa-truck text-indigo-400 text-sm"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-gray-800">{{ $sup->name }}</p>
                    <div class="flex flex-wrap gap-x-3 text-xs text-gray-400 mt-0.5">
                        @if($sup->contact_name)<span><i class="fas fa-user mr-0.5"></i>{{ $sup->contact_name }}</span>@endif
                        @if($sup->phone)<span><i class="fas fa-phone mr-0.5"></i>{{ $sup->phone }}</span>@endif
                        @if($sup->email)<span><i class="fas fa-envelope mr-0.5"></i>{{ $sup->email }}</span>@endif
                        @if($sup->city)<span><i class="fas fa-map-marker-alt mr-0.5"></i>{{ $sup->city }}</span>@endif
                    </div>
                    @if($sup->payment_terms)
                    <p class="text-xs text-gray-400 mt-0.5"><i class="fas fa-calendar mr-0.5"></i>{{ $sup->payment_terms }}</p>
                    @endif
                </div>
                <span class="ml-auto text-xs px-2 py-0.5 rounded-full flex-shrink-0 {{ $sup->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                    {{ $sup->is_active ? 'Actif' : 'Inactif' }}
                </span>
            </div>

            {{-- Formulaire édition inline --}}
            <form method="POST" action="{{ route('suppliers.update', $sup) }}"
                  id="sup-form-{{ $sup->id }}" class="hidden flex-1 flex-wrap gap-2 items-start">
                @csrf @method('PUT')
                <div class="grid grid-cols-2 gap-2 flex-1">
                    <input type="text" name="name" value="{{ $sup->name }}" required placeholder="Nom *"
                           class="border border-gray-200 rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <input type="text" name="contact_name" value="{{ $sup->contact_name }}" placeholder="Contact"
                           class="border border-gray-200 rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <input type="text" name="phone" value="{{ $sup->phone }}" placeholder="Téléphone"
                           class="border border-gray-200 rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <input type="email" name="email" value="{{ $sup->email }}" placeholder="Email"
                           class="border border-gray-200 rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <input type="text" name="city" value="{{ $sup->city }}" placeholder="Ville"
                           class="border border-gray-200 rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <input type="text" name="payment_terms" value="{{ $sup->payment_terms }}" placeholder="Conditions paiement"
                           class="border border-gray-200 rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <div class="col-span-2 flex items-center gap-3">
                        <label class="flex items-center gap-1 text-xs text-gray-600 cursor-pointer">
                            <input type="checkbox" name="is_active" value="1" {{ $sup->is_active ? 'checked' : '' }}
                                   class="rounded border-gray-300"> Actif
                        </label>
                        <button type="submit" class="bg-blue-600 text-white px-3 py-1.5 rounded-lg text-xs font-semibold hover:bg-blue-700">
                            <i class="fas fa-check mr-1"></i>Enregistrer
                        </button>
                        <button type="button" onclick="cancelEdit('sup', {{ $sup->id }})"
                                class="border border-gray-200 text-gray-500 px-3 py-1.5 rounded-lg text-xs hover:bg-gray-50">
                            Annuler
                        </button>
                    </div>
                </div>
            </form>

            {{-- Actions --}}
            <div class="flex items-center gap-1 flex-shrink-0 mt-1" id="sup-actions-{{ $sup->id }}">
                <button type="button" onclick="startEdit('sup', {{ $sup->id }})"
                        class="opacity-0 group-hover:opacity-100 text-blue-500 hover:text-blue-700 p-1.5 rounded transition" title="Modifier">
                    <i class="fas fa-pen text-xs"></i>
                </button>
                <form method="POST" action="{{ route('suppliers.destroy', $sup) }}"
                      onsubmit="return confirm('Supprimer le fournisseur « {{ $sup->name }} » ?')">
                    @csrf @method('DELETE')
                    <button type="submit"
                            class="opacity-0 group-hover:opacity-100 text-red-400 hover:text-red-600 p-1.5 rounded transition" title="Supprimer">
                        <i class="fas fa-trash text-xs"></i>
                    </button>
                </form>
            </div>
        </div>
        @empty
        <div class="px-5 py-8 text-center text-sm text-gray-400">
            <i class="fas fa-truck text-2xl mb-2 block text-gray-200"></i>
            Aucun fournisseur. Ajoutez-en un ci-dessus.
        </div>
        @endforelse
    </div>
</div>
@endif

@endsection

@push('scripts')
<script>
function previewAvatar(input) {
    if (!input.files || !input.files[0]) return;
    const reader = new FileReader();
    reader.onload = e => {
        const preview = document.getElementById('avatarPreview');
        if (preview.tagName === 'IMG') {
            preview.src = e.target.result;
        } else {
            const img = document.createElement('img');
            img.src = e.target.result;
            img.id  = 'avatarPreview';
            img.className = 'w-20 h-20 rounded-full object-cover border-2 border-gray-200';
            preview.replaceWith(img);
        }
    };
    reader.readAsDataURL(input.files[0]);
}

function togglePwd(id, btn) {
    const input = document.getElementById(id);
    const icon  = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}

function checkStrength(pwd) {
    const bar = document.getElementById('strengthBar');
    if (!bar) return;
    let score = 0;
    if (pwd.length >= 8)          score++;
    if (/[A-Z]/.test(pwd))        score++;
    if (/[0-9]/.test(pwd))        score++;
    if (/[^A-Za-z0-9]/.test(pwd)) score++;
    const colors = ['','bg-red-500','bg-yellow-500','bg-blue-500','bg-green-500'];
    const widths = ['0%','25%','50%','75%','100%'];
    bar.style.width = widths[score];
    bar.className   = `h-full rounded-full transition-all duration-300 ${colors[score]}`;
}

function startEdit(type, id) {
    document.getElementById(`${type}-view-${id}`)?.classList.add('hidden');
    document.getElementById(`${type}-actions-${id}`)?.classList.add('hidden');
    const form = document.getElementById(`${type}-form-${id}`);
    form?.classList.remove('hidden');
    form?.classList.add('flex');
}

function cancelEdit(type, id) {
    document.getElementById(`${type}-view-${id}`)?.classList.remove('hidden');
    document.getElementById(`${type}-actions-${id}`)?.classList.remove('hidden');
    const form = document.getElementById(`${type}-form-${id}`);
    form?.classList.add('hidden');
    form?.classList.remove('flex');
}
</script>
@endpush
