@extends('layouts.app')

@section('title', 'Commissionnaires')
@section('page-title', 'Gestion des commissionnaires')

@section('content')
<div class="flex justify-end mb-4">
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
                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Boutiques</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Total commissions</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Depuis</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @forelse($commissioners as $commissioner)
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3 font-medium text-gray-800">{{ $commissioner->name }}</td>
                <td class="px-4 py-3 text-gray-600">{{ $commissioner->email }}</td>
                <td class="px-4 py-3 text-center text-gray-700">{{ $commissioner->shops_count ?? 0 }}</td>
                <td class="px-4 py-3 text-right font-semibold text-gray-800">
                    {{ number_format($commissioner->total_commissions ?? 0, 0, ',', ' ') }} XOF
                </td>
                <td class="px-4 py-3 text-gray-500 text-xs">{{ $commissioner->created_at->format('d/m/Y') }}</td>
                <td class="px-4 py-3 text-right">
                    <form method="POST" action="{{ route('super-admin.commissioners.destroy', $commissioner) }}"
                          onsubmit="return confirm('Supprimer ce commissionnaire ?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-red-500 hover:text-red-700 text-xs">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="px-4 py-8 text-center text-gray-400">Aucun commissionnaire enregistré.</td>
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
