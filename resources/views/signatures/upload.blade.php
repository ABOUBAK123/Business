@extends('layouts.app')
@section('title', 'Signer un document')
@section('page-title', 'Signer un document depuis votre ordinateur')
@section('content')



{{-- En-tête --}}
<div class="flex items-center gap-4 mb-6">
    <a href="{{ route('signatures.index') }}"
        class="h-9 w-9 flex items-center justify-center rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-100 hover:text-gray-800 transition">
        <i class="fas fa-arrow-left text-sm"></i>
    </a>
    <div>
        <h1 class="text-xl font-bold text-gray-900">Signer un document depuis votre ordinateur</h1>
        <p class="text-sm text-gray-500 mt-0.5">Uploadez un PDF, positionnez la zone de signature, puis apposez votre signature.</p>
    </div>
</div>

@if($errors->any())
    <div class="mb-5 px-4 py-3 bg-red-50 border border-red-200 text-red-700 rounded-xl text-sm space-y-1">
        @foreach($errors->all() as $e)
            <p class="flex items-center gap-2"><i class="fas fa-exclamation-circle text-red-400"></i>{{ $e }}</p>
        @endforeach
    </div>
@endif

{{-- ───────────────────────────────────────────────────────── --}}
{{-- ÉTAPE 1 : UPLOAD (visible si aucun fichier sélectionné) --}}
{{-- ───────────────────────────────────────────────────────── --}}
<div id="step-upload" class="max-w-2xl mx-auto">
    <div class="bg-white rounded-2xl border-2 border-dashed border-gray-300 hover:border-[#2453d6] transition p-12 text-center cursor-pointer"
        id="drop-area"
        onclick="document.getElementById('file-input').click()"
        ondragover="dragOver(event)"
        ondragleave="dragLeave(event)"
        ondrop="dropFile(event)">
        <input type="file" id="file-input" accept=".pdf,application/pdf" class="hidden" onchange="fileSelected(this.files[0])">
        <div id="upload-icon" class="w-20 h-20 rounded-full bg-blue-50 flex items-center justify-center mx-auto mb-5">
            <i class="fas fa-file-pdf text-4xl text-[#2453d6]"></i>
        </div>
        <h2 class="text-lg font-bold text-gray-800 mb-2">Glissez votre PDF ici ou cliquez pour parcourir</h2>
        <p class="text-sm text-gray-500 mb-4">Formats acceptés : PDF uniquement · Taille maximale : 20 Mo</p>
        <div class="inline-flex items-center gap-2 bg-[#2453d6] hover:bg-[#1f47bb] text-white px-6 py-2.5 rounded-xl text-sm font-semibold transition shadow-sm">
            <i class="fas fa-upload"></i> Choisir un fichier PDF
        </div>
        <p id="upload-filename" class="mt-4 text-sm font-medium text-green-700 hidden"></p>
    </div>
    <div id="upload-progress" class="hidden mt-4">
        <div class="flex items-center gap-3 px-4 py-3 bg-blue-50 border border-blue-200 rounded-xl text-sm text-blue-800">
            <i class="fas fa-spinner fa-spin text-blue-500"></i>
            <span>Chargement du PDF en cours…</span>
        </div>
    </div>
</div>

