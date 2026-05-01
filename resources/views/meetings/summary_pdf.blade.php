<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #222; }
        h1 { font-size: 16px; margin-bottom: 8px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        th { background: #f5f5f5; }
    </style>
</head>
<body>
    <h1>Synthèse {{ $mode === 'annual' ? 'annuelle' : 'mensuelle' }} des réunions</h1>
    <p>Période: {{ $mode === 'annual' ? $year : sprintf('%02d/%d', $month, $year) }}</p>
    <p>Nombre total: {{ $meetings->count() }}</p>

    <table>
        <thead>
            <tr>
                <th>Titre</th>
                <th>Type</th>
                <th>Date début</th>
                <th>Salle</th>
                <th>Organisateur</th>
                <th>Workflow</th>
            </tr>
        </thead>
        <tbody>
            @forelse($meetings as $m)
            <tr>
                <td>{{ $m->title }}</td>
                <td>{{ $m->meeting_type }}</td>
                <td>{{ $m->starts_at?->format('d/m/Y H:i') }}</td>
                <td>{{ $m->room?->name }}</td>
                <td>{{ $m->organizer?->name }}</td>
                <td>{{ $m->workflow_status ?: 'draft' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="6">Aucune réunion sur cette période.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
