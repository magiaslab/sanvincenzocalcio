<?php

namespace App\Filament\Resources\EventResource\Pages;

use App\Filament\Resources\EventResource;
use App\Filament\Widgets\CalendarWidget;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use App\Models\Team;
use App\Models\User;
use App\Models\Athlete;

class ListEvents extends ListRecords implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = EventResource::class;

    public ?int $teamFilter = null;
    public ?int $coachFilter = null;
    public ?int $athleteFilter = null;

    protected static string $view = 'filament.resources.event-resource.pages.list-events';

    public function mount(): void
    {
        parent::mount();
    }

    protected function getHeaderActions(): array
    {
        $actions = [];
        
        // Solo super_admin, dirigente e allenatore possono creare eventi
        if (auth()->user()?->hasAnyRole(['super_admin', 'dirigente', 'allenatore'])) {
            $actions[] = Actions\CreateAction::make();
        }
        
        return $actions;
    }

    // Rimossi i widget dall'header perché vengono renderizzati manualmente nella vista
    // protected function getHeaderWidgets(): array
    // {
    //     return [
    //         CalendarWidget::make([
    //             'teamFilter' => $this->teamFilter,
    //             'coachFilter' => $this->coachFilter,
    //             'athleteFilter' => $this->athleteFilter,
    //         ]),
    //     ];
    // }

    public function updatedTeamFilter(): void
    {
        $this->dispatch('update-calendar-filters', teamFilter: $this->teamFilter);
        $this->resetTable();
        // Forza l'aggiornamento del calendario
        $this->dispatch('$refresh');
    }

    public function updatedCoachFilter(): void
    {
        $this->dispatch('update-calendar-filters', coachFilter: $this->coachFilter);
        $this->resetTable();
        // Forza l'aggiornamento del calendario
        $this->dispatch('$refresh');
    }

    public function updatedAthleteFilter(): void
    {
        $this->dispatch('update-calendar-filters', athleteFilter: $this->athleteFilter);
        $this->resetTable();
        // Forza l'aggiornamento del calendario
        $this->dispatch('$refresh');
    }

    protected function getTableQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getTableQuery();
        
        // Applica filtri
        if ($this->teamFilter) {
            $query->where('team_id', $this->teamFilter);
        }
        
        if ($this->coachFilter) {
            $query->whereHas('team', function ($q) {
                $q->where('coach_id', $this->coachFilter);
            });
        }
        
        if ($this->athleteFilter) {
            $query->whereHas('team', function ($q) {
                $q->whereHas('athletes', function ($aq) {
                    $aq->where('athletes.id', $this->athleteFilter);
                });
            });
        }
        
        return $query;
    }
}