{{-- ───────────────────────────────────────────────────────── --}}
{{-- ÉTAPE 2 : POSITIONNEMENT + SIGNATURE (caché par défaut) --}}
{{-- ───────────────────────────────────────────────────────── --}}
<div id="step-sign" class="hidden">

    {{-- Barre de statut fichier --}}
    <div class="flex items-center gap-3 mb-5 px-4 py-3 bg-green-50 border border-green-200 rounded-xl text-sm">
        <i class="fas fa-file-pdf text-green-500 text-lg flex-shrink-0"></i>
        <div class="flex-1 min-w-0">
            <span class="font-semibold text-green-800" id="status-filename">—</span>
            <span class="text-green-600 ml-2" id="status-filesize"></span>
        </div>
        <button type="button" onclick="resetUpload()"
            class="text-xs text-gray-500 hover:text-red-600 flex items-center gap-1 transition flex-shrink-0">
            <i class="fas fa-times-circle"></i> Changer de fichier
        </button>
    </div>

    <div class="flex gap-5 items-start">

        {{-- ── PANNEAU GAUCHE : PDF + ZONE ── --}}
        <div class="flex-1 min-w-0">
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">

                {{-- Barre outils --}}
                <div class="flex items-center gap-3 px-4 py-3 border-b border-gray-100 bg-gray-50 flex-wrap">
                    <button onclick="prevPage()" id="btn-prev"
                        class="h-8 w-8 flex items-center justify-center rounded-lg border border-gray-200 text-gray-600 hover:bg-white transition" title="Page précédente">
                        <i class="fas fa-chevron-left text-xs"></i>
                    </button>
                    <span class="text-sm text-gray-700 min-w-[90px] text-center">
                        Page <span id="page-num" class="font-bold text-gray-900">1</span>/<span id="page-count">—</span>
                    </span>
                    <button onclick="nextPage()" id="btn-next"
                        class="h-8 w-8 flex items-center justify-center rounded-lg border border-gray-200 text-gray-600 hover:bg-white transition" title="Page suivante">
                        <i class="fas fa-chevron-right text-xs"></i>
                    </button>
                    <div class="flex-1"></div>
                    <button onclick="zoomIn()"
                        class="h-8 w-8 flex items-center justify-center rounded-lg border border-gray-200 text-gray-600 hover:bg-white transition" title="Zoom +">
                        <i class="fas fa-search-plus text-xs"></i>
                    </button>
                    <span id="zoom-label" class="text-xs text-gray-500 w-10 text-center">140%</span>
                    <button onclick="zoomOut()"
                        class="h-8 w-8 flex items-center justify-center rounded-lg border border-gray-200 text-gray-600 hover:bg-white transition" title="Zoom -">
                        <i class="fas fa-search-minus text-xs"></i>
                    </button>
                    <button onclick="resetZone()"
                        class="px-3 py-1.5 rounded-lg bg-red-50 border border-red-200 text-red-600 text-xs font-medium hover:bg-red-100 transition flex items-center gap-1">
                        <i class="fas fa-times text-xs"></i> Effacer zone
                    </button>
                </div>

                {{-- Zone de rendu PDF --}}
                <div class="relative bg-gray-100 overflow-auto" style="min-height:580px;max-height:80vh;" id="pdf-container">
                    <div id="pdf-loading" class="absolute inset-0 flex items-center justify-center">
                        <div class="flex flex-col items-center gap-3 text-gray-400">
                            <i class="fas fa-spinner fa-spin text-4xl text-[#2453d6]"></i>
                            <span class="text-sm">Rendu du PDF…</span>
                        </div>
                    </div>
                    <div class="relative inline-block mx-auto" id="pdf-wrapper" style="display:none;">
                        <canvas id="pdf-canvas" class="block shadow-md mx-auto"></canvas>
                        <canvas id="zone-canvas" class="absolute top-0 left-0 cursor-crosshair" style="pointer-events:all;"></canvas>
                    </div>
                </div>

                {{-- Légende --}}
                <div class="px-4 py-2.5 border-t border-gray-100 bg-gray-50 text-xs text-gray-500 flex items-center gap-3">
                    <span class="inline-block w-4 h-4 border-2 border-dashed border-blue-500 bg-blue-100/50 rounded-sm flex-shrink-0"></span>
                    Zone de signature — cliquez et faites glisser pour la dessiner sur le document
                </div>
            </div>
        </div>

        {{-- ── PANNEAU DROIT : FORMULAIRE ── --}}
        <div class="w-80 flex-shrink-0">
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden sticky top-4">
                <div class="px-5 py-4 border-b border-gray-100 bg-gradient-to-r from-blue-600 to-blue-700">
                    <h2 class="text-base font-bold text-white flex items-center gap-2">
                        <i class="fas fa-pen-nib"></i> Apposer ma signature
                    </h2>
                    <p class="text-xs text-blue-200 mt-0.5">Dessinez ou tapez votre signature</p>
                </div>

                <form id="sign-form" action="{{ route('signatures.upload.post') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    {{-- Fichier PDF (injecté via JS) --}}
                    <input type="file" id="form-file-input" name="pdf_file" class="hidden" accept=".pdf">

                    <input type="hidden" name="zone_page"   id="inp-zone-page"   value="1">
                    <input type="hidden" name="zone_x"      id="inp-zone-x"      value="">
                    <input type="hidden" name="zone_y"      id="inp-zone-y"      value="">
                    <input type="hidden" name="zone_width"  id="inp-zone-width"  value="">
                    <input type="hidden" name="zone_height" id="inp-zone-height" value="">
                    <input type="hidden" name="zone_label"  id="inp-zone-label"  value="">
                    <input type="hidden" name="signature"   id="inp-signature"   value="">

                    <div class="px-5 py-4 space-y-4">

                        {{-- Titre du document --}}
                        <div class="space-y-1.5">
                            <label class="block text-sm font-semibold text-gray-700">
                                Titre du document <span class="text-gray-400 font-normal text-xs">(optionnel)</span>
                            </label>
                            <input type="text" name="doc_title" id="f-doc-title"
                                placeholder="Nom du document…"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#2453d6]">
                        </div>

                        {{-- Zone info --}}
                        <div id="zone-info" class="hidden p-3 bg-blue-50 border border-blue-200 rounded-xl text-xs text-blue-800 space-y-1">
                            <p class="font-semibold flex items-center gap-1.5">
                                <i class="fas fa-check-circle text-blue-500"></i> Zone sélectionnée
                            </p>
                            <p id="zone-info-text"></p>
                        </div>
                        <div id="zone-warn" class="p-3 bg-amber-50 border border-amber-200 rounded-xl text-xs text-amber-700 flex items-start gap-1.5">
                            <i class="fas fa-mouse-pointer text-amber-500 flex-shrink-0 mt-0.5"></i>
                            <p>Dessinez d'abord la zone sur le PDF en cliquant et faisant glisser.</p>
                        </div>

                        {{-- Pad signature --}}
                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <label class="text-sm font-semibold text-gray-700">Votre signature</label>
                                <div class="flex gap-1.5">
                                    <button type="button" onclick="setSignMode('draw')" id="btn-mode-draw"
                                        class="px-2.5 py-1 text-xs rounded-lg border font-medium transition bg-[#2453d6] text-white border-[#2453d6]">
                                        <i class="fas fa-pen mr-1"></i>Dessiner
                                    </button>
                                    <button type="button" onclick="setSignMode('type')" id="btn-mode-type"
                                        class="px-2.5 py-1 text-xs rounded-lg border font-medium transition bg-white text-gray-600 border-gray-300 hover:border-gray-400">
                                        <i class="fas fa-keyboard mr-1"></i>Texte
                                    </button>
                                </div>
                            </div>

                            {{-- Mode dessin --}}
                            <div id="panel-draw" class="border border-gray-200 rounded-xl overflow-hidden bg-white">
                                <canvas id="sig-canvas" width="288" height="100"
                                    class="block w-full cursor-crosshair bg-gray-50"
                                    onmousedown="sigStart(event)" onmousemove="sigDraw(event)"
                                    onmouseup="sigStop()" onmouseleave="sigStop()"
                                    ontouchstart="sigStartT(event)" ontouchmove="sigDrawT(event)" ontouchend="sigStop()">
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
                                <input type="text" id="sig-text-input" placeholder="Votre nom ou initiales"
                                    oninput="updateTextSig(this.value)"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#2453d6]">
                                <div class="border border-gray-200 rounded-xl overflow-hidden">
                                    <canvas id="sig-text-canvas" width="288" height="80" class="block w-full bg-gray-50"></canvas>
                                    <div class="px-3 py-1.5 border-t border-gray-100 bg-gray-50">
                                        <p class="text-xs text-gray-400">Style</p>
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
                            <input type="text" name="reason" placeholder="Ex: Bon pour accord, Approuvé…"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#2453d6]">
                        </div>

                        {{-- Label de zone --}}
                        <div class="space-y-1.5">
                            <label class="block text-sm font-semibold text-gray-700">
                                Texte de la zone <span class="text-gray-400 font-normal text-xs">(optionnel)</span>
                            </label>
                            <input type="text" id="f-zone-label" placeholder="Ex: Signature du directeur"
                                oninput="document.getElementById('inp-zone-label').value=this.value; updateZoneOverlay();"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#2453d6]">
                        </div>

                        {{-- Message erreur JS --}}
                        <div id="js-error" class="hidden p-3 bg-red-50 border border-red-200 rounded-xl text-xs text-red-700">
                            <i class="fas fa-exclamation-circle mr-1"></i><span id="js-error-text"></span>
                        </div>

                        {{-- Bouton --}}
                        <button type="button" onclick="submitSign()"
                            class="w-full py-3 bg-[#2453d6] hover:bg-[#1f47bb] text-white font-bold rounded-xl flex items-center justify-center gap-2 transition shadow-sm">
                            <i class="fas fa-check-circle"></i> Signer et enregistrer
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
</div>

