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

    protected function getHeaderWidgets(): array
    {
        return [
            CalendarWidget::make([
                'teamFilter' => $this->teamFilter,
                'coachFilter' => $this->coachFilter,
                'athleteFilter' => $this->athleteFilter,
            ]),
        ];
    }

    public function updatedTeamFilter(): void
    {
        $this->dispatch('update-calendar-filters', teamFilter: $this->teamFilter);
    }

    public function updatedCoachFilter(): void
    {
        $this->dispatch('update-calendar-filters', coachFilter: $this->coachFilter);
    }

    public function updatedAthleteFilter(): void
    {
        $this->dispatch('update-calendar-filters', athleteFilter: $this->athleteFilter);
    }
}
