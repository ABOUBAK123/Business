<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Impression QR codes</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: white; padding: 10px; }
        .grid { display: flex; flex-wrap: wrap; gap: 8px; }
        .label { border: 1.5px solid #333; padding: 8px; text-align: center; width: 180px; }
        .label svg { width: 140px; height: 140px; }
        .shop { font-size: 8px; font-weight: bold; color: #1e40af; margin-bottom: 3px; }
        .name { font-size: 9px; font-weight: bold; margin: 3px 0; }
        .ref { font-size: 7px; color: #666; margin-bottom: 3px; }
        .price { font-size: 13px; font-weight: bold; color: #1e40af; }
        .unit { font-size: 7px; color: #666; }
        .no-print { display: block; }
        @media print {
            .no-print { display: none !important; }
            body { padding: 0; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="padding: 16px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; margin-bottom: 16px;">
        <div style="display:flex;align-items:center;justify-content:space-between;">
            <div>
                <h1 style="font-size:16px;font-weight:bold;color:#1e293b;">{{ count($qrCodes) }} étiquette(s) à imprimer</h1>
            </div>
            <div style="display:flex;gap:8px;">
                <button onclick="window.print()"
                        style="background:#1e40af;color:white;padding:8px 20px;border:none;border-radius:6px;cursor:pointer;font-size:14px;">
                    Imprimer tout
                </button>
                <button onclick="window.close()"
                        style="padding:8px 20px;border:1px solid #ccc;border-radius:6px;cursor:pointer;font-size:14px;">
                    Fermer
                </button>
            </div>
        </div>
    </div>

    <div class="grid">
        @foreach($qrCodes as $entry)
        <div class="label">
            <div class="shop">{{ $entry['article']->tenant?->shop_name }}</div>
            {!! $entry['qr'] !!}
            <div class="name">{{ Str::limit($entry['article']->designation, 28) }}</div>
            <div class="ref">Réf: {{ $entry['article']->reference }}</div>
            <div class="price">{{ number_format($entry['article']->sale_price_ttc, 0, ',', ' ') }} FCFA</div>
            <div class="unit">/ {{ $entry['article']->unit }}</div>
        </div>
        @endforeach
    </div>
</body>
</html>