{{-- PDF.js CDN --}}
<script src="{{ asset('vendor/pdfjs/pdf.min.js') }}"></script>

<script>
// ══════════════════════════════════════════════════════════
// ÉTAT GLOBAL
// ══════════════════════════════════════════════════════════
let pdfDoc    = null;
let pageNum   = 1;
let pageCount = 0;
let scale     = 1.4;
let currentFile = null;   // File object sélectionné
let signMode  = 'draw';
let sigFont   = 'Dancing Script';
let isSigDrawing = false;
let sigLastPos   = null;
let isDragging   = false;
let dragStart    = { x: 0, y: 0 };
let zone         = null;  // { x, y, w, h } pixels sur canvas

const pdfCanvas  = () => document.getElementById('pdf-canvas');
const zoneCanvas = () => document.getElementById('zone-canvas');

// ══════════════════════════════════════════════════════════
// UPLOAD / DRAG & DROP
// ══════════════════════════════════════════════════════════
function dragOver(e) {
    e.preventDefault();
    document.getElementById('drop-area').classList.add('border-[#2453d6]', 'bg-blue-50');
}
function dragLeave(e) {
    document.getElementById('drop-area').classList.remove('bg-blue-50');
}
function dropFile(e) {
    e.preventDefault();
    dragLeave(e);
    const f = e.dataTransfer.files[0];
    if (f) fileSelected(f);
}
function fileSelected(file) {
    if (!file || file.type !== 'application/pdf') {
        showJsError('Veuillez sélectionner un fichier PDF valide.');
        return;
    }
    if (file.size > 20 * 1024 * 1024) {
        showJsError('Le fichier dépasse la taille maximale de 20 Mo.');
        return;
    }
    currentFile = file;

    // Pré-remplir le titre
    const titleInput = document.getElementById('f-doc-title');
    if (!titleInput.value) {
        titleInput.value = file.name.replace(/\.pdf$/i, '');
    }

    // Afficher le nom + taille
    document.getElementById('status-filename').textContent = file.name;
    document.getElementById('status-filesize').textContent = '(' + formatSize(file.size) + ')';
    document.getElementById('upload-progress').classList.remove('hidden');

    // Lire le fichier via FileReader → ArrayBuffer → PDF.js
    const reader = new FileReader();
    reader.onload = function(e) {
        loadPdf(e.target.result);
    };
    reader.readAsArrayBuffer(file);

    // Transition UI
    document.getElementById('step-upload').classList.add('hidden');
    document.getElementById('step-sign').classList.remove('hidden');

    // Synchroniser avec l'input file du formulaire (DataTransfer)
    const dt = new DataTransfer();
    dt.items.add(file);
    document.getElementById('form-file-input').files = dt.files;
}

