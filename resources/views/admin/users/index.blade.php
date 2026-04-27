@extends('layouts.app')
@section('title', 'Utilisateurs')
@section('page-title', 'Gestion des Utilisateurs')
@section('content')

<div class="flex justify-between items-center mb-6">
    <form method="GET" class="flex gap-3">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Rechercher..."
               class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 w-56">
        <select name="role" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
            <option value="">Tous les rôles</option>
            @foreach(['admin'=>'Admin','manager'=>'Manager','user'=>'Utilisateur','signer'=>'Signataire'] as $v => $l)
            <option value="{{ $v }}" {{ request('role') === $v ? 'selected' : '' }}>{{ $l }}</option>
            @endforeach
        </select>
        <button class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg text-sm">Filtrer</button>
    </form>
    <a href="{{ route('admin.users.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-indigo-700 flex items-center gap-2">
        <i class="fas fa-plus"></i> Nouvel utilisateur
    </a>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
                <th class="text-left text-xs font-semibold text-gray-500 uppercase px-5 py-3">Utilisateur</th>
                <th class="text-left text-xs font-semibold text-gray-500 uppercase px-5 py-3">Rôle</th>
                <th class="text-left text-xs font-semibold text-gray-500 uppercase px-5 py-3">Statut</th>
                <th class="text-left text-xs font-semibold text-gray-500 uppercase px-5 py-3">Créé le</th>
                <th class="text-left text-xs font-semibold text-gray-500 uppercase px-5 py-3">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @forelse($users as $user)
            <tr class="hover:bg-gray-50">
                <td class="px-5 py-4">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 bg-indigo-100 rounded-full flex items-center justify-center text-xs font-bold text-indigo-700">
                            {{ strtoupper(substr($user->name, 0, 2)) }}
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-800">{{ $user->name }}</p>
                            <p class="text-xs text-gray-400">{{ $user->email }}</p>
                        </div>
                    </div>
                </td>
                <td class="px-5 py-4">
                    <span class="text-xs px-2.5 py-1 rounded-full bg-gray-100 text-gray-700">{{ ucfirst($user->role ?? 'user') }}</span>
                </td>
                <td class="px-5 py-4">
                    <span class="text-xs px-2.5 py-1 rounded-full font-medium
                        @if($user->status === 'active') bg-green-100 text-green-700
                        @elseif($user->status === 'suspended') bg-red-100 text-red-700
                        @else bg-gray-100 text-gray-600 @endif">
                        {{ ucfirst($user->status ?? 'active') }}
                    </span>
                </td>
                <td class="px-5 py-4 text-xs text-gray-500">{{ $user->created_at->format('d/m/Y') }}</td>
                <td class="px-5 py-4">
                    <div class="flex gap-2">
                        <a href="{{ route('admin.users.edit', $user) }}" class="text-yellow-600 hover:text-yellow-800 text-sm p-1" title="Modifier">
                            <i class="fas fa-edit"></i>
                        </a>
                        @if($user->id !== auth()->id())
                        <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Supprimer cet utilisateur ?')">
                            @csrf @method('DELETE')
                            <button class="text-red-500 hover:text-red-700 text-sm p-1"><i class="fas fa-trash"></i></button>
                        </form>
                        @endif
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="5" class="px-5 py-10 text-center text-gray-400">Aucun utilisateur</td></tr>
            @endforelse
        </tbody>
    </table>
    @if($users->hasPages())
    <div class="px-5 py-4 border-t border-gray-100">{{ $users->withQueryString()->links() }}</div>
    @endif
</div>
@endsection
