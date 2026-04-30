@extends('layouts.app')
@section('title', 'Réception')
@section('page-title', 'Réception')
@section('page-subtitle', 'Documents reçus depuis d\'autres administrations')
@section('content')

{{-- Barre de recherche --}}
<div class="flex items-center gap-4 mb-6">
    <form method="GET" action="{{ route('reception.index') }}" class="flex-1 max-w-md flex gap-2">
        <div class="relative flex-1">
            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
            <input type="text" name="q" value="{{ $search }}" placeholder="Rechercher un document reçu…"
                class="w-full border border-gray-300 rounded-xl pl-9 pr-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#2453d6] bg-white">
        </div>
        <button type="submit" class="px-4 py-2.5 bg-[#2453d6] text-white rounded-xl text-sm font-semibold hover:bg-[#1f47bb] transition">
            Chercher
        </button>
        @if($search)
            <a href="{{ route('reception.index') }}" class="px-4 py-2.5 border border-gray-300 text-gray-600 rounded-xl text-sm hover:bg-gray-100 transition">
                <i class="fas fa-times"></i>
            </a>
        @endif
    </form>
</div>

@if($documents->isEmpty())
    <div class="flex flex-col items-center justify-center py-24 text-center">
        <div class="w-20 h-20 rounded-full bg-gray-100 flex items-center justify-center mb-5">
            <i class="fas fa-inbox text-4xl text-gray-300"></i>
        </div>
        <h3 class="text-lg font-semibold text-gray-700 mb-1">Aucun document reçu</h3>
        <p class="text-sm text-gray-400 max-w-sm">
            Les documents partagés avec vous par d'autres administrations apparaîtront ici.
        </p>
    </div>
@else
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="text-base font-bold text-gray-800 flex items-center gap-2">
                <i class="fas fa-inbox text-[#2453d6]"></i> Documents reçus
            </h2>
            <span class="text-sm text-gray-500">{{ $documents->total() }} document(s)</span>
        </div>

        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-100">
                <tr>
                    <th class="text-left px-5 py-3 font-semibold text-gray-600">Document</th>
                    <th class="text-left px-5 py-3 font-semibold text-gray-600">Expéditeur</th>
                    <th class="text-left px-5 py-3 font-semibold text-gray-600">Demandeur</th>
                    <th class="text-left px-5 py-3 font-semibold text-gray-600">Téléphone</th>
                    <th class="text-left px-5 py-3 font-semibold text-gray-600">Statut</th>
                    <th class="text-left px-5 py-3 font-semibold text-gray-600">Reçu le</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($documents as $doc)
                @php $shareInfo = $sharesInfo[$doc->id] ?? null; @endphp
                <tr class="hover:bg-gray-50/50 transition">
                    <td class="px-5 py-4">
                        <div class="flex items-center gap-3">
                            <div class="h-9 w-9 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-file-alt text-blue-500 text-sm"></i>
                            </div>
                            <div>
                                <p class="font-semibold text-gray-800 truncate max-w-xs">{{ $doc->title }}</p>
                                <p class="text-xs text-gray-400">{{ $doc->file_size ? number_format($doc->file_size / 1024, 0) . ' KB' : '—' }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-5 py-4 text-gray-600">{{ $doc->owner?->name ?? '—' }}</td>
                    <td class="px-5 py-4">
                        @if($shareInfo?->applicant_full_name)
                            <div class="font-medium text-gray-800 text-xs">{{ $shareInfo->applicant_full_name }}</div>
                            @if($shareInfo->applicant_email)
                                <div class="text-xs text-gray-400">{{ $shareInfo->applicant_email }}</div>
                            @endif
                            @if($shareInfo->tracking_number)
                                <div class="text-xs text-indigo-500 font-mono">{{ $shareInfo->tracking_number }}</div>
                            @endif
                        @else
                            <span class="text-gray-400">—</span>
                        @endif
                    </td>
                    <td class="px-5 py-4 text-gray-600 text-xs">
                        {{ $shareInfo?->applicant_phone ?? '—' }}
                    </td>
                    <td class="px-5 py-4">
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold
                            {{ $doc->status === 'signed' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700' }}">
                            {{ ucfirst($doc->status) }}
                        </span>
                    </td>
                    <td class="px-5 py-4 text-gray-500 text-xs">{{ $doc->created_at?->format('d/m/Y H:i') }}</td>
                    <td class="px-5 py-4 text-right">
                        @if($doc->file_path)
                        <a href="{{ route('documents.download', $doc) }}"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-medium rounded-lg transition">
                            <i class="fas fa-download text-xs"></i> Télécharger
                        </a>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        @if($documents->hasPages())
        <div class="px-5 py-4 border-t border-gray-100">
            {{ $documents->links() }}
        </div>
        @endif
    </div>
@endif

@endsection
