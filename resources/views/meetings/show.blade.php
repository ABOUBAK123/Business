@extends('layouts.app')
@section('title', 'Détail réunion')
@section('page-title', $meeting->title)
@section('page-subtitle', 'Détails, participants et émargement')

@section('content')
@include('meetings._nav')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <div class="lg:col-span-2 bg-white rounded-2xl border border-gray-100 shadow-sm p-5 space-y-3">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
            <div><span class="text-gray-500">Type:</span> {{ $meeting->meeting_type }}</div>
            <div><span class="text-gray-500">Salle:</span> {{ $meeting->room?->name }} ({{ $meeting->room?->location }})</div>
            <div><span class="text-gray-500">Début:</span> {{ $meeting->starts_at?->format('d/m/Y H:i') }}</div>
            <div><span class="text-gray-500">Fin:</span> {{ $meeting->ends_at?->format('d/m/Y H:i') }}</div>
            <div><span class="text-gray-500">Organisateur:</span> {{ $meeting->organizer?->name }}</div>
            <div><span class="text-gray-500">Rédacteur:</span> {{ $meeting->minutesWriter?->name }}</div>
        </div>

        <div>
            <h3 class="font-semibold text-gray-800 mb-1">Ordre du jour</h3>
            <div class="text-sm text-gray-700 whitespace-pre-line">{{ $meeting->agenda ?: 'Aucun ordre du jour.' }}</div>
        </div>

        <div>
            <h3 class="font-semibold text-gray-800 mb-1">Participants ({{ $meeting->participants->count() }})</h3>
            <ul class="text-sm text-gray-700 space-y-1">
                @forelse($meeting->participants as $p)
                <li>- {{ $p->full_name ?: $p->user?->name }} ({{ $p->email ?: $p->user?->email }})</li>
                @empty
                <li class="text-gray-400">Aucun participant enregistré.</li>
                @endforelse
            </ul>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
        <h3 class="font-semibold text-gray-800 mb-3">Émargement QR</h3>
        <div class="text-xs text-gray-500 mb-3">Lien public pour scanner et signer la présence.</div>

        <div id="meeting-qr-print" class="mb-3 border border-gray-200 rounded-xl p-4 bg-gray-50 text-center">
            <div class="text-xs text-gray-500">Scanner pour accéder au formulaire d'émargement</div>
            @if(!empty($qrImageDataUri))
                <img src="{{ $qrImageDataUri }}" alt="QR émargement" class="mx-auto mt-2 h-48 w-48 object-contain">
            @else
                <div class="mt-2 text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-2 py-1">
                    Aperçu QR indisponible. Utilisez le lien ci-dessous.
                </div>
            @endif
            <div class="mt-2 text-[11px] text-gray-600">{{ $meeting->title }}</div>
        </div>

        <input type="text" readonly value="{{ $qrUrl }}" class="w-full border border-gray-300 rounded-lg px-2 py-2 text-xs mb-3">
        <div class="flex items-center gap-2">
            <a href="{{ $qrUrl }}" target="_blank" class="inline-block px-3 py-2 rounded-lg bg-[#2453d6] text-white text-sm font-semibold hover:bg-[#1f47bb]">Ouvrir la page QR</a>
            <button type="button" onclick="window.print()" class="inline-block px-3 py-2 rounded-lg border border-gray-300 text-gray-700 text-sm font-semibold hover:bg-gray-100">Imprimer le QR</button>
        </div>

        <div class="mt-4 pt-4 border-t border-gray-100">
            <a href="{{ route('meetings.dashboard', $meeting) }}" class="text-sm text-[#2453d6] font-semibold hover:underline">Tableau de présence</a>
            <div class="text-xs text-gray-500 mt-1">Présents: {{ $meeting->attendances->count() }}</div>
        </div>
    </div>
</div>

<style>
@media print {
    body * { visibility: hidden !important; }
    #meeting-qr-print, #meeting-qr-print * { visibility: visible !important; }
    #meeting-qr-print {
        position: absolute;
        inset: 0 auto auto 0;
        width: 100%;
        border: 0;
        background: #fff;
        margin: 0;
        padding: 20px;
    }
}
</style>
@endsection
