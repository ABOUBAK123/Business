<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 11px;
            color: #1a1a1a;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #e87020;
            padding-bottom: 10px;
            margin-bottom: 14px;
        }
        .header img {
            height: 60px;
            margin-bottom: 6px;
        }
        .header .entity {
            font-size: 14px;
            font-weight: bold;
            color: #1a1a1a;
        }
        .header .label {
            font-size: 10px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 2px;
        }
        .meeting-info {
            background: #f7f9fc;
            border: 1px solid #dde3ed;
            border-radius: 4px;
            padding: 8px 12px;
            margin-bottom: 14px;
            font-size: 10px;
            color: #444;
        }
        .meeting-info .title {
            font-size: 13px;
            font-weight: bold;
            color: #1a1a1a;
            margin-bottom: 4px;
        }
        .meeting-info table {
            width: 100%;
            border: none;
        }
        .meeting-info td {
            padding: 1px 8px 1px 0;
            border: none;
        }
        .stats {
            margin-bottom: 10px;
            font-size: 10px;
            color: #555;
        }
        .stats strong { color: #1a1a1a; }
        table.list {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
        }
        table.list thead tr {
            background: #2453d6;
            color: #fff;
        }
        table.list thead th {
            padding: 6px 8px;
            text-align: left;
            font-weight: bold;
        }
        table.list tbody tr:nth-child(even) {
            background: #f4f6fb;
        }
        table.list tbody td {
            padding: 5px 8px;
            border-bottom: 1px solid #e5e9f0;
            vertical-align: top;
        }
        .signature-preview {
            width: 82px;
            height: 32px;
            object-fit: contain;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            background: #fff;
            padding: 2px;
        }
        .signature-missing {
            color: #9ca3af;
            font-style: italic;
        }
        .footer {
            margin-top: 16px;
            font-size: 9px;
            color: #999;
            text-align: right;
        }
        .no-data {
            text-align: center;
            color: #aaa;
            padding: 20px;
            font-style: italic;
        }
    </style>
</head>
<body>

<div class="header">
    @if(!empty($branding['logo_pdf_src']))
        <img src="{{ $branding['logo_pdf_src'] }}">
    @elseif(!empty($branding['logo_url']))
        <img src="{{ $branding['logo_url'] }}">
    @endif
    @if(!empty($branding['tutelle_entity_name']))
        <div class="entity">{{ $branding['tutelle_entity_name'] }}</div>
    @endif
    @if(!empty($branding['tutelle_entity_code']))
        <div class="entity"><strong>{{ $branding['tutelle_entity_code'] }}</strong></div>
    @endif
    <div class="label">Liste de présence</div>
</div>

<div class="meeting-info">
    <div class="title">{{ $meeting->title }}</div>
    <table>
        <tr>
            <td><strong>Date :</strong> {{ $meeting->starts_at?->format('d/m/Y H:i') }}</td>
            <td><strong>Fin :</strong> {{ $meeting->ends_at?->format('d/m/Y H:i') }}</td>
        </tr>
        <tr>
            <td><strong>Salle :</strong> {{ $meeting->room?->name }} ({{ $meeting->room?->location }})</td>
            <td><strong>Organisateur :</strong> {{ $meeting->organizer?->name }}</td>
        </tr>
    </table>
</div>

<div class="stats">
    Participants attendus : <strong>{{ $meeting->participants->count() }}</strong> &nbsp;|&nbsp;
    Présents : <strong>{{ $attendances->count() }}</strong>
</div>

@if($attendances->isEmpty())
    <div class="no-data">Aucune présence enregistrée.</div>
@else
<table class="list">
    <thead>
        <tr>
            <th>#</th>
            <th>Nom complet</th>
            <th>Identifiant</th>
            <th>Email</th>
            <th>Téléphone</th>
            <th>Fonction</th>
            <th>Organisation</th>
            <th>Signature</th>
            <th>Heure</th>
        </tr>
    </thead>
    <tbody>
        @foreach($attendances as $i => $a)
        <tr>
            <td>{{ $i + 1 }}</td>
            <td>{{ $a->full_name }}</td>
            <td>{{ filter_var($a->identifier, FILTER_VALIDATE_EMAIL) ? '' : $a->identifier }}</td>
            <td>{{ $a->email }}</td>
            <td>{{ $a->phone }}</td>
            <td>{{ $a->job_title }}</td>
            <td>{{ $a->organization }}</td>
            <td>
                @if(!empty($signatureSources[(string) $a->id] ?? null))
                    <img src="{{ $signatureSources[(string) $a->id] }}" class="signature-preview">
                @else
                    <span class="signature-missing">Aucune</span>
                @endif
            </td>
            <td>{{ $a->signed_at?->format('H:i') }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

<div class="footer">Généré le {{ now()->format('d/m/Y à H:i') }}</div>

</body>
</html>
