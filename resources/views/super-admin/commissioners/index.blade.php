@extends('layouts.app')

@section('title', 'Commissionnaires')
@section('page-title', 'Gestion des commissionnaires')

@section('content')
<div class="flex justify-between items-center mb-4">
    @if($pendingCount > 0)
        <span class="text-xs px-2.5 py-1 rounded-full bg-yellow-100 text-yellow-700 font-medium">
            {{ $pendingCount }} compte(s) en attente de validation
        </span>
    @else
        <span></span>
    @endif
    <a href="{{ route('super-admin.commissioners.create') }}"
       class="bg-blue-600 text-white text-sm font-semibold px-4 py-2 rounded-lg hover:bg-blue-700 transition flex items-center gap-2">
        <i class="fas fa-plus"></i> Nouveau commissionnaire
    </a>
</div>

<div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Nom</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Email</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Code</th>
                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Boutiques</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Total commissions</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Depuis</th>
                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Pièce d'identité</th>
                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Statut</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @forelse($commissioners as $commissioner)
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3 font-medium text-gray-800">{{ $commissioner->name }}</td>
                <td class="px-4 py-3 text-gray-600">{{ $commissioner->email }}</td>
                <td class="px-4 py-3 text-gray-500 font-mono text-xs">{{ $commissioner->referral_code ?? '—' }}</td>
                <td class="px-4 py-3 text-center text-gray-700">{{ $commissioner->shops_count ?? 0 }}</td>
                <td class="px-4 py-3 text-right font-semibold text-gray-800">
                    {{ number_format($commissioner->total_commissions ?? 0, 0, ',', ' ') }} XOF
                </td>
                <td class="px-4 py-3 text-gray-500 text-xs">{{ $commissioner->created_at->format('d/m/Y') }}</td>
                <td class="px-4 py-3 text-center">
                    @if($commissioner->id_document_path)
                        <a href="{{ route('super-admin.commissioners.document', $commissioner) }}" target="_blank"
                           class="inline-flex items-center gap-1 text-blue-600 hover:text-blue-700 text-xs font-medium">
                            <i class="fas fa-id-card"></i>
                            {{ $commissioner->id_document_type === 'passeport' ? 'Passeport' : 'CNI' }}
                        </a>
                    @else
                        <span class="text-xs text-gray-300">—</span>
                    @endif
                </td>
                <td class="px-4 py-3 text-center">
                    <span class="text-xs font-medium px-2 py-0.5 rounded-full
                        {{ $commissioner->is_active ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                        {{ $commissioner->is_active ? 'Actif' : 'En attente' }}
                    </span>
                </td>
                <td class="px-4 py-3">
                    <div class="flex items-center justify-end gap-2">
                        <form method="POST" action="{{ route('super-admin.commissioners.toggle-status', $commissioner) }}">
                            @csrf @method('PATCH')
                            <button type="submit" class="text-xs font-medium px-2 py-1 rounded-lg
                                {{ $commissioner->is_active ? 'text-red-500 hover:bg-red-50' : 'text-green-600 hover:bg-green-50' }}"
                                    title="{{ $commissioner->is_active ? 'Désactiver' : 'Activer' }}">
                                <i class="fas fa-{{ $commissioner->is_active ? 'ban' : 'check-circle' }}"></i>
                            </button>
                        </form>
                        <form method="POST" action="{{ route('super-admin.commissioners.destroy', $commissioner) }}"
                              onsubmit="return confirm('Supprimer ce commissionnaire ?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-red-500 hover:text-red-700 text-xs px-2 py-1">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="9" class="px-4 py-8 text-center text-gray-400">Aucun commissionnaire enregistré.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    @if($commissioners->hasPages())
    <div class="px-4 py-3 border-t border-gray-100">
        {{ $commissioners->links() }}
    </div>
    @endif
</div>
@endsection
