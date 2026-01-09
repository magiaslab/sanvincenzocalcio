<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistiche Presenze - {{ $team->name }}</title>
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
        .percentage {
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
    </style>
</head>
<body>
    <div class="header">
        <h1>STATISTICHE PRESENZE</h1>
        <h2>{{ $team->name }}</h2>
    </div>

    <div class="period">
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
            <div class="summary-label">Atleti nella Squadra:</div>
            <div class="summary-value">{{ $athletes->count() }}</div>
        </div>
        <div class="summary-row">
            <div class="summary-label">Presenze Totali:</div>
            <div class="summary-value">{{ $totalPresences }}</div>
        </div>
        <div class="summary-row">
            <div class="summary-label">Assenze Totali:</div>
            <div class="summary-value">{{ $totalAbsences }}</div>
        </div>
    </div>

    <h3 style="margin-bottom: 12px; color: #1F2937; font-size: 14px;">Statistiche per Atleta</h3>
    
    <table class="stats-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Nome Atleta</th>
                <th>Presenze</th>
                <th>Assenze</th>
                <th>% Presenza</th>
            </tr>
        </thead>
        <tbody>
            @foreach($athletes as $index => $athlete)
            @php
                $athleteAttendances = $athlete->attendances()
                    ->whereHas('event', function($q) use ($team, $startDate, $endDate) {
                        $q->where('events.team_id', $team->id)
                          ->where('type', 'allenamento');
                        if ($startDate) {
                            $q->where('start_time', '>=', $startDate);
                        }
                        if ($endDate) {
                            $q->where('start_time', '<=', $endDate . ' 23:59:59');
                        }
                    })
                    ->get();
                
                $presences = $athleteAttendances->where('is_present', true)->count();
                $absences = $athleteAttendances->where('is_present', false)->count();
                $total = $presences + $absences;
                $percentage = $total > 0 ? round(($presences / $total) * 100, 1) : 0;
                $percentageClass = $percentage >= 80 ? 'percentage-high' : ($percentage >= 60 ? 'percentage-medium' : 'percentage-low');
            @endphp
            <tr>
                <td>{{ $index + 1 }}</td>
                <td><strong>{{ $athlete->name }}</strong></td>
                <td>{{ $presences }}</td>
                <td>{{ $absences }}</td>
                <td class="percentage {{ $percentageClass }}">{{ $percentage }}%</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>Documento generato il {{ now()->format('d/m/Y H:i') }} - San Vincenzo Calcio</p>
    </div>
</body>
</html>