function formatSize(bytes) {
    if (bytes < 1024) return bytes + ' o';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' Ko';
    return (bytes / (1024 * 1024)).toFixed(1) + ' Mo';
}

function resetUpload() {
    currentFile = null;
    pdfDoc = null;
    pageNum = 1;
    pageCount = 0;
    zone = null;
    document.getElementById('step-sign').classList.add('hidden');
    document.getElementById('step-upload').classList.remove('hidden');
    document.getElementById('upload-progress').classList.add('hidden');
    document.getElementById('file-input').value = '';
    document.getElementById('form-file-input').value = '';
    document.getElementById('pdf-wrapper').style.display = 'none';
    document.getElementById('pdf-loading').style.display = 'flex';
    clearJsError();
}

// ══════════════════════════════════════════════════════════
// PDF.JS
// ══════════════════════════════════════════════════════════
function loadPdf(arrayBuffer) {
    pdfjsLib.GlobalWorkerOptions.workerSrc =
        '{{ asset("vendor/pdfjs/pdf.worker.min.js") }}';

    const loadingTask = pdfjsLib.getDocument({ data: arrayBuffer });
    loadingTask.promise.then(function(pdf) {
        pdfDoc   = pdf;
        pageCount = pdf.numPages;
        document.getElementById('page-count').textContent = pageCount;
        renderPage(1);
    }).catch(function(err) {
        document.getElementById('pdf-loading').innerHTML =
            '<div class="flex flex-col items-center gap-3 text-red-400">' +
            '<i class="fas fa-exclamation-triangle text-4xl"></i>' +
            '<span class="text-sm">Impossible de lire ce PDF.</span>' +
            '</div>';
    });
}

