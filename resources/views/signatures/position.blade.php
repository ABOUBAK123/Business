@extends('layouts.app')
@section('title', 'Positionner la signature')
@section('page-title', 'Positionner la zone de signature')
@section('content')

@php
    $doc      = $signatureRequest->document;
    $fileUrl  = $doc && $doc->file_path ? asset($doc->file_path) : null;
    $isPdf    = $doc && str_contains(strtolower($doc->mime_type ?? $doc->file_path ?? ''), 'pdf');
    $requester = $signatureRequest->requester;
    $firstZone = !empty($templateZones) ? $templateZones[0] : null;
@endphp

{{-- Flash messages --}}
@if(session('success'))
    <div class="mb-4 px-4 py-3 bg-green-50 border border-green-200 text-green-700 rounded-xl text-sm font-medium flex items-center gap-2">
        <i class="fas fa-check-circle text-green-500"></i> {{ session('success') }}
    </div>
@endif

{{-- En-tête --}}
<div class="flex items-center gap-4 mb-6">
    <a href="{{ route('signatures.index') }}"
        class="h-9 w-9 flex items-center justify-center rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-100 hover:text-gray-800 transition">
        <i class="fas fa-arrow-left text-sm"></i>
    </a>
    <div>
        <h1 class="text-xl font-bold text-gray-900">Positionner la zone de signature</h1>
        <p class="text-sm text-gray-500 mt-0.5">
            Document : <span class="font-semibold text-gray-700">{{ $doc->title ?? 'Document' }}</span>
            @if($requester)
                &nbsp;·&nbsp; Demandé par <span class="font-semibold text-gray-700">{{ $requester->name ?? $requester->email }}</span>
            @endif
        </p>
    </div>
</div>

{{-- Message de la demande --}}
@if($signatureRequest->message)
    <div class="mb-5 px-4 py-3 bg-blue-50 border border-blue-200 text-blue-800 rounded-xl text-sm flex items-start gap-3">
        <i class="fas fa-envelope text-blue-400 mt-0.5 flex-shrink-0"></i>
        <div>
            <p class="font-semibold text-blue-700 mb-0.5">Message du demandeur :</p>
            <p>{{ $signatureRequest->message }}</p>
        </div>
    </div>
@endif

