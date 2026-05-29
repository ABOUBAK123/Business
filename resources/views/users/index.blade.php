@extends('layouts.app')
@section('title', 'Utilisateurs')
@section('page-title', 'Gestion des utilisateurs')

@section('content')
<div class="flex items-center justify-between mb-4 flex-wrap gap-2">
    <form class="flex gap-2 flex-wrap">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Nom, email..."
               class="border border-gray-200 rounded-lg px-3 py-2 text-sm w-48 focus:ring-2 focus:ring-blue-500">
        <button class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700">Filtrer</button>
    </form>
    <a href="{{ route('users.create') }}"
       class="flex items-center gap-2 bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700">
        <i class="fas fa-plus"></i> Nouvel utilisateur
    </a>
</div>

@if(session('success'))
<div class="bg-green-50 border border-green-200 text-green-700 rounded-lg p-3 mb-4 text-sm">{{ session('success') }}</div>
@endif

<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Utilisateur</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Rôle</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Succursale</th>
                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Statut</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Dernière connexion</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @forelse($users as $user)
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center text-blue-700 font-bold text-xs">
                            {{ strtoupper(substr($user->name, 0, 2)) }}
                        </div>
                        <div>
                            <p class="font-medium text-gray-800">{{ $user->name }}</p>
                            <p class="text-xs text-gray-400">{{ $user->email }}</p>
                        </div>
                    </div>
                </td>
                <td class="px-4 py-3">
                    @foreach($user->roles as $role)
                        <span class="text-xs bg-indigo-100 text-indigo-700 px-2 py-0.5 rounded-full">{{ $role->name }}</span>
                    @endforeach
                </td>
                <td class="px-4 py-3 text-gray-600">{{ $user->branch?->name ?? '—' }}</td>
                <td class="px-4 py-3 text-center">
                    @if($user->is_active)
                        <span class="bg-green-100 text-green-700 text-xs px-2 py-0.5 rounded-full">Actif</span>
                    @else
                        <span class="bg-gray-100 text-gray-500 text-xs px-2 py-0.5 rounded-full">Inactif</span>
                    @endif
                </td>
                <td class="px-4 py-3 text-xs text-gray-500">
                    {{ $user->last_login_at ? $user->last_login_at->diffForHumans() : 'Jamais' }}
                </td>
                <td class="px-4 py-3">
                    <div class="flex items-center justify-end gap-2">
                        <a href="{{ route('users.edit', $user) }}" class="text-gray-400 hover:text-yellow-600" title="Modifier">
                            <i class="fas fa-pen"></i>
                        </a>
                        @if($user->id !== auth()->id())
                        <form method="POST" action="{{ route('users.toggle-active', $user) }}">
                            @csrf @method('PATCH')
                            <button type="submit" class="text-gray-400 hover:text-{{ $user->is_active ? 'red' : 'green' }}-600"
                                    title="{{ $user->is_active ? 'Désactiver' : 'Activer' }}">
                                <i class="fas fa-{{ $user->is_active ? 'user-slash' : 'user-check' }}"></i>
                            </button>
                        </form>
                        @endif
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="6" class="px-4 py-12 text-center text-gray-400">
                <i class="fas fa-users text-3xl mb-2 block"></i>Aucun utilisateur trouvé
            </td></tr>
            @endforelse
        </tbody>
    </table>
    @if($users->hasPages())
        <div class="px-4 py-3 border-t border-gray-100">{{ $users->links() }}</div>
    @endif
</div>
@endsection
