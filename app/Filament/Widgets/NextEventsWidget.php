<?php

namespace App\Filament\Widgets;

use App\Models\Event;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;

class NextEventsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $user = auth()->user();
        $stats = [];

        // Prossimo Allenamento
        $trainingQuery = Event::query()
            ->where('type', 'allenamento')
            ->where('start_time', '>=', now())
            ->with(['team', 'field'])
            ->orderBy('start_time', 'asc');

        // Filtra in base al ruolo
        if ($user && $user->hasRole('genitore') && !$user->hasAnyRole(['super_admin', 'dirigente', 'allenatore'])) {
            $athleteIds = $user->athletes()->pluck('id');
            $teamIds = \App\Models\Team::whereHas('athletes', function ($q) use ($athleteIds) {
                $q->whereIn('athletes.id', $athleteIds);
            })->pluck('id');
            $trainingQuery->whereIn('team_id', $teamIds);
        } elseif ($user && $user->hasRole('allenatore') && !$user->hasAnyRole(['super_admin', 'dirigente'])) {
            $teamIds = \App\Models\Team::where('coach_id', $user->id)->pluck('id');
            $trainingQuery->whereIn('team_id', $teamIds);
        }

        $nextTraining = $trainingQuery->first();

        if (!$nextTraining) {
            $stats[] = Stat::make('Prossimo Allenamento', 'Nessun allenamento programmato')
                ->description('Non ci sono allenamenti in programma')
                ->icon('heroicon-o-calendar')
                ->color('gray');
        } else {
            $daysUntil = now()->diffInDays($nextTraining->start_time, false);
            $formattedDate = $nextTraining->start_time->format('d/m/Y');
            $formattedTime = $nextTraining->start_time->format('H:i');
            
            $description = "{$formattedDate} alle {$formattedTime}";
            if ($nextTraining->team) {
                $description .= " - {$nextTraining->team->name}";
            }
            if ($nextTraining->field) {
                $description .= " ({$nextTraining->field->name})";
            }

            if ($daysUntil == 0) {
                $description = "Oggi alle {$formattedTime}" . ($nextTraining->team ? " - {$nextTraining->team->name}" : '');
            } elseif ($daysUntil == 1) {
                $description = "Domani alle {$formattedTime}" . ($nextTraining->team ? " - {$nextTraining->team->name}" : '');
            }

            $stats[] = Stat::make('Prossimo Allenamento', $nextTraining->team?->name ?? 'N/D')
                ->description($description)
                ->icon('heroicon-o-calendar')
                ->color($daysUntil == 0 ? 'success' : ($daysUntil <= 2 ? 'warning' : 'info'))
                ->url(\App\Filament\Resources\EventResource::getUrl('index'));
        }

        // Prossima Partita
        $matchQuery = Event::query()
            ->whereIn('type', ['partita', 'torneo'])
            ->where('start_time', '>=', now())
            ->with(['team', 'field'])
            ->orderBy('start_time', 'asc');

        // Filtra in base al ruolo
        if ($user && $user->hasRole('genitore') && !$user->hasAnyRole(['super_admin', 'dirigente', 'allenatore'])) {
            $athleteIds = $user->athletes()->pluck('id');
            $teamIds = \App\Models\Team::whereHas('athletes', function ($q) use ($athleteIds) {
                $q->whereIn('athletes.id', $athleteIds);
            })->pluck('id');
            $matchQuery->whereIn('team_id', $teamIds);
        } elseif ($user && $user->hasRole('allenatore') && !$user->hasAnyRole(['super_admin', 'dirigente'])) {
            $teamIds = \App\Models\Team::where('coach_id', $user->id)->pluck('id');
            $matchQuery->whereIn('team_id', $teamIds);
        }

        $nextMatch = $matchQuery->first();

        if (!$nextMatch) {
            $stats[] = Stat::make('Prossima Partita', 'Nessuna partita programmata')
                ->description('Non ci sono partite in programma')
                ->icon('heroicon-o-trophy')
                ->color('gray');
        } else {
            $daysUntil = now()->diffInDays($nextMatch->start_time, false);
            $formattedDate = $nextMatch->start_time->format('d/m/Y');
            $formattedTime = $nextMatch->start_time->format('H:i');
            
            $title = $nextMatch->title ?? ($nextMatch->team?->name ?? 'Partita');
            $description = "{$formattedDate} alle {$formattedTime}";
            if ($nextMatch->team && !$nextMatch->title) {
                $description .= " - {$nextMatch->team->name}";
            }
            if ($nextMatch->field) {
                $description .= " ({$nextMatch->field->name})";
            }

            if ($daysUntil == 0) {
                $description = "Oggi alle {$formattedTime}" . ($nextMatch->team ? " - {$nextMatch->team->name}" : '');
            } elseif ($daysUntil == 1) {
                $description = "Domani alle {$formattedTime}" . ($nextMatch->team ? " - {$nextMatch->team->name}" : '');
            }

            $stats[] = Stat::make('Prossima Partita', $title)
                ->description($description)
                ->icon('heroicon-o-trophy')
                ->color($daysUntil == 0 ? 'success' : ($daysUntil <= 2 ? 'warning' : 'danger'))
                ->url(\App\Filament\Resources\EventResource::getUrl('index'));
        }

        return $stats;
    }
}