{{-- Layout principal --}}
<div class="flex gap-5 items-start">

    {{-- Panneau gauche : document + zone de sélection --}}
    <div class="flex-1 min-w-0">
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">

            {{-- Barre outils PDF --}}
            <div class="flex items-center gap-3 px-4 py-3 border-b border-gray-100 bg-gray-50">
                @if($isPdf)
                    <button onclick="prevPage()"
                        class="h-8 w-8 flex items-center justify-center rounded-lg border border-gray-200 text-gray-600 hover:bg-white transition disabled:opacity-40" id="btn-prev-page">
                        <i class="fas fa-chevron-left text-xs"></i>
                    </button>
                    <span class="text-sm text-gray-700">
                        Page <span id="page-num" class="font-bold text-gray-900">1</span>/<span id="page-count">—</span>
                    </span>
                    <button onclick="nextPage()"
                        class="h-8 w-8 flex items-center justify-center rounded-lg border border-gray-200 text-gray-600 hover:bg-white transition disabled:opacity-40" id="btn-next-page">
                        <i class="fas fa-chevron-right text-xs"></i>
                    </button>
                    <div class="flex-1"></div>
                    <button onclick="zoomIn()"
                        class="h-8 w-8 flex items-center justify-center rounded-lg border border-gray-200 text-gray-600 hover:bg-white transition" title="Zoom +">
                        <i class="fas fa-search-plus text-xs"></i>
                    </button>
                    <button onclick="zoomOut()"
                        class="h-8 w-8 flex items-center justify-center rounded-lg border border-gray-200 text-gray-600 hover:bg-white transition" title="Zoom -">
                        <i class="fas fa-search-minus text-xs"></i>
                    </button>
                    <button onclick="resetZone()"
                        class="px-3 py-1.5 rounded-lg bg-red-50 border border-red-200 text-red-600 text-xs font-medium hover:bg-red-100 transition flex items-center gap-1">
                        <i class="fas fa-times text-xs"></i> Effacer la zone
                    </button>
                @else
                    <span class="text-sm text-gray-500 italic">Aperçu non disponible pour ce type de fichier</span>
                @endif
            </div>

            {{-- Zone de rendu du document --}}
            <div class="relative bg-gray-100 overflow-auto" style="min-height:600px;max-height:80vh;" id="pdf-container">

                @if($isPdf && $fileUrl)
                    {{-- Canvas PDF.js --}}
                    <div class="relative inline-block mx-auto" id="pdf-wrapper" style="display:block;">
                        <canvas id="pdf-canvas" class="block shadow-md mx-auto"></canvas>
                        {{-- Overlay pour la zone de signature --}}
                        <canvas id="zone-canvas" class="absolute top-0 left-0 cursor-crosshair" style="pointer-events:all;"
                            onmousedown="startDrag(event)"
                            onmousemove="onDrag(event)"
                            onmouseup="endDrag(event)"
                            ontouchstart="startDragTouch(event)"
                            ontouchmove="onDragTouch(event)"
                            ontouchend="endDragTouch(event)">
                        </canvas>
                    </div>

                @elseif($fileUrl)
                    {{-- Image ou autre --}}
                    <div class="relative inline-block mx-auto" id="pdf-wrapper">
                        <img src="{{ $fileUrl }}" alt="{{ $doc->title }}"
                            class="block max-w-full shadow-md mx-auto" id="img-preview"
                            onload="initImageZone(this)">
                        <canvas id="zone-canvas" class="absolute top-0 left-0 cursor-crosshair"
                            onmousedown="startDrag(event)"
                            onmousemove="onDrag(event)"
                            onmouseup="endDrag(event)">
                        </canvas>
                    </div>

                @else
                    <div class="flex flex-col items-center justify-center h-80 text-gray-400">
                        <i class="fas fa-file-alt text-6xl mb-4 text-gray-200"></i>
                        <p class="text-lg font-medium text-gray-400">Aperçu non disponible</p>
                        <p class="text-sm">Le fichier n'est pas accessible ou son chemin est invalide.</p>
                        <p class="text-xs mt-2 text-gray-300">Vous pouvez quand même positionner la zone à droite.</p>
                    </div>
                @endif
            </div>

            {{-- Légende --}}
            <div class="px-4 py-3 border-t border-gray-100 bg-gray-50 flex items-center gap-4 text-xs text-gray-500">
                <span class="flex items-center gap-1.5">
                    <span class="inline-block w-4 h-4 border-2 border-dashed border-blue-500 bg-blue-100/50 rounded-sm"></span>
                    Zone de signature (cliquez et faites glisser pour dessiner)
                </span>
                <span>·</span>
                <span>La zone indique où votre signature apparaîtra sur le document final</span>
            </div>
        </div>
    </div>

    {{-- Panneau droit : formulaire de signature --}}
    <div class="w-80 flex-shrink-0">
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden sticky top-4">
            <div class="px-5 py-4 border-b border-gray-100 bg-gradient-to-r from-blue-600 to-blue-700">
                <h2 class="text-base font-bold text-white flex items-center gap-2">
                    <i class="fas fa-pen-nib"></i> Apposer ma signature
                </h2>
                <p class="text-xs text-blue-200 mt-0.5">Dessinez votre signature ou tapez vos initiales</p>
            </div>

            <form id="sign-form" action="{{ route('signatures.sign', $signatureRequest) }}" method="POST">
                @csrf
                <input type="hidden" name="zone_page"   id="inp-zone-page"   value="1">
                <input type="hidden" name="zone_x"      id="inp-zone-x"      value="{{ $firstZone ? $firstZone['x'] : '' }}">
                <input type="hidden" name="zone_y"      id="inp-zone-y"      value="{{ $firstZone ? $firstZone['y'] : '' }}">
                <input type="hidden" name="zone_width"  id="inp-zone-width"  value="{{ $firstZone ? ($firstZone['w'] ?? 22) : '' }}">
                <input type="hidden" name="zone_height" id="inp-zone-height" value="{{ $firstZone ? ($firstZone['h'] ?? 18) : '' }}">
                <input type="hidden" name="zone_label"  id="inp-zone-label"  value="{{ $firstZone ? ($firstZone['label'] ?? 'Signature') : '' }}">
                <input type="hidden" name="signature"   id="inp-signature"   value="">

                {{-- Zones du template pré-définies --}}
                <script>window._templateZones = @json($templateZones ?? []);</script>

                <div class="px-5 py-4 space-y-4">

                    @if($firstZone)
                    {{-- Bandeau info zone pré-définie --}}
                    <div class="p-3 bg-emerald-50 border border-emerald-200 rounded-xl text-xs text-emerald-800 flex items-start gap-2">
                        <i class="fas fa-stamp text-emerald-500 mt-0.5 flex-shrink-0"></i>
                        <div>
                            <p class="font-semibold">Zone de signature pré-positionnée</p>
                            <p class="text-emerald-600 mt-0.5">L'administrateur a déjà défini la position de votre signature sur ce document. Vous pouvez dessiner ou taper votre signature ci-dessous, puis cliquer sur <strong>Signer</strong>.</p>
                        </div>
                    </div>
                    @endif

                    {{-- Zone info --}}
                    <div id="zone-info" class="{{ $firstZone ? '' : 'hidden' }} p-3 bg-blue-50 border border-blue-200 rounded-xl text-xs text-blue-800 space-y-1">
                        <p class="font-semibold flex items-center gap-1.5">
                            <i class="fas fa-check-circle text-blue-500"></i> Zone {{ $firstZone ? 'pré-définie' : 'sélectionnée' }}
                        </p>
                        <p id="zone-info-text">{{ $firstZone ? 'Position X: ' . round($firstZone['x'], 1) . '% · Y: ' . round($firstZone['y'], 1) . '% · Taille: ' . round($firstZone['w'] ?? 22, 1) . '% × ' . round($firstZone['h'] ?? 18, 1) . '%' : '' }}</p>
                    </div>
                    <div id="zone-warn" class="{{ $firstZone ? 'hidden' : '' }} p-3 bg-amber-50 border border-amber-200 rounded-xl text-xs text-amber-700 flex items-start gap-1.5">
                        <i class="fas fa-mouse-pointer text-amber-500 flex-shrink-0 mt-0.5"></i>
                        <p>Dessinez d'abord la zone sur le document en cliquant et faisant glisser.</p>
                    </div>

                    {{-- Signature pad --}}
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <label class="text-sm font-semibold text-gray-700">Votre signature</label>
                            <div class="flex gap-1.5">
                                <button type="button" onclick="setSignMode('draw')"
                                    id="btn-mode-draw"
                                    class="px-2.5 py-1 text-xs rounded-lg border font-medium transition bg-[#2453d6] text-white border-[#2453d6]">
                                    <i class="fas fa-pen mr-1"></i>Dessiner
                                </button>
                                <button type="button" onclick="setSignMode('type')"
                                    id="btn-mode-type"
                                    class="px-2.5 py-1 text-xs rounded-lg border font-medium transition bg-white text-gray-600 border-gray-300 hover:border-gray-400">
                                    <i class="fas fa-keyboard mr-1"></i>Texte
                                </button>
                            </div>
                        </div>

                        {{-- Mode dessin --}}
                        <div id="panel-draw" class="border border-gray-200 rounded-xl overflow-hidden bg-white">
                            <canvas id="sig-canvas" width="288" height="100"
                                class="block w-full cursor-crosshair bg-gray-50"
                                onmousedown="sigStart(event)"
                                onmousemove="sigDraw(event)"
                                onmouseup="sigStop()"
                                onmouseleave="sigStop()"
                                ontouchstart="sigStartT(event)"
                                ontouchmove="sigDrawT(event)"
                                ontouchend="sigStop()">
                            </canvas>
                            <div class="flex justify-between items-center px-3 py-2 border-t border-gray-100 bg-gray-50">
                                <span class="text-xs text-gray-400">Tracez votre signature</span>
                                <button type="button" onclick="clearSig()" class="text-xs text-red-500 hover:text-red-700 font-medium transition">
                                    <i class="fas fa-eraser mr-1"></i>Effacer
                                </button>
                            </div>
                        </div>

                        {{-- Mode texte --}}
                        <div id="panel-type" class="hidden space-y-2">
                            <input type="text" id="sig-text-input"
                                placeholder="Votre nom complet ou initiales"
                                oninput="updateTextSig(this.value)"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#2453d6]">
                            <div class="border border-gray-200 rounded-xl overflow-hidden">
                                <canvas id="sig-text-canvas" width="288" height="80" class="block w-full bg-gray-50"></canvas>
                                <div class="px-3 py-1.5 border-t border-gray-100 bg-gray-50">
                                    <p class="text-xs text-gray-400">Style de signature</p>
                                    <div class="flex gap-2 mt-1.5">
                                        <button type="button" onclick="setSigFont('Dancing Script')" class="sig-font-btn px-2 py-1 text-xs border border-[#2453d6] bg-blue-50 text-blue-700 rounded font-medium" style="font-family:'Dancing Script',cursive">Cursive</button>
                                        <button type="button" onclick="setSigFont('Pacifico')" class="sig-font-btn px-2 py-1 text-xs border border-gray-200 text-gray-600 rounded" style="font-family:'Pacifico',cursive">Stylé</button>
                                        <button type="button" onclick="setSigFont('Arial')" class="sig-font-btn px-2 py-1 text-xs border border-gray-200 text-gray-600 rounded">Normal</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Motif --}}
                    <div class="space-y-1.5">
                        <label class="block text-sm font-semibold text-gray-700">
                            Motif <span class="text-gray-400 font-normal text-xs">(optionnel)</span>
                        </label>
                        <input type="text" name="reason"
                            placeholder="Ex: Bon pour accord, Approuvé..."
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#2453d6]">
                    </div>

                    {{-- Label de zone --}}
                    <div class="space-y-1.5">
                        <label class="block text-sm font-semibold text-gray-700">
                            Texte de la zone <span class="text-gray-400 font-normal text-xs">(optionnel)</span>
                        </label>
                        <input type="text" id="f-zone-label"
                            placeholder="Ex: Signature du directeur"
                            oninput="document.getElementById('inp-zone-label').value=this.value; updateZoneOverlay();"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#2453d6]">
                    </div>

                    {{-- Erreurs --}}
                    @if($errors->any())
                        <div class="p-3 bg-red-50 border border-red-200 rounded-xl text-xs text-red-700 space-y-1">
                            @foreach($errors->all() as $e)
                                <p><i class="fas fa-exclamation-circle mr-1"></i>{{ $e }}</p>
                            @endforeach
                        </div>
                    @endif

                    {{-- Bouton soumettre --}}
                    <button type="button" onclick="submitSign()"
                        class="w-full py-3 bg-[#2453d6] hover:bg-[#1f47bb] text-white font-bold rounded-xl flex items-center justify-center gap-2 transition shadow-sm disabled:opacity-50">
                        <i class="fas fa-check-circle"></i> Apposer la signature
                    </button>

                    <a href="{{ route('signatures.index') }}"
                        class="block w-full py-2.5 text-center text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-xl text-sm font-medium transition">
                        Annuler
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- PDF.js via CDN --}}
<script src="{{ asset('vendor/pdfjs/pdf.min.js') }}"></script>

