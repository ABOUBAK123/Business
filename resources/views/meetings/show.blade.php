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
            <div><span class="text-gray-500">Délai fixé:</span> {{ $meeting->processing_deadline?->format('d/m/Y H:i') ?: '—' }}</div>
            <div><span class="text-gray-500">Organisateur:</span> {{ $meeting->organizer?->name }}</div>
            <div><span class="text-gray-500">Rédacteur:</span> {{ $meeting->minutesWriter?->name }}</div>
            <div><span class="text-gray-500">Workflow:</span> <span class="font-semibold">{{ $meeting->workflow_status ?? 'draft' }}</span></div>
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

        <div>
            <h3 class="font-semibold text-gray-800 mb-2">Pièces jointes de la réunion</h3>
            <ul class="text-sm text-gray-700 space-y-1">
                @forelse((array)($meeting->attachments ?? []) as $file)
                    <li>- {{ $file['name'] ?? 'Document' }}</li>
                @empty
                    <li class="text-gray-400">Aucune pièce jointe.</li>
                @endforelse
            </ul>
        </div>

        <div class="border-t border-gray-100 pt-4 space-y-3">
            <h3 class="font-semibold text-gray-800">Compte rendu personnalisable</h3>
            <form method="POST" action="{{ route('meetings.minutes.update', $meeting) }}" class="space-y-2">
                @csrf
                <textarea name="minutes_content" rows="8" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="Rédiger le compte rendu...">{{ old('minutes_content', $meeting->minutes_content ?? $meeting->minutes_template ?? '') }}</textarea>
                <input type="text" name="note" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="Note de version (optionnel)">
                <button class="px-3 py-2 rounded-lg bg-[#2453d6] text-white text-sm font-semibold hover:bg-[#1f47bb]">Enregistrer une version</button>
            </form>
        </div>

        <div class="border-t border-gray-100 pt-4 space-y-3">
            <h3 class="font-semibold text-gray-800">Validation et signature</h3>
            <div class="flex flex-wrap gap-2">
                <form method="POST" action="{{ route('meetings.workflow', $meeting) }}">@csrf<input type="hidden" name="action" value="submit_validation"><button class="px-3 py-1.5 rounded-lg bg-blue-600 text-white text-xs font-semibold">Envoyer en validation</button></form>
                <form method="POST" action="{{ route('meetings.workflow', $meeting) }}">@csrf<input type="hidden" name="action" value="validate"><button class="px-3 py-1.5 rounded-lg bg-emerald-600 text-white text-xs font-semibold">Valider</button></form>
                <form method="POST" action="{{ route('meetings.workflow', $meeting) }}" class="flex items-center gap-2">
                    @csrf
                    <input type="hidden" name="action" value="request_review">
                    <input type="text" name="review_comment" placeholder="Commentaire de relecture" class="border border-gray-300 rounded-lg px-2 py-1 text-xs">
                    <button class="px-3 py-1.5 rounded-lg bg-amber-500 text-white text-xs font-semibold">Demander relecture</button>
                </form>
                <form method="POST" action="{{ route('meetings.workflow', $meeting) }}" id="sign-form">
                    @csrf
                    <input type="hidden" name="action" value="sign_writer">
                    <input type="hidden" name="signature" id="writer-signature-input">
                    <button type="button" onclick="submitWriterSignature()" class="px-3 py-1.5 rounded-lg bg-indigo-600 text-white text-xs font-semibold">Signature électronique du rédacteur</button>
                </form>
                <form method="POST" action="{{ route('meetings.workflow', $meeting) }}">@csrf<input type="hidden" name="action" value="publish"><button class="px-3 py-1.5 rounded-lg bg-purple-600 text-white text-xs font-semibold">Publier et diffuser</button></form>
            </div>

            <div class="rounded-lg border border-gray-200 p-3">
                <p class="text-xs text-gray-500 mb-2">Zone de signature du rédacteur</p>
                <canvas id="writer-signature-pad" width="520" height="120" class="w-full border border-gray-300 rounded bg-white"></canvas>
                <button type="button" onclick="clearWriterSignature()" class="mt-2 text-xs text-gray-600 hover:underline">Effacer la signature</button>
                <p class="text-xs text-gray-500 mt-1">Signé le: {{ $meeting->writer_signed_at?->format('d/m/Y H:i') ?: '—' }}</p>
            </div>

            @if($meeting->review_requested)
                <div class="rounded-lg border border-amber-200 bg-amber-50 text-amber-800 text-sm px-3 py-2">
                    Relecture demandée: {{ $meeting->review_comment ?: 'Aucun commentaire' }}
                </div>
            @endif
        </div>

        <div class="border-t border-gray-100 pt-4">
            <h3 class="font-semibold text-gray-800 mb-2">Historique des versions</h3>
            <ul class="space-y-2 text-sm">
                @forelse($meeting->minutesVersions as $version)
                    <li class="border border-gray-200 rounded-lg px-3 py-2">
                        <div class="font-semibold text-gray-700">Version {{ $version->version_no }} - {{ $version->workflow_status ?: 'draft' }}</div>
                        <div class="text-xs text-gray-500">{{ $version->created_at?->format('d/m/Y H:i') }} par {{ $version->creator?->name ?: 'Utilisateur' }}</div>
                        <div class="text-xs text-gray-600 mt-1">{{ $version->note ?: 'Mise à jour' }}</div>
                    </li>
                @empty
                    <li class="text-gray-400">Aucune version enregistrée.</li>
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

<script>
(function () {
    const canvas = document.getElementById('writer-signature-pad');
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    ctx.lineWidth = 2;
    ctx.lineCap = 'round';
    let drawing = false;

    function getPos(e) {
        const rect = canvas.getBoundingClientRect();
        const clientX = e.touches ? e.touches[0].clientX : e.clientX;
        const clientY = e.touches ? e.touches[0].clientY : e.clientY;
        return { x: clientX - rect.left, y: clientY - rect.top };
    }

    function start(e) {
        drawing = true;
        const p = getPos(e);
        ctx.beginPath();
        ctx.moveTo(p.x, p.y);
    }

    function move(e) {
        if (!drawing) return;
        e.preventDefault();
        const p = getPos(e);
        ctx.lineTo(p.x, p.y);
        ctx.stroke();
    }

    function end() {
        drawing = false;
    }

    canvas.addEventListener('mousedown', start);
    canvas.addEventListener('mousemove', move);
    window.addEventListener('mouseup', end);
    canvas.addEventListener('touchstart', start, { passive: true });
    canvas.addEventListener('touchmove', move, { passive: false });
    canvas.addEventListener('touchend', end);

    window.clearWriterSignature = function () {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
    };

    window.submitWriterSignature = function () {
        const data = canvas.toDataURL('image/png');
        document.getElementById('writer-signature-input').value = data;
        document.getElementById('sign-form').submit();
    };
})();
</script>
@endsection
