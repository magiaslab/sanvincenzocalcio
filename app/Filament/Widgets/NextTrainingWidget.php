<?php

namespace App\Filament\Widgets;

use App\Models\Event;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;

class NextTrainingWidget extends BaseWidget
{
    protected static ?int $sort = 1;
    
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
            ->where('type', 'allenamento')
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

        $nextTraining = $query->first();

        if (!$nextTraining) {
            return [
                Stat::make('Prossimo Allenamento', 'Nessun allenamento programmato')
                    ->description('Non ci sono allenamenti in programma')
                    ->icon('heroicon-o-calendar')
                    ->color('gray'),
            ];
        }

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

        return [
            Stat::make('Prossimo Allenamento', $nextTraining->team?->name ?? 'N/D')
                ->description($description)
                ->icon('heroicon-o-calendar')
                ->color($daysUntil == 0 ? 'success' : ($daysUntil <= 2 ? 'warning' : 'info'))
                ->url($nextTraining ? \App\Filament\Resources\EventResource::getUrl('index') : null),
        ];
    }
}