<script>
// ══════════════════════════════════════════════════════════
// CONFIG
// ══════════════════════════════════════════════════════════
const FILE_URL  = @json($fileUrl);
const IS_PDF    = @json($isPdf);
const SIGN_ROUTE = '{{ route('signatures.sign', $signatureRequest) }}';
const CSRF      = '{{ csrf_token() }}';

// ══════════════════════════════════════════════════════════
// PDF.JS — RENDU DU DOCUMENT
// ══════════════════════════════════════════════════════════
let pdfDoc   = null;
let pageNum  = 1;
let pageCount = 0;
let scale    = 1.4;
const pdfCanvas  = document.getElementById('pdf-canvas');
const zoneCanvas = document.getElementById('zone-canvas');

if (IS_PDF && FILE_URL) {
    pdfjsLib.GlobalWorkerOptions.workerSrc =
        '{{ asset("vendor/pdfjs/pdf.worker.min.js") }}';

    pdfjsLib.getDocument(FILE_URL).promise.then(pdf => {
        pdfDoc   = pdf;
        pageCount = pdf.numPages;
        document.getElementById('page-count').textContent = pageCount;
        renderPage(1);
    }).catch(err => {
        console.error('PDF.js error:', err);
        document.getElementById('pdf-container').innerHTML =
            '<div class="flex flex-col items-center justify-center h-80 text-gray-400">' +
            '<i class="fas fa-exclamation-triangle text-5xl mb-4 text-amber-300"></i>' +
            '<p class="text-base font-medium">Impossible de charger le PDF</p>' +
            '<p class="text-sm mt-1">Vérifiez que le fichier est accessible.</p>' +
            '</div>';
    });
}