function renderPage(n) {
    pageNum = n;
    document.getElementById('page-num').textContent = n;
    document.getElementById('inp-zone-page').value  = n;
    document.getElementById('zoom-label').textContent = Math.round(scale * 100) + '%';

    pdfDoc.getPage(n).then(function(page) {
        const vp = page.getViewport({ scale });
        const pc = pdfCanvas();
        const zc = zoneCanvas();
        pc.width  = vp.width;  pc.height  = vp.height;
        zc.width  = vp.width;  zc.height  = vp.height;

        page.render({ canvasContext: pc.getContext('2d'), viewport: vp }).promise.then(function() {
            document.getElementById('pdf-loading').style.display = 'none';
            document.getElementById('pdf-wrapper').style.display = 'block';
            redrawZone();
        });
    });
}

function prevPage() { if (pageNum > 1) { resetZone(); renderPage(pageNum - 1); } }
function nextPage() { if (pdfDoc && pageNum < pageCount) { resetZone(); renderPage(pageNum + 1); } }
function zoomIn()  { scale = Math.min(scale + 0.2, 3.0); renderPage(pageNum); }
function zoomOut() { scale = Math.max(scale - 0.2, 0.5); renderPage(pageNum); }

// ══════════════════════════════════════════════════════════
// SÉLECTION DE ZONE
// ══════════════════════════════════════════════════════════
function getCanvasPos(e, canvas) {
    const r = canvas.getBoundingClientRect();
    return {
        x: (e.clientX - r.left) * (canvas.width  / r.width),
        y: (e.clientY - r.top)  * (canvas.height / r.height),
    };
}

function setupZoneCanvas() {
    const zc = zoneCanvas();
    zc.onmousedown  = startDrag;
    zc.onmousemove  = onDrag;
    zc.onmouseup    = endDrag;
    zc.ontouchstart = startDragTouch;
    zc.ontouchmove  = onDragTouch;
    zc.ontouchend   = endDragTouch;
}

