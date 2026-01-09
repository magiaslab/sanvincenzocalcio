<?php

namespace App\Filament\Widgets;

use App\Models\Event;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;

class NextMatchWidget extends BaseWidget
{
    protected static ?int $sort = 2;
    
    protected int | string | array $columnSpan = [
        'md' => 6,
        'xl' => 6,
    ];

    public static function canView(): bool
    {
        return false; // Disabilitato, usare NextEventsWidget
    }

    protected function getStats(): array
    {
        $user = auth()->user();
        $query = Event::query()
            ->whereIn('type', ['partita', 'torneo'])
            ->where('start_time', '>=', now())
            ->with(['team', 'field'])
            ->orderBy('start_time', 'asc');

        // Filtra in base al ruolo
        if ($user && $user->hasRole('genitore') && !$user->hasAnyRole(['super_admin', 'dirigente', 'allenatore'])) {
            // Genitori: solo eventi delle squadre dei propri figli
            $athleteIds = $user->athletes()->pluck('id');
            $teamIds = \App\Models\Team::whereHas('athletes', function ($q) use ($athleteIds) {
                $q->whereIn('athletes.id', $athleteIds);
            })->pluck('id');
            $query->whereIn('team_id', $teamIds);
        } elseif ($user && $user->hasRole('allenatore') && !$user->hasAnyRole(['super_admin', 'dirigente'])) {
            // Allenatori: solo eventi delle proprie squadre
            $teamIds = \App\Models\Team::where('coach_id', $user->id)->pluck('id');
            $query->whereIn('team_id', $teamIds);
        }

        $nextMatch = $query->first();

        if (!$nextMatch) {
            return [
                Stat::make('Prossima Partita', 'Nessuna partita programmata')
                    ->description('Non ci sono partite in programma')
                    ->icon('heroicon-o-trophy')
                    ->color('gray'),
            ];
        }

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

        return [
            Stat::make('Prossima Partita', $title)
                ->description($description)
                ->icon('heroicon-o-trophy')
                ->color($daysUntil == 0 ? 'success' : ($daysUntil <= 2 ? 'warning' : 'danger'))
                ->url($nextMatch ? \App\Filament\Resources\EventResource::getUrl('index') : null),
        ];
    }
}

