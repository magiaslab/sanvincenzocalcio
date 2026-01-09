<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Convocazioni - {{ $event->title ?? $event->team->name }}</title>
    <style>
        @page {
            margin: 20mm;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            line-height: 1.6;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #F59E0B;
            padding-bottom: 20px;
        }
        .header h1 {
            font-size: 24px;
            margin: 0;
            color: #1F2937;
        }
        .header h2 {
            font-size: 18px;
            margin: 10px 0 0 0;
            color: #6B7280;
            font-weight: normal;
        }
        .event-details {
            background-color: #F9FAFB;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        .event-details h3 {
            font-size: 16px;
            margin: 0 0 15px 0;
            color: #1F2937;
            border-bottom: 2px solid #E5E7EB;
            padding-bottom: 10px;
        }
        .detail-row {
            display: table;
            width: 100%;
            margin-bottom: 10px;
        }
        .detail-label {
            display: table-cell;
            width: 150px;
            font-weight: bold;
            color: #4B5563;
        }
        .detail-value {
            display: table-cell;
            color: #1F2937;
        }
        .convocations-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .convocations-table thead {
            background-color: #F59E0B;
            color: white;
        }
        .convocations-table th {
            padding: 12px;
            text-align: left;
            font-weight: bold;
        }
        .convocations-table td {
            padding: 10px 12px;
            border-bottom: 1px solid #E5E7EB;
        }
        .convocations-table tbody tr:nth-child(even) {
            background-color: #F9FAFB;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
        }
        .status-convocato {
            background-color: #FEF3C7;
            color: #92400E;
        }
        .status-accettato {
            background-color: #D1FAE5;
            color: #065F46;
        }
        .status-rifiutato {
            background-color: #FEE2E2;
            color: #991B1B;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #E5E7EB;
            text-align: center;
            font-size: 10px;
            color: #6B7280;
        }
        .no-convocations {
            text-align: center;
            padding: 40px;
            color: #6B7280;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>CONVOCAZIONI</h1>
        <h2>{{ $event->team->name ?? 'Squadra' }}</h2>
    </div>

    <div class="event-details">
        <h3>Dettagli Partita</h3>
        <div class="detail-row">
            <div class="detail-label">Titolo:</div>
            <div class="detail-value">{{ $event->title ?? 'Nessun titolo' }}</div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Data e Ora:</div>
            <div class="detail-value">{{ $event->start_time->format('d/m/Y H:i') }} - {{ $event->end_time->format('H:i') }}</div>
        </div>
        @if($event->field)
        <div class="detail-row">
            <div class="detail-label">Campo:</div>
            <div class="detail-value">{{ $event->field->name }}</div>
        </div>
        @if($event->field->address)
        <div class="detail-row">
            <div class="detail-label">Luogo di Ritrovo:</div>
            <div class="detail-value">{{ $event->field->address }}</div>
        </div>
        @endif
        @else
        <div class="detail-row">
            <div class="detail-label">Luogo di Ritrovo:</div>
            <div class="detail-value">Da definire</div>
        </div>
        @endif
        @if($event->description)
        <div class="detail-row">
            <div class="detail-label">Note:</div>
            <div class="detail-value">{{ $event->description }}</div>
        </div>
        @endif
    </div>

    <h3 style="margin-bottom: 15px; color: #1F2937;">Atleti Convocati</h3>
    
    @if($convocations->count() > 0)
    <table class="convocations-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Nome Atleta</th>
                <th>Genitore</th>
                <th>Stato</th>
                <th>Note</th>
            </tr>
        </thead>
        <tbody>
            @foreach($convocations as $index => $convocation)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td><strong>{{ $convocation->athlete->name }}</strong></td>
                <td>{{ $convocation->athlete->parent->name ?? 'N/D' }}</td>
                <td>
                    <span class="status-badge status-{{ $convocation->status }}">
                        @if($convocation->status === 'convocato')
                            Convocato
                        @elseif($convocation->status === 'accettato')
                            Accettato
                        @else
                            Rifiutato
                        @endif
                    </span>
                </td>
                <td>{{ $convocation->notes ?? '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <div class="no-convocations">
        Nessuna convocazione presente per questo evento.
    </div>
    @endif

    <div class="footer">
        <p>Documento generato il {{ now()->format('d/m/Y H:i') }} - San Vincenzo Calcio</p>
        <p>Totale convocati: {{ $convocations->count() }}</p>
    </div>
</body>
</html>