function startDrag(e) {
    if (e.button !== 0) return;
    isDragging = true;
    dragStart  = getCanvasPos(e, zoneCanvas());
    zone       = null;
}
function onDrag(e) {
    if (!isDragging) return;
    const pos = getCanvasPos(e, zoneCanvas());
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
function startDragTouch(e) { e.preventDefault(); startDrag(e.touches[0]); }
function onDragTouch(e)    { e.preventDefault(); onDrag(e.touches[0]); }
function endDragTouch(e)   { endDrag(e); }

function redrawZone() {
    const zc = zoneCanvas();
    if (!zc) return;
    const ctx = zc.getContext('2d');
    ctx.clearRect(0, 0, zc.width, zc.height);
    if (!zone || zone.w < 2 || zone.h < 2) return;

    // Rectangle en tirets bleus
    ctx.save();
    ctx.strokeStyle = '#2453d6';
    ctx.lineWidth   = 2;
    ctx.setLineDash([6, 4]);
    ctx.fillStyle   = 'rgba(36,83,214,0.07)';
    ctx.fillRect(zone.x, zone.y, zone.w, zone.h);
    ctx.strokeRect(zone.x, zone.y, zone.w, zone.h);
    ctx.setLineDash([]);

    // Poignées de coin
    const hs = 8;
    ctx.fillStyle = '#2453d6';
    [[zone.x, zone.y], [zone.x+zone.w, zone.y],
     [zone.x, zone.y+zone.h], [zone.x+zone.w, zone.y+zone.h]].forEach(([cx, cy]) => {
        ctx.fillRect(cx - hs/2, cy - hs/2, hs, hs);
    });

    // Label
    const label = document.getElementById('f-zone-label')?.value || '';
    if (label) {
        ctx.fillStyle = '#2453d6';
        ctx.font = '12px sans-serif';
        ctx.fillText(label, zone.x + 4, zone.y - 6);
    }
    ctx.restore();
}

function saveZoneInputs() {
    const zc = zoneCanvas();
    const xPct = ((zone.x / zc.width)  * 100).toFixed(4);
    const yPct = ((zone.y / zc.height) * 100).toFixed(4);
    const wPct = ((zone.w / zc.width)  * 100).toFixed(4);
    const hPct = ((zone.h / zc.height) * 100).toFixed(4);

    document.getElementById('inp-zone-x').value      = xPct;
    document.getElementById('inp-zone-y').value      = yPct;
    document.getElementById('inp-zone-width').value  = wPct;
    document.getElementById('inp-zone-height').value = hPct;

    document.getElementById('zone-info-text').textContent =
        `Page ${pageNum} · X ${parseFloat(xPct).toFixed(1)}% · Y ${parseFloat(yPct).toFixed(1)}% · ${parseFloat(wPct).toFixed(1)}×${parseFloat(hPct).toFixed(1)}%`;
    document.getElementById('zone-info').classList.remove('hidden');
    document.getElementById('zone-warn').classList.add('hidden');
}

function updateZoneOverlay() { redrawZone(); }

function resetZone() {
    zone = null;
    redrawZone();
    document.getElementById('inp-zone-x').value      = '';
    document.getElementById('inp-zone-y').value      = '';
    document.getElementById('inp-zone-width').value  = '';
    document.getElementById('inp-zone-height').value = '';
    document.getElementById('zone-info').classList.add('hidden');
    document.getElementById('zone-warn').classList.remove('hidden');
}

// ══════════════════════════════════════════════════════════
// SIGNATURE PAD
// ══════════════════════════════════════════════════════════
function setSignMode(mode) {
    signMode = mode;
    const isDraw = mode === 'draw';
    document.getElementById('panel-draw').classList.toggle('hidden', !isDraw);
    document.getElementById('panel-type').classList.toggle('hidden', isDraw);
    document.getElementById('btn-mode-draw').className = isDraw
        ? 'px-2.5 py-1 text-xs rounded-lg border font-medium transition bg-[#2453d6] text-white border-[#2453d6]'
        : 'px-2.5 py-1 text-xs rounded-lg border font-medium transition bg-white text-gray-600 border-gray-300 hover:border-gray-400';
    document.getElementById('btn-mode-type').className = isDraw
        ? 'px-2.5 py-1 text-xs rounded-lg border font-medium transition bg-white text-gray-600 border-gray-300 hover:border-gray-400'
        : 'px-2.5 py-1 text-xs rounded-lg border font-medium transition bg-[#2453d6] text-white border-[#2453d6]';
}

function sigStart(e) {
    isSigDrawing = true;
    const c = document.getElementById('sig-canvas');
    const r = c.getBoundingClientRect();
    sigLastPos = {
        x: (e.clientX - r.left) * (c.width / r.width),
        y: (e.clientY - r.top)  * (c.height / r.height),
    };
}
function sigDraw(e) {
    if (!isSigDrawing) return;
    const c   = document.getElementById('sig-canvas');
    const ctx = c.getContext('2d');
    const r   = c.getBoundingClientRect();
    const pos = {
        x: (e.clientX - r.left) * (c.width / r.width),
        y: (e.clientY - r.top)  * (c.height / r.height),
    };
    ctx.beginPath();
    ctx.moveTo(sigLastPos.x, sigLastPos.y);
    ctx.lineTo(pos.x, pos.y);
    ctx.strokeStyle = '#1a1a2e';
    ctx.lineWidth   = 2;
    ctx.lineCap     = 'round';
    ctx.stroke();
    sigLastPos = pos;
}
function sigStop() { isSigDrawing = false; sigLastPos = null; }
function sigStartT(e) { e.preventDefault(); sigStart(e.touches[0]); }
function sigDrawT(e)  { e.preventDefault(); sigDraw(e.touches[0]); }

function clearSig() {
    const c = document.getElementById('sig-canvas');
    c.getContext('2d').clearRect(0, 0, c.width, c.height);
}

function setSigFont(font) {
    sigFont = font;
    document.querySelectorAll('.sig-font-btn').forEach(b => {
        b.className = 'sig-font-btn px-2 py-1 text-xs border border-gray-200 text-gray-600 rounded';
    });
    event.target.className = 'sig-font-btn px-2 py-1 text-xs border border-[#2453d6] bg-blue-50 text-blue-700 rounded font-medium';
    const txt = document.getElementById('sig-text-input').value;
    if (txt) updateTextSig(txt);
}

function updateTextSig(txt) {
    const c   = document.getElementById('sig-text-canvas');
    const ctx = c.getContext('2d');
    ctx.clearRect(0, 0, c.width, c.height);
    if (!txt) return;
    ctx.font      = `36px '${sigFont}', cursive`;
    ctx.fillStyle = '#1a1a2e';
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    ctx.fillText(txt, c.width / 2, c.height / 2);
}

// ══════════════════════════════════════════════════════════
// SOUMISSION
// ══════════════════════════════════════════════════════════
function submitSign() {
    clearJsError();

    // Vérifier zone
    if (!document.getElementById('inp-zone-x').value) {
        showJsError('Veuillez dessiner la zone de signature sur le document.');
        document.getElementById('zone-warn').scrollIntoView({ behavior: 'smooth' });
        return;
    }

    // Récupérer la signature
    let sigData = '';
    if (signMode === 'draw') {
        const c = document.getElementById('sig-canvas');
        sigData = c.toDataURL('image/png');
        // Vérifier si le canvas est vide
        const ctx = c.getContext('2d');
        const data = ctx.getImageData(0, 0, c.width, c.height).data;
        const isEmpty = !data.some(v => v !== 0);
        if (isEmpty) {
            showJsError('Veuillez tracer votre signature dans le cadre.');
            return;
        }
    } else {
        const txt = document.getElementById('sig-text-input').value.trim();
        if (!txt) {
            showJsError('Veuillez saisir votre nom ou initiales pour la signature.');
            return;
        }
        sigData = document.getElementById('sig-text-canvas').toDataURL('image/png');
    }

    document.getElementById('inp-signature').value = sigData;

    // Vérifier que le fichier est toujours attaché
    if (!currentFile) {
        showJsError('Aucun fichier sélectionné. Veuillez recommencer.');
        return;
    }

    // S'assurer que l'input file est synchronisé
    const dt = new DataTransfer();
    dt.items.add(currentFile);
    document.getElementById('form-file-input').files = dt.files;

    document.getElementById('sign-form').submit();
}

function showJsError(msg) {
    document.getElementById('js-error-text').textContent = msg;
    document.getElementById('js-error').classList.remove('hidden');
}
function clearJsError() {
    document.getElementById('js-error').classList.add('hidden');
}

// ══════════════════════════════════════════════════════════
// INIT
// ══════════════════════════════════════════════════════════
document.addEventListener('DOMContentLoaded', function() {
    setupZoneCanvas();
});
</script>
@endsection
