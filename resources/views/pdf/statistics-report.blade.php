<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Report Statistiche - {{ $team ? $team->name : 'Tutte le Squadre' }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 20px;
        }
        h1 {
            color: #1f2937;
            border-bottom: 3px solid #3b82f6;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        h2 {
            color: #374151;
            margin-top: 30px;
            margin-bottom: 15px;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #d1d5db;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f3f4f6;
            font-weight: bold;
            color: #1f2937;
        }
        tr:nth-child(even) {
            background-color: #f9fafb;
        }
        .summary {
            background-color: #eff6ff;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .summary-item {
            display: inline-block;
            margin-right: 30px;
            font-weight: bold;
        }
        .percentage-high {
            color: #059669;
            font-weight: bold;
        }
        .percentage-medium {
            color: #d97706;
            font-weight: bold;
        }
        .percentage-low {
            color: #dc2626;
            font-weight: bold;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            color: #6b7280;
            font-size: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Report Statistiche Presenze</h1>
        <p>
            <strong>Periodo:</strong> {{ $startDate->format('d/m/Y') }} - {{ $endDate->format('d/m/Y') }}
            @if($team)
                <br><strong>Squadra:</strong> {{ $team->name }}
            @else
                <br><strong>Squadra:</strong> Tutte le squadre
            @endif
        </p>
        <p><strong>Generato il:</strong> {{ now()->format('d/m/Y H:i') }}</p>
    </div>

    <div class="summary">
        <h2>Riepilogo Generale</h2>
        <div class="summary-item">
            <strong>Atleti Totali:</strong> {{ $statistics['total_athletes'] }}
        </div>
        <div class="summary-item">
            <strong>Allenamenti Totali:</strong> {{ $statistics['total_trainings'] }}
        </div>
        <div class="summary-item">
            <strong>Presenze Totali:</strong> {{ $statistics['total_presences'] }}
        </div>
        <div class="summary-item">
            <strong>Assenze Totali:</strong> {{ $statistics['total_absences'] }}
        </div>
        <div class="summary-item">
            <strong>Percentuale Presenze:</strong> 
            <span class="{{ $statistics['overall_percentage'] >= 80 ? 'percentage-high' : ($statistics['overall_percentage'] >= 60 ? 'percentage-medium' : 'percentage-low') }}">
                {{ $statistics['overall_percentage'] }}%
            </span>
        </div>
    </div>

    <h2>Statistiche per Atleta</h2>
    <table>
        <thead>
            <tr>
                <th>Atleta</th>
                <th>Squadre</th>
                <th>Allenamenti</th>
                <th>Presenze</th>
                <th>Assenze</th>
                <th>Percentuale</th>
            </tr>
        </thead>
        <tbody>
            @forelse($statistics['athletes'] as $stat)
                <tr>
                    <td><strong>{{ $stat['athlete']->name }}</strong></td>
                    <td>{{ $stat['athlete']->teams->pluck('name')->join(', ') ?: 'N/D' }}</td>
                    <td>{{ $stat['total'] }}</td>
                    <td style="color: #059669; font-weight: bold;">{{ $stat['presences'] }}</td>
                    <td style="color: #dc2626;">{{ $stat['absences'] }}</td>
                    <td class="{{ $stat['percentage'] >= 80 ? 'percentage-high' : ($stat['percentage'] >= 60 ? 'percentage-medium' : 'percentage-low') }}">
                        {{ $stat['percentage'] }}%
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align: center; color: #6b7280;">
                        Nessun dato disponibile per il periodo selezionato
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <p>Report generato automaticamente dal sistema San Vincenzo Calcio</p>
    </div>
</body>
</html>
