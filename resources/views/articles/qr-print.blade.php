<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>QR Code - {{ $article->designation }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; background: white; }
        .label { border: 1.5px solid #333; padding: 10px; text-align: center; width: 200px; }
        .label svg { width: 160px; height: 160px; }
        .shop { font-size: 9px; font-weight: bold; color: #1e40af; margin-bottom: 4px; }
        .name { font-size: 10px; font-weight: bold; margin: 4px 0; }
        .ref { font-size: 8px; color: #666; margin-bottom: 4px; }
        .price { font-size: 14px; font-weight: bold; color: #1e40af; }
        .unit { font-size: 8px; color: #666; }
        @media print { body { margin: 0; } .no-print { display: none; } }
    </style>
</head>
<body>
    <div>
        <div class="label">
            <div class="shop">{{ $article->tenant?->shop_name }}</div>
            {!! $qrImage !!}
            <div class="name">{{ Str::limit($article->designation, 30) }}</div>
            <div class="ref">Réf: {{ $article->reference }}</div>
            <div class="price">{{ number_format($article->sale_price_ttc, 0, ',', ' ') }} FCFA</div>
            <div class="unit">/ {{ $article->unit }}</div>
        </div>

        <div class="no-print" style="margin-top: 20px; text-align: center;">
            <button onclick="window.print()" style="background:#1e40af;color:white;padding:8px 20px;border:none;border-radius:6px;cursor:pointer;font-size:14px;">
                🖨 Imprimer l'étiquette
            </button>
            <button onclick="window.close()" style="margin-left:10px;padding:8px 20px;border:1px solid #ccc;border-radius:6px;cursor:pointer;font-size:14px;">
                Fermer
            </button>
        </div>
    </div>
</body>
</html>
