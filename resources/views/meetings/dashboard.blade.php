@extends('layouts.app')
@section('title', 'Présences réunion')
@section('page-title', 'Présences')
@section('page-subtitle', $meeting->title)

@section('content')
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 mb-4">
    <div class="text-sm text-gray-700">Participants attendus: <strong>{{ $meeting->participants->count() }}</strong></div>
    <div class="text-sm text-gray-700">Présents: <strong>{{ $meeting->attendances->count() }}</strong></div>
</div>

<div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
                <th class="text-left px-4 py-3 font-semibold text-gray-700">Nom</th>
                <th class="text-left px-4 py-3 font-semibold text-gray-700">Identifiant</th>
                <th class="text-left px-4 py-3 font-semibold text-gray-700">Email</th>
                <th class="text-left px-4 py-3 font-semibold text-gray-700">Heure</th>
            </tr>
        </thead>
        <tbody>
            @forelse($meeting->attendances()->latest('signed_at')->get() as $a)
            <tr class="border-b border-gray-100">
                <td class="px-4 py-3 text-gray-800">{{ $a->full_name }}</td>
                <td class="px-4 py-3 text-gray-600">{{ $a->identifier }}</td>
                <td class="px-4 py-3 text-gray-600">{{ $a->email }}</td>
                <td class="px-4 py-3 text-gray-600">{{ $a->signed_at?->format('d/m/Y H:i:s') }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="4" class="px-4 py-8 text-center text-gray-400">Aucune présence enregistrée.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