function renderPage(n) {
    pageNum = n;
    document.getElementById('page-num').textContent = n;
    document.getElementById('inp-zone-page').value  = n;

    pdfDoc.getPage(n).then(page => {
        const viewport = page.getViewport({ scale });
        pdfCanvas.width  = viewport.width;
        pdfCanvas.height = viewport.height;
        zoneCanvas.width  = viewport.width;
        zoneCanvas.height = viewport.height;

        const ctx = pdfCanvas.getContext('2d');
        page.render({ canvasContext: ctx, viewport }).promise.then(() => {
            redrawZone();
        });
    });
}

function prevPage() { if (pageNum > 1) { resetZone(); renderPage(pageNum - 1); } }
function nextPage() { if (pdfDoc && pageNum < pageCount) { resetZone(); renderPage(pageNum + 1); } }
function zoomIn()  { scale = Math.min(scale + 0.2, 3.0); resetZone(); renderPage(pageNum); }
function zoomOut() { scale = Math.max(scale - 0.2, 0.5); resetZone(); renderPage(pageNum); }

// Pour image statique
function initImageZone(img) {
    const zc = document.getElementById('zone-canvas');
    zc.width  = img.naturalWidth;
    zc.height = img.naturalHeight;
    zc.style.width  = img.offsetWidth  + 'px';
    zc.style.height = img.offsetHeight + 'px';
}

