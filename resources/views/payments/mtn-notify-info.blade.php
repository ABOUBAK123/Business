<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Callback MTN MoMo</title>
    <style>
        :root {
            --bg-1: #0b1220;
            --bg-2: #14213d;
            --card: #ffffff;
            --text: #1f2937;
            --muted: #6b7280;
            --ok: #16a34a;
            --warn: #b45309;
            --code-bg: #f3f4f6;
            --code-text: #111827;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            font-family: "Segoe UI", Tahoma, Arial, sans-serif;
            color: var(--text);
            background:
                radial-gradient(900px 500px at 10% 0%, #1f3a8a55 0%, transparent 70%),
                radial-gradient(700px 400px at 100% 100%, #22c55e22 0%, transparent 70%),
                linear-gradient(140deg, var(--bg-1), var(--bg-2));
            padding: 20px;
        }

        .card {
            width: min(760px, 100%);
            background: var(--card);
            border-radius: 16px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, .28);
            overflow: hidden;
            border: 1px solid #e5e7eb;
        }

        .head {
            padding: 18px 22px;
            background: linear-gradient(90deg, #f0fdf4, #ecfeff);
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .dot {
            width: 11px;
            height: 11px;
            border-radius: 999px;
            background: var(--ok);
            box-shadow: 0 0 0 4px #dcfce7;
            flex: 0 0 auto;
        }

        h1 {
            margin: 0;
            font-size: 18px;
            line-height: 1.3;
            font-weight: 700;
        }

        .body {
            padding: 22px;
        }

        p {
            margin: 0 0 14px 0;
            color: var(--muted);
            line-height: 1.55;
        }

        .box {
            margin-top: 8px;
            background: #fffbeb;
            border: 1px solid #fde68a;
            color: #92400e;
            border-radius: 10px;
            padding: 12px 14px;
            font-size: 14px;
        }

        .label {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: #64748b;
            margin: 16px 0 8px;
        }

        code {
            display: block;
            width: 100%;
            background: var(--code-bg);
            color: var(--code-text);
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 11px 12px;
            font-family: Consolas, "Courier New", monospace;
            font-size: 13px;
            overflow-x: auto;
            white-space: nowrap;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 10px;
            margin-top: 14px;
        }

        .item {
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 10px 12px;
            background: #fafafa;
        }

        .item b {
            display: block;
            font-size: 12px;
            color: #475569;
            margin-bottom: 4px;
            text-transform: uppercase;
        }

        .item span {
            font-size: 14px;
            color: #111827;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <main class="card" role="main" aria-label="Information callback MTN">
        <header class="head">
            <span class="dot" aria-hidden="true"></span>
            <h1>Endpoint Callback MTN MoMo actif</h1>
        </header>

        <section class="body">
            <p>
                Cette URL est un point de réception technique pour les notifications MTN Mobile Money.
                Elle est accessible en GET pour information, mais le traitement réel des paiements se fait uniquement en POST.
            </p>

            <div class="box">
                Utilisez cette adresse dans votre portail MTN MoMo comme callback serveur.
            </div>

            <div class="label">URL callback</div>
            <code>{{ url('/payment/mtn/notify') }}</code>

            <div class="grid">
                <div class="item">
                    <b>Méthode attendue</b>
                    <span>POST</span>
                </div>
                <div class="item">
                    <b>Route applicative</b>
                    <span>payment.mtn.notify</span>
                </div>
                <div class="item">
                    <b>Environnement local</b>
                    <span>OK</span>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
