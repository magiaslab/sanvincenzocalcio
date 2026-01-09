<?php

namespace App\Filament\Widgets;

use App\Models\Team;
use App\Models\Attendance;
use App\Models\Event;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TeamStatisticsWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected function getStats(): array
    {
        $user = auth()->user();
        $stats = [];

        // Query base per le squadre
        $teamsQuery = Team::query();
        
        // Filtra in base al ruolo
        if ($user && $user->hasRole('genitore') && !$user->hasAnyRole(['super_admin', 'dirigente', 'allenatore'])) {
            $athleteIds = $user->athletes()->pluck('id');
            $teamsQuery->whereHas('athletes', function ($q) use ($athleteIds) {
                $q->whereIn('athletes.id', $athleteIds);
            });
        } elseif ($user && $user->hasRole('allenatore') && !$user->hasAnyRole(['super_admin', 'dirigente'])) {
            $teamsQuery->where('coach_id', $user->id);
        }

        $totalTeams = $teamsQuery->count();

        if ($totalTeams == 0) {
            return [
                Stat::make('Squadre Totali', '0')
                    ->description('Nessuna squadra disponibile')
                    ->icon('heroicon-o-shield-check')
                    ->color('gray'),
            ];
        }

        $teamIds = $teamsQuery->pluck('id');

        // Calcola statistiche presenze per squadre
        $totalTrainings = Event::where('type', 'allenamento')
            ->whereIn('team_id', $teamIds)
            ->count();

        $totalPresences = Attendance::whereHas('event', function ($q) use ($teamIds) {
                $q->where('type', 'allenamento')
                  ->whereIn('team_id', $teamIds);
            })
            ->where('is_present', true)
            ->count();

        $totalAbsences = Attendance::whereHas('event', function ($q) use ($teamIds) {
                $q->where('type', 'allenamento')
                  ->whereIn('team_id', $teamIds);
            })
            ->where('is_present', false)
            ->count();

        $totalAttendances = $totalPresences + $totalAbsences;
        
        // Calcola il numero totale di atleti nelle squadre
        $totalAthletes = \App\Models\Athlete::whereHas('teams', function ($q) use ($teamIds) {
            $q->whereIn('teams.id', $teamIds);
        })->count();
        
        // Tasso di partecipazione: presenze totali / (allenamenti * atleti)
        $expectedAttendances = $totalTrainings * $totalAthletes;
        $participationRate = $expectedAttendances > 0 
            ? round(($totalAttendances / $expectedAttendances) * 100, 1) 
            : 0;

        $stats[] = Stat::make('Squadre Totali', $totalTeams)
            ->description('Squadre registrate')
            ->icon('heroicon-o-shield-check')
            ->color('info');

        $stats[] = Stat::make('Presenze Totali', $totalPresences)
            ->description("Su {$totalTrainings} allenamenti")
            ->icon('heroicon-o-check-circle')
            ->color('success');

        $stats[] = Stat::make('Tasso Partecipazione', $participationRate . '%')
            ->description('Media partecipazione')
            ->icon('heroicon-o-chart-bar')
            ->color($participationRate >= 80 ? 'success' : ($participationRate >= 60 ? 'warning' : 'danger'));

        $stats[] = Stat::make('Assenze Totali', $totalAbsences)
            ->description('Assenze registrate')
            ->icon('heroicon-o-x-circle')
            ->color('danger');

        return $stats;
    }
}