// ══════════════════════════════════════════════════════════
// SÉLECTION DE ZONE PAR DRAG
// ══════════════════════════════════════════════════════════
let isDragging = false;
let dragStart  = { x: 0, y: 0 };
let zone       = null;  // { x, y, w, h } en pixels sur le canvas

function getCanvasPos(e, canvas) {
    const r = canvas.getBoundingClientRect();
    const scaleX = canvas.width  / r.width;
    const scaleY = canvas.height / r.height;
    return {
        x: (e.clientX - r.left) * scaleX,
        y: (e.clientY - r.top)  * scaleY,
    };
}

function startDrag(e) {
    if (e.button !== 0) return;
    const zc = document.getElementById('zone-canvas');
    isDragging = true;
    dragStart  = getCanvasPos(e, zc);
    zone       = null;
}
function onDrag(e) {
    if (!isDragging) return;
    const zc = document.getElementById('zone-canvas');
    const pos = getCanvasPos(e, zc);
    zone = {
        x: Math.min(dragStart.x, pos.x),
        y: Math.min(dragStart.y, pos.y),
        w: Math.abs(pos.x - dragStart.x),
        h: Math.abs(pos.y - dragStart.y),
    };
    redrawZone();
}
function endDrag(e) {
    if (!isDragging) return;
    isDragging = false;
    if (zone && zone.w > 10 && zone.h > 10) {
        saveZoneInputs();
    } else {
        zone = null;
        redrawZone();
    }
}

// Touch support
function getTouchPos(e, canvas) {
    const r = canvas.getBoundingClientRect();
    const t = e.touches[0];
    const scaleX = canvas.width  / r.width;
    const scaleY = canvas.height / r.height;
    return { x: (t.clientX - r.left) * scaleX, y: (t.clientY - r.top) * scaleY };
}
function startDragTouch(e) { e.preventDefault(); const zc = document.getElementById('zone-canvas'); isDragging = true; dragStart = getTouchPos(e, zc); zone = null; }
function onDragTouch(e)    { e.preventDefault(); if (!isDragging) return; const zc = document.getElementById('zone-canvas'); const pos = getTouchPos(e, zc); zone = { x: Math.min(dragStart.x, pos.x), y: Math.min(dragStart.y, pos.y), w: Math.abs(pos.x - dragStart.x), h: Math.abs(pos.y - dragStart.y) }; redrawZone(); }
function endDragTouch(e)   { e.preventDefault(); isDragging = false; if (zone && zone.w > 10 && zone.h > 10) saveZoneInputs(); else { zone = null; redrawZone(); } }

