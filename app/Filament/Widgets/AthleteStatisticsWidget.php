<?php

namespace App\Filament\Widgets;

use App\Models\Athlete;
use App\Models\Attendance;
use App\Models\Event;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class AthleteStatisticsWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        $user = auth()->user();
        $stats = [];

        // Query base per gli atleti
        $athletesQuery = Athlete::query();
        
        // Filtra in base al ruolo
        if ($user && $user->hasRole('genitore') && !$user->hasAnyRole(['super_admin', 'dirigente', 'allenatore'])) {
            $athletesQuery->where('parent_id', $user->id);
        } elseif ($user && $user->hasRole('allenatore') && !$user->hasAnyRole(['super_admin', 'dirigente'])) {
            $teamIds = \App\Models\Team::where('coach_id', $user->id)->pluck('id');
            $athletesQuery->whereHas('teams', function ($q) use ($teamIds) {
                $q->whereIn('teams.id', $teamIds);
            });
        }

        $totalAthletes = $athletesQuery->count();

        if ($totalAthletes == 0) {
            return [
                Stat::make('Atleti Totali', '0')
                    ->description('Nessun atleta disponibile')
                    ->icon('heroicon-o-user-group')
                    ->color('gray'),
            ];
        }

        // Calcola statistiche presenze
        $athleteIds = $athletesQuery->pluck('id');
        
        // Presenze totali (solo allenamenti)
        $totalTrainings = Event::where('type', 'allenamento')
            ->whereHas('attendances', function ($q) use ($athleteIds) {
                $q->whereIn('athlete_id', $athleteIds);
            })
            ->count();

        $totalPresences = Attendance::whereIn('athlete_id', $athleteIds)
            ->whereHas('event', function ($q) {
                $q->where('type', 'allenamento');
            })
            ->where('is_present', true)
            ->count();

        $totalAbsences = Attendance::whereIn('athlete_id', $athleteIds)
            ->whereHas('event', function ($q) {
                $q->where('type', 'allenamento');
            })
            ->where('is_present', false)
            ->count();

        $totalAttendances = $totalPresences + $totalAbsences;
        $presencePercentage = $totalAttendances > 0 ? round(($totalPresences / $totalAttendances) * 100, 1) : 0;
        $averagePresences = $totalAthletes > 0 ? round($totalPresences / $totalAthletes, 1) : 0;

        $stats[] = Stat::make('Atleti Totali', $totalAthletes)
            ->description('Atleti registrati')
            ->icon('heroicon-o-user-group')
            ->color('info');

        $stats[] = Stat::make('Presenze Totali', $totalPresences)
            ->description("Su {$totalAttendances} allenamenti totali")
            ->icon('heroicon-o-check-circle')
            ->color('success');

        $stats[] = Stat::make('Percentuale Presenze', $presencePercentage . '%')
            ->description("Media generale")
            ->icon('heroicon-o-chart-bar')
            ->color($presencePercentage >= 80 ? 'success' : ($presencePercentage >= 60 ? 'warning' : 'danger'));

        $stats[] = Stat::make('Media Presenze/Atleta', $averagePresences)
            ->description('Presenze medie per atleta')
            ->icon('heroicon-o-calculator')
            ->color('info');

        return $stats;
    }
}
