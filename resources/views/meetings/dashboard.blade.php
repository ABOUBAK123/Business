@extends('layouts.app')
@section('title', 'Présences réunion')
@section('page-title', 'Présences')
@section('page-subtitle', $meeting->title)

@section('content')
@include('meetings._nav')
<div class="flex items-center justify-between mb-4 gap-3">
    <div class="flex items-center gap-3">
        @if(!empty($branding['logo_url']))
            <img src="{{ $branding['logo_url'] }}" alt="Logo administration" class="h-12 w-12 object-contain rounded bg-white border border-gray-200 p-1">
        @endif
        <div>
            @if(!empty($branding['tutelle_entity_name']))
                <div class="text-xs uppercase tracking-wide text-gray-500">{{ $branding['tutelle_entity_name'] }}</div>
            @endif
            @if(!empty($branding['tutelle_entity_code']))
                <div class="text-sm font-bold text-gray-900">{{ $branding['tutelle_entity_code'] }}</div>
            @endif
        <div class="text-sm text-gray-700">Participants attendus: <strong>{{ $meeting->participants->count() }}</strong></div>
        <div class="text-sm text-gray-700">Présents: <strong>{{ $meeting->attendances->count() }}</strong></div>
        </div>
    </div>
    <a href="#" onclick="document.getElementById('download-modal').classList.remove('hidden');return false;"
       class="flex items-center gap-2 px-4 py-2 rounded-lg bg-[#2453d6] text-white text-sm font-semibold hover:bg-[#1f47bb]">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1M12 12v6m0 0l-3-3m3 3l3-3M12 3v9"/>
        </svg>
        Télécharger la liste
    </a>
</div>

{{-- Modal choix format --}}
<div id="download-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40">
    <div class="bg-white rounded-2xl shadow-xl p-6 w-full max-w-sm mx-4">
        <h3 class="text-base font-bold text-gray-800 mb-4">Télécharger la liste de présence</h3>
        <form method="GET" action="{{ route('meetings.attendance.download', $meeting) }}" class="space-y-4">
            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-2">Format</label>
                <div class="flex gap-3">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="format" value="pdf" checked class="accent-[#2453d6]"> PDF
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="format" value="csv" class="accent-[#2453d6]"> CSV (Excel)
                    </label>
                </div>
            </div>
            <div id="orientation-group">
                <label class="block text-xs font-semibold text-gray-700 mb-2">Orientation de la page (PDF)</label>
                <div class="flex gap-3">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="orientation" value="portrait" checked class="accent-[#2453d6]">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-6 text-gray-500" fill="none" viewBox="0 0 16 22" stroke="currentColor"><rect x="1" y="1" width="14" height="20" rx="1" stroke-width="1.5"/></svg>
                        Portrait
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="orientation" value="landscape" class="accent-[#2453d6]">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-4 text-gray-500" fill="none" viewBox="0 0 22 16" stroke="currentColor"><rect x="1" y="1" width="20" height="14" rx="1" stroke-width="1.5"/></svg>
                        Paysage
                    </label>
                </div>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="flex-1 px-4 py-2 rounded-lg bg-[#2453d6] text-white text-sm font-semibold hover:bg-[#1f47bb]">Télécharger</button>
                <button type="button" onclick="document.getElementById('download-modal').classList.add('hidden')" class="flex-1 px-4 py-2 rounded-lg border border-gray-300 text-gray-700 text-sm font-semibold hover:bg-gray-50">Annuler</button>
            </div>
        </form>
    </div>
</div>

<script>
document.querySelectorAll('input[name="format"]').forEach(function(r) {
    r.addEventListener('change', function() {
        document.getElementById('orientation-group').style.display = this.value === 'pdf' ? '' : 'none';
    });
});
</script>

<div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
                <th class="text-left px-4 py-3 font-semibold text-gray-700">Nom</th>
                <th class="text-left px-4 py-3 font-semibold text-gray-700">Identifiant</th>
                <th class="text-left px-4 py-3 font-semibold text-gray-700">Email</th>
                <th class="text-left px-4 py-3 font-semibold text-gray-700">Signature</th>
                <th class="text-left px-4 py-3 font-semibold text-gray-700">Heure</th>
            </tr>
        </thead>
        <tbody>
            @forelse($meeting->attendances()->latest('signed_at')->get() as $a)
            <tr class="border-b border-gray-100">
                <td class="px-4 py-3 text-gray-800">{{ $a->full_name }}</td>
                <td class="px-4 py-3 text-gray-600">{{ filter_var($a->identifier, FILTER_VALIDATE_EMAIL) ? '' : $a->identifier }}</td>
                <td class="px-4 py-3 text-gray-600">{{ $a->email }}</td>
                <td class="px-4 py-3 text-gray-600">
                    @if(!empty($a->signature_path))
                        <img src="{{ $a->signature_path }}" alt="Signature de {{ $a->full_name }}" class="h-12 w-28 object-contain rounded border border-gray-200 bg-white p-1">
                    @else
                        <span class="text-gray-400">Non signee</span>
                    @endif
                </td>
                <td class="px-4 py-3 text-gray-600">{{ $a->signed_at?->format('d/m/Y H:i:s') }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="px-4 py-8 text-center text-gray-400">Aucune présence enregistrée.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
