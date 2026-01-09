<?php

namespace App\Filament\Widgets;

use App\Models\Attendance;
use App\Models\Event;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AttendanceChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Presenze nel Tempo';
    protected static ?int $sort = 4;
    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        $user = auth()->user();
        
        // Filtra in base al ruolo
        $query = Attendance::query()
            ->select(
                DB::raw('DATE(events.start_time) as date'),
                DB::raw('COUNT(CASE WHEN attendances.is_present = 1 THEN 1 END) as presences'),
                DB::raw('COUNT(CASE WHEN attendances.is_present = 0 THEN 1 END) as absences')
            )
            ->join('events', 'attendances.event_id', '=', 'events.id')
            ->where('events.type', 'allenamento')
            ->where('events.start_time', '>=', now()->subMonths(3))
            ->groupBy('date')
            ->orderBy('date', 'asc');

        if ($user && $user->hasRole('genitore') && !$user->hasAnyRole(['super_admin', 'dirigente', 'allenatore'])) {
            $athleteIds = $user->athletes()->pluck('id');
            $query->whereIn('attendances.athlete_id', $athleteIds);
        } elseif ($user && $user->hasRole('allenatore') && !$user->hasAnyRole(['super_admin', 'dirigente'])) {
            $teamIds = \App\Models\Team::where('coach_id', $user->id)->pluck('id');
            $query->whereIn('events.team_id', $teamIds);
        }

        $data = $query->get();

        $labels = $data->map(fn ($item) => Carbon::parse($item->date)->format('d/m/Y'))->toArray();
        $presences = $data->pluck('presences')->toArray();
        $absences = $data->pluck('absences')->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'Presenze',
                    'data' => $presences,
                    'backgroundColor' => 'rgba(34, 197, 94, 0.2)',
                    'borderColor' => 'rgba(34, 197, 94, 1)',
                    'borderWidth' => 2,
                ],
                [
                    'label' => 'Assenze',
                    'data' => $absences,
                    'backgroundColor' => 'rgba(239, 68, 68, 0.2)',
                    'borderColor' => 'rgba(239, 68, 68, 1)',
                    'borderWidth' => 2,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'stepSize' => 1,
                    ],
                ],
            ],
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                ],
            ],
        ];
    }
}
