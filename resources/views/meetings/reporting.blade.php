@extends('layouts.app')
@section('title', 'Reporting réunions')
@section('page-title', 'Reporting et statistiques')
@section('page-subtitle', 'Tableaux de bord et exports')

@section('content')
@include('meetings._nav')

<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-5">
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
        <p class="text-xs text-gray-500">Nombre total de réunions ({{ $year }})</p>
        <p class="text-2xl font-bold text-gray-800">{{ $totalMeetings }}</p>
    </div>
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
        <p class="text-xs text-gray-500">Taux de participation moyen</p>
        <p class="text-2xl font-bold text-gray-800">{{ number_format($avgParticipation, 2) }}%</p>
    </div>
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
        <p class="text-xs text-gray-500">Exports rapides</p>
        <div class="mt-2 flex flex-wrap gap-2">
            <a href="{{ route('meetings.export.csv', ['type' => 'meetings']) }}" class="px-2 py-1 rounded bg-blue-100 text-blue-700 text-xs font-semibold">CSV Réunions</a>
            <a href="{{ route('meetings.export.csv', ['type' => 'attendances']) }}" class="px-2 py-1 rounded bg-emerald-100 text-emerald-700 text-xs font-semibold">CSV Présences</a>
            <a href="{{ route('meetings.export.csv', ['type' => 'minutes']) }}" class="px-2 py-1 rounded bg-indigo-100 text-indigo-700 text-xs font-semibold">CSV Comptes rendus</a>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
        <h3 class="font-semibold text-gray-800 mb-3">Réunions par type</h3>
        <ul class="space-y-1 text-sm">
            @forelse($byType as $type => $count)
                <li class="flex justify-between"><span>{{ $type }}</span><span class="font-semibold">{{ $count }}</span></li>
            @empty
                <li class="text-gray-400">Aucune donnée.</li>
            @endforelse
        </ul>
    </div>

    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
        <h3 class="font-semibold text-gray-800 mb-3">Réunions par salle</h3>
        <ul class="space-y-1 text-sm">
            @forelse($byRoom as $room => $count)
                <li class="flex justify-between"><span>{{ $room }}</span><span class="font-semibold">{{ $count }}</span></li>
            @empty
                <li class="text-gray-400">Aucune donnée.</li>
            @endforelse
        </ul>
    </div>

    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 lg:col-span-2">
        <h3 class="font-semibold text-gray-800 mb-3">Réunions par période</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-2 text-sm">
            @forelse($byMonth as $month => $count)
                <div class="rounded-lg border border-gray-200 px-3 py-2">
                    <div class="text-gray-500 text-xs">{{ $month }}</div>
                    <div class="font-semibold text-gray-800">{{ $count }}</div>
                </div>
            @empty
                <div class="text-gray-400">Aucune donnée.</div>
            @endforelse
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 lg:col-span-2">
        <h3 class="font-semibold text-gray-800 mb-3">Statistiques par utilisateur</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 text-gray-600">
                        <th class="text-left py-2">Utilisateur</th>
                        <th class="text-left py-2">Réunions organisées</th>
                        <th class="text-left py-2">Taux de participation</th>
                        <th class="text-left py-2">Temps passé (min)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($userStats as $row)
                        <tr class="border-b border-gray-50">
                            <td class="py-2">{{ $row['name'] }}</td>
                            <td class="py-2">{{ $row['organized_count'] }}</td>
                            <td class="py-2">{{ $row['participation_rate'] }}%</td>
                            <td class="py-2">{{ $row['time_minutes'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="py-4 text-gray-400">Aucune donnée.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mt-5 flex flex-wrap gap-2">
    <a href="{{ route('meetings.export.pdf.summary', ['mode' => 'monthly']) }}" class="px-3 py-2 rounded-lg bg-gray-900 text-white text-sm font-semibold">Synthèse mensuelle PDF</a>
    <a href="{{ route('meetings.export.pdf.summary', ['mode' => 'annual']) }}" class="px-3 py-2 rounded-lg bg-gray-700 text-white text-sm font-semibold">Synthèse annuelle PDF</a>
</div>
@endsection