function redrawZone() {
    const zc  = document.getElementById('zone-canvas');
    const ctx = zc.getContext('2d');
    ctx.clearRect(0, 0, zc.width, zc.height);
    if (!zone) return;

    // Rectangle de zone
    ctx.strokeStyle = '#2453d6';
    ctx.lineWidth   = 2;
    ctx.setLineDash([6, 3]);
    ctx.fillStyle   = 'rgba(36, 83, 214, 0.08)';
    ctx.fillRect(zone.x, zone.y, zone.w, zone.h);
    ctx.strokeRect(zone.x, zone.y, zone.w, zone.h);

    // Poignées de coin
    ctx.setLineDash([]);
    ctx.fillStyle = '#2453d6';
    [[zone.x, zone.y],[zone.x+zone.w, zone.y],[zone.x, zone.y+zone.h],[zone.x+zone.w, zone.y+zone.h]].forEach(([cx,cy]) => {
        ctx.beginPath(); ctx.arc(cx, cy, 5, 0, Math.PI*2); ctx.fill();
    });

    // Label
    const label = document.getElementById('f-zone-label')?.value || 'Zone de signature';
    ctx.setLineDash([]);
    ctx.fillStyle = '#2453d6';
    ctx.font = 'bold 11px sans-serif';
    ctx.fillText(label, zone.x + 6, zone.y + 16);
}

function saveZoneInputs() {
    if (!zone) return;
    const zc = document.getElementById('zone-canvas');
    const pct = {
        x: (zone.x / zc.width  * 100).toFixed(4),
        y: (zone.y / zc.height * 100).toFixed(4),
        w: (zone.w / zc.width  * 100).toFixed(4),
        h: (zone.h / zc.height * 100).toFixed(4),
    };
    document.getElementById('inp-zone-x').value      = pct.x;
    document.getElementById('inp-zone-y').value      = pct.y;
    document.getElementById('inp-zone-width').value  = pct.w;
    document.getElementById('inp-zone-height').value = pct.h;

    const info = `Page ${pageNum} · X: ${pct.x}% · Y: ${pct.y}% · L: ${pct.w}% · H: ${pct.h}%`;
    document.getElementById('zone-info-text').textContent = info;
    document.getElementById('zone-info').classList.remove('hidden');
    document.getElementById('zone-warn').classList.add('hidden');
}

function updateZoneOverlay() { redrawZone(); }

function resetZone() {
    zone = null;
    const zc = document.getElementById('zone-canvas');
    if (zc) zc.getContext('2d').clearRect(0, 0, zc.width, zc.height);
    ['inp-zone-x','inp-zone-y','inp-zone-width','inp-zone-height'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.value = '';
    });
    document.getElementById('zone-info').classList.add('hidden');
    document.getElementById('zone-warn').classList.remove('hidden');
}

// ══════════════════════════════════════════════════════════
// PAD DE SIGNATURE
// ══════════════════════════════════════════════════════════
let signMode  = 'draw';
let isDrawing = false;
let sigFont   = 'Dancing Script';

function setSignMode(mode) {
    signMode = mode;
    document.getElementById('panel-draw').classList.toggle('hidden', mode !== 'draw');
    document.getElementById('panel-type').classList.toggle('hidden', mode !== 'type');
    document.getElementById('btn-mode-draw').className = mode === 'draw'
        ? 'px-2.5 py-1 text-xs rounded-lg border font-medium transition bg-[#2453d6] text-white border-[#2453d6]'
        : 'px-2.5 py-1 text-xs rounded-lg border font-medium transition bg-white text-gray-600 border-gray-300 hover:border-gray-400';
    document.getElementById('btn-mode-type').className = mode === 'type'
        ? 'px-2.5 py-1 text-xs rounded-lg border font-medium transition bg-[#2453d6] text-white border-[#2453d6]'
        : 'px-2.5 py-1 text-xs rounded-lg border font-medium transition bg-white text-gray-600 border-gray-300 hover:border-gray-400';
}

// ── Mode dessin ────────────────────────────────────────────
const sigCanvas = document.getElementById('sig-canvas');
const sigCtx    = sigCanvas ? sigCanvas.getContext('2d') : null;
let lastPt = null;

function sigStart(e) {
    isDrawing = true;
    const p = getSigPos(e, sigCanvas);
    lastPt = p;
    if (sigCtx) { sigCtx.beginPath(); sigCtx.moveTo(p.x, p.y); }
}
function sigDraw(e) {
    if (!isDrawing || !sigCtx) return;
    const p = getSigPos(e, sigCanvas);
    sigCtx.strokeStyle = '#1a1a2e';
    sigCtx.lineWidth   = 2;
    sigCtx.lineCap     = 'round';
    sigCtx.lineJoin    = 'round';
    sigCtx.lineTo(p.x, p.y);
    sigCtx.stroke();
    lastPt = p;
}
function sigStop() { isDrawing = false; }

