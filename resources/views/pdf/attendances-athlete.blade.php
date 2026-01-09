<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistiche Presenze - {{ $athlete->name }}</title>
    <style>
        @page {
            margin: 15mm;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            line-height: 1.5;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 25px;
            border-bottom: 3px solid #F59E0B;
            padding-bottom: 15px;
        }
        .header h1 {
            font-size: 22px;
            margin: 0;
            color: #1F2937;
        }
        .header h2 {
            font-size: 16px;
            margin: 8px 0 0 0;
            color: #6B7280;
            font-weight: normal;
        }
        .summary {
            background-color: #F9FAFB;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .summary-row {
            display: table;
            width: 100%;
            margin-bottom: 8px;
        }
        .summary-label {
            display: table-cell;
            width: 200px;
            font-weight: bold;
            color: #4B5563;
        }
        .summary-value {
            display: table-cell;
            color: #1F2937;
        }
        .stats-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            margin-bottom: 20px;
        }
        .stats-table thead {
            background-color: #F59E0B;
            color: white;
        }
        .stats-table th {
            padding: 10px;
            text-align: left;
            font-weight: bold;
            font-size: 10px;
        }
        .stats-table td {
            padding: 8px 10px;
            border-bottom: 1px solid #E5E7EB;
            font-size: 10px;
        }
        .stats-table tbody tr:nth-child(even) {
            background-color: #F9FAFB;
        }
        .present {
            color: #065F46;
            font-weight: bold;
        }
        .absent {
            color: #991B1B;
            font-weight: bold;
        }
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #E5E7EB;
            text-align: center;
            font-size: 9px;
            color: #6B7280;
        }
        .period {
            font-size: 10px;
            color: #6B7280;
            margin-bottom: 15px;
        }
        .percentage {
            font-size: 14px;
            font-weight: bold;
        }
        .percentage-high {
            color: #065F46;
        }
        .percentage-medium {
            color: #92400E;
        }
        .percentage-low {
            color: #991B1B;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>STATISTICHE PRESENZE</h1>
        <h2>{{ $athlete->name }}</h2>
    </div>

    <div class="period">
        <strong>Squadre:</strong> {{ $athlete->teams->pluck('name')->join(', ') ?: 'N/D' }}<br>
        <strong>Periodo:</strong> 
        @if($startDate && $endDate)
            Dal {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} al {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}
        @else
            Tutti gli allenamenti
        @endif
    </div>

    <div class="summary">
        <div class="summary-row">
            <div class="summary-label">Totale Allenamenti:</div>
            <div class="summary-value">{{ $totalTrainings }}</div>
        </div>
        <div class="summary-row">
            <div class="summary-label">Presenze:</div>
            <div class="summary-value present">{{ $totalPresences }}</div>
        </div>
        <div class="summary-row">
            <div class="summary-label">Assenze:</div>
            <div class="summary-value absent">{{ $totalAbsences }}</div>
        </div>
        <div class="summary-row">
            <div class="summary-label">Percentuale Presenza:</div>
            <div class="summary-value">
                <span class="percentage {{ $percentageClass }}">{{ $percentage }}%</span>
            </div>
        </div>
    </div>

    <h3 style="margin-bottom: 12px; color: #1F2937; font-size: 14px;">Dettaglio Presenze per Data</h3>
    
    @if($attendances->count() > 0)
    <table class="stats-table">
        <thead>
            <tr>
                <th>Data</th>
                <th>Ora</th>
                <th>Stato</th>
                <th>Motivazione</th>
            </tr>
        </thead>
        <tbody>
            @foreach($attendances as $attendance)
            <tr>
                <td>{{ $attendance->event->start_time->format('d/m/Y') }}</td>
                <td>{{ $attendance->event->start_time->format('H:i') }}</td>
                <td class="{{ $attendance->is_present ? 'present' : 'absent' }}">
                    {{ $attendance->is_present ? '✓ Presente' : '✗ Assente' }}
                </td>
                <td>{{ $attendance->reason ?? '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <p style="text-align: center; padding: 20px; color: #6B7280; font-style: italic;">
        Nessuna presenza registrata per questo periodo.
    </p>
    @endif

    <div class="footer">
        <p>Documento generato il {{ now()->format('d/m/Y H:i') }} - San Vincenzo Calcio</p>
    </div>
</body>
</html>

