<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #222; }
        h1 { font-size: 16px; margin-bottom: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        th { background: #f5f5f5; }
    </style>
</head>
<body>
    <h1>Liste de présence</h1>
    <div>Réunion: {{ $meeting->title }}</div>
    <div>Date: {{ $meeting->starts_at?->format('d/m/Y H:i') }}</div>

    <table>
        <thead>
            <tr>
                <th>Nom</th>
                <th>Email</th>
                <th>Téléphone</th>
                <th>Signature</th>
            </tr>
        </thead>
        <tbody>
            @forelse($attendances as $a)
            <tr>
                <td>{{ $a->full_name }}</td>
                <td>{{ $a->email }}</td>
                <td>{{ $a->phone }}</td>
                <td>{{ $a->signed_at?->format('d/m/Y H:i:s') }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="4">Aucune présence enregistrée.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