function sigStartT(e) { e.preventDefault(); sigStart({ clientX: e.touches[0].clientX, clientY: e.touches[0].clientY }); }
function sigDrawT(e)  { e.preventDefault(); sigDraw({ clientX: e.touches[0].clientX, clientY: e.touches[0].clientY }); }

function getSigPos(e, canvas) {
    const r = canvas.getBoundingClientRect();
    const sx = canvas.width  / r.width;
    const sy = canvas.height / r.height;
    return { x: (e.clientX - r.left) * sx, y: (e.clientY - r.top) * sy };
}

function clearSig() {
    if (sigCtx) sigCtx.clearRect(0, 0, sigCanvas.width, sigCanvas.height);
}

// ── Mode texte ─────────────────────────────────────────────
const sigTextCanvas = document.getElementById('sig-text-canvas');
const sigTextCtx    = sigTextCanvas ? sigTextCanvas.getContext('2d') : null;

function updateTextSig(text) {
    if (!sigTextCtx) return;
    sigTextCtx.clearRect(0, 0, sigTextCanvas.width, sigTextCanvas.height);
    if (!text.trim()) return;

    // Fond transparent, texte signature
    sigTextCtx.fillStyle = '#1a1a2e';
    const fontSize = Math.min(40, Math.floor(sigTextCanvas.width / (text.length * 0.55)));
    sigTextCtx.font = `${Math.max(fontSize, 18)}px '${sigFont}', cursive`;
    sigTextCtx.textAlign = 'center';
    sigTextCtx.textBaseline = 'middle';
    sigTextCtx.fillText(text, sigTextCanvas.width / 2, sigTextCanvas.height / 2);
}

function setSigFont(font) {
    sigFont = font;
    document.querySelectorAll('.sig-font-btn').forEach(btn => {
        btn.className = 'sig-font-btn px-2 py-1 text-xs border border-gray-200 text-gray-600 rounded';
    });
    event.currentTarget.className = 'sig-font-btn px-2 py-1 text-xs border border-[#2453d6] bg-blue-50 text-blue-700 rounded font-medium';
    updateTextSig(document.getElementById('sig-text-input').value);
}

// ── Récupérer la signature en base64 ──────────────────────
function getSignatureDataUrl() {
    if (signMode === 'draw') {
        if (!sigCanvas) return null;
        // Vérifier si le canvas est vide
        const imgData = sigCtx.getImageData(0, 0, sigCanvas.width, sigCanvas.height);
        const hasPixels = Array.from(imgData.data).some((v, i) => i % 4 === 3 && v > 0);
        return hasPixels ? sigCanvas.toDataURL('image/png') : null;
    } else {
        if (!sigTextCanvas) return null;
        const text = document.getElementById('sig-text-input').value.trim();
        if (!text) return null;
        return sigTextCanvas.toDataURL('image/png');
    }
}

// ══════════════════════════════════════════════════════════
// SOUMISSION
// ══════════════════════════════════════════════════════════
function submitSign() {
    const zoneX = document.getElementById('inp-zone-x').value;
    const zoneY = document.getElementById('inp-zone-y').value;
    const zoneW = document.getElementById('inp-zone-width').value;
    const zoneH = document.getElementById('inp-zone-height').value;

    if (!zoneX || !zoneY || !zoneW || !zoneH) {
        showLocalError('Veuillez d\'abord dessiner la zone de signature sur le document.');
        return;
    }

    const sigData = getSignatureDataUrl();
    if (!sigData) {
        showLocalError('Veuillez dessiner ou saisir votre signature.');
        return;
    }

    document.getElementById('inp-signature').value  = sigData;
    document.getElementById('inp-zone-label').value = document.getElementById('f-zone-label').value;

    // Désactiver le bouton
    const btn = document.querySelector('[onclick="submitSign()"]');
    if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Envoi en cours...'; }

    document.getElementById('sign-form').submit();
}

function showLocalError(msg) {
    let el = document.getElementById('local-error');
    if (!el) {
        el = document.createElement('div');
        el.id = 'local-error';
        el.className = 'p-3 bg-red-50 border border-red-200 text-red-700 rounded-xl text-sm flex items-center gap-2';
        document.querySelector('#sign-form .px-5.py-4').prepend(el);
    }
    el.innerHTML = `<i class="fas fa-exclamation-circle text-red-500"></i> ${msg}`;
    el.style.display = 'flex';
    setTimeout(() => { if (el) el.style.display = 'none'; }, 5000);
}
</script>
@endsection
