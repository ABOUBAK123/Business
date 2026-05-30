<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Étiquettes articles</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; background: #f3f4f6; }

        .screen-controls {
            background: #1e3a8a; color: white; padding: 16px 24px;
            display: flex; align-items: center; gap: 16px; flex-wrap: wrap;
        }
        .screen-controls h1 { font-size: 18px; font-weight: bold; flex: 1; }
        .btn { padding: 8px 16px; border-radius: 8px; font-size: 13px;
               font-weight: 600; cursor: pointer; border: none; }
        .btn-white { background: white; color: #1e3a8a; }
        .btn-green { background: #16a34a; color: white; }
        .btn-red   { background: #dc2626; color: white; }

        .selector { background: white; padding: 16px 24px; border-bottom: 1px solid #e5e7eb; }
        .selector label { font-size: 13px; color: #6b7280; margin-right: 8px; }
        .article-grid {
            padding: 20px 24px;
            display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 12px;
        }
        .article-card {
            background: white; border-radius: 10px; padding: 12px;
            border: 2px solid #e5e7eb; cursor: pointer;
            display: flex; align-items: center; gap-10px; gap: 10px;
        }
        .article-card:hover { border-color: #3b82f6; }
        .article-card.selected { border-color: #1e3a8a; background: #eff6ff; }
        .article-card input { width: 18px; height: 18px; }
        .article-info { flex: 1; min-width: 0; }
        .article-ref { font-size: 10px; color: #6b7280; font-family: monospace; }
        .article-name { font-size: 12px; font-weight: 600; color: #1f2937;
                        white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .article-price { font-size: 13px; font-weight: bold; color: #1e3a8a; }

        /* Zone d'impression */
        .labels-preview { display: none; }

        @media print {
            .screen-controls, .selector, .article-grid { display: none !important; }
            .labels-preview { display: block !important; }
            body { background: white; }
        }

        .labels-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 4mm;
            padding: 5mm;
        }
        .label {
            border: 1px solid #d1d5db;
            border-radius: 6px;
            padding: 8px;
            text-align: center;
            page-break-inside: avoid;
        }
        .label-ref { font-size: 9px; color: #6b7280; font-family: monospace; margin-bottom: 4px; }
        .label-name { font-size: 11px; font-weight: bold; color: #1f2937; margin-bottom: 4px;
                      white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .label-price { font-size: 14px; font-weight: bold; color: #1e3a8a; margin-top: 4px; }
        .label-unit  { font-size: 9px; color: #9ca3af; }
        .qr-container { display: flex; justify-content: center; margin: 4px 0; }
        .qr-container canvas, .qr-container img { width: 80px !important; height: 80px !important; }
    </style>
</head>
<body>

<div class="screen-controls">
    <h1><i class="fas fa-qrcode" style="margin-right:8px"></i>Étiquettes articles</h1>
    <span id="selectedCount" style="font-size:13px;opacity:.8">0 sélectionné(s)</span>
    <button class="btn btn-white" onclick="selectAll()">Tout sélectionner</button>
    <button class="btn btn-white" onclick="deselectAll()">Tout désélectionner</button>
    <button class="btn btn-green" onclick="printLabels()">🖨️ Imprimer les étiquettes</button>
    <a href="{{ route('articles.index') }}" class="btn btn-red">✕ Fermer</a>
</div>

<div class="selector">
    <label>Nombre de copies par étiquette :</label>
    <select id="copies" style="border:1px solid #d1d5db;border-radius:6px;padding:4px 8px;font-size:13px;">
        <option value="1">1</option>
        <option value="2">2</option>
        <option value="3">3</option>
        <option value="5">5</option>
    </select>
</div>

<div class="article-grid">
    @foreach($articles as $article)
    <div class="article-card" onclick="toggleCard(this, {{ $article->id }})"
         data-id="{{ $article->id }}"
         data-ref="{{ $article->reference }}"
         data-name="{{ $article->designation }}"
         data-price="{{ number_format($article->sale_price_ttc, 0, ',', ' ') }}"
         data-unit="{{ $article->unit }}">
        <input type="checkbox" onclick="event.stopPropagation();toggleCard(this.closest('.article-card'), {{ $article->id }})">
        <div class="article-info">
            <div class="article-ref">{{ $article->reference }}</div>
            <div class="article-name">{{ $article->designation }}</div>
            <div class="article-price">{{ number_format($article->sale_price_ttc, 0, ',', ' ') }} FCFA / {{ $article->unit }}</div>
        </div>
    </div>
    @endforeach
</div>

{{-- Zone d'impression générée dynamiquement --}}
<div class="labels-preview" id="labelsPreview"></div>

<script>
const selected = new Set();

function toggleCard(card, id) {
    const cb = card.querySelector('input[type=checkbox]');
    if (selected.has(id)) {
        selected.delete(id);
        card.classList.remove('selected');
        cb.checked = false;
    } else {
        selected.add(id);
        card.classList.add('selected');
        cb.checked = true;
    }
    document.getElementById('selectedCount').textContent = selected.size + ' sélectionné(s)';
}

function selectAll() {
    document.querySelectorAll('.article-card').forEach(card => {
        const id = parseInt(card.dataset.id);
        if (!selected.has(id)) toggleCard(card, id);
    });
}
function deselectAll() {
    document.querySelectorAll('.article-card').forEach(card => {
        const id = parseInt(card.dataset.id);
        if (selected.has(id)) toggleCard(card, id);
    });
}

function printLabels() {
    if (selected.size === 0) { alert('Sélectionnez au moins un article.'); return; }

    const copies = parseInt(document.getElementById('copies').value);
    const preview = document.getElementById('labelsPreview');
    preview.innerHTML = '';

    const grid = document.createElement('div');
    grid.className = 'labels-grid';

    const cards = [...document.querySelectorAll('.article-card')]
        .filter(c => selected.has(parseInt(c.dataset.id)));

    let pending = 0;

    cards.forEach(card => {
        for (let i = 0; i < copies; i++) {
            const label = document.createElement('div');
            label.className = 'label';

            const ref   = card.dataset.ref;
            const name  = card.dataset.name;
            const price = card.dataset.price;
            const unit  = card.dataset.unit;

            label.innerHTML = `
                <div class="label-ref">${ref}</div>
                <div class="label-name">${name}</div>
                <div class="qr-container" id="qr_${card.dataset.id}_${i}"></div>
                <div class="label-price">${price} FCFA</div>
                <div class="label-unit">/ ${unit}</div>
            `;
            grid.appendChild(label);
            pending++;
        }
    });

    preview.appendChild(grid);

    // Générer les QR codes
    cards.forEach(card => {
        for (let i = 0; i < copies; i++) {
            new QRCode(document.getElementById(`qr_${card.dataset.id}_${i}`), {
                text: card.dataset.ref,
                width: 80, height: 80,
                colorDark: '#000000', colorLight: '#ffffff',
                correctLevel: QRCode.CorrectLevel.M
            });
        }
    });

    // Attendre que les QR soient rendus puis imprimer
    setTimeout(() => window.print(), 600);
}
</script>
</body>
</html>
