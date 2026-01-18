<?php

namespace App\Filament\Widgets;

use App\Models\Event;
use App\Models\Team;
use App\Models\User;
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;
use Saade\FilamentFullCalendar\Data\EventData;
use Filament\Forms\Form;
use Filament\Forms;
use Illuminate\Database\Eloquent\Model;
use Saade\FilamentFullCalendar\Actions;
use Illuminate\Database\Eloquent\Builder;

class CalendarWidget extends FullCalendarWidget
{
    protected static ?int $sort = 10; // Dopo i widget eventi

    public Model|string|null $model = Event::class;

    public ?int $teamFilter = null;
    public ?int $coachFilter = null;
    public ?int $athleteFilter = null;

    protected $listeners = ['update-calendar-filters' => 'updateFilters'];

    public function updateFilters(?int $teamFilter = null, ?int $coachFilter = null, ?int $athleteFilter = null): void
    {
        if ($teamFilter !== null) {
            $this->teamFilter = $teamFilter;
        }
        if ($coachFilter !== null) {
            $this->coachFilter = $coachFilter;
        }
        if ($athleteFilter !== null) {
            $this->athleteFilter = $athleteFilter;
        }
        // Forza il refresh del calendario
        $this->dispatch('refresh-calendar');
    }
    
    public function mount(?int $teamFilter = null, ?int $coachFilter = null, ?int $athleteFilter = null): void
    {
        $this->teamFilter = $teamFilter;
        $this->coachFilter = $coachFilter;
        $this->athleteFilter = $athleteFilter;
    }


    public function fetchEvents(array $fetchInfo): array
    {
        $query = Event::query()
            ->where('start_time', '>=', $fetchInfo['start'])
            ->where('end_time', '<=', $fetchInfo['end'])
            ->with(['team']);

        // Filtro per squadra
        if ($this->teamFilter) {
            $query->where('team_id', $this->teamFilter);
        }

        // Filtro per allenatore
        if ($this->coachFilter) {
            $query->whereHas('team', function (Builder $q) {
                $q->where('coach_id', $this->coachFilter);
            });
        }

        // Filtro per atleta (per genitori)
        if ($this->athleteFilter) {
            $query->whereHas('team', function (Builder $q) {
                $q->whereHas('athletes', function (Builder $aq) {
                    $aq->where('athletes.id', $this->athleteFilter);
                });
            });
        }

        return $query->get()
                ->map(
                fn (Event $event) => EventData::make()
                    ->id($event->id)
                    ->title(($event->title ? $event->title . ' - ' : '') . ($event->team->name ?? 'N/D') . ' - ' . match($event->type) {
                        'allenamento' => 'Allenamento',
                        'partita' => 'Partita',
                        'torneo' => 'Torneo',
                        'riunione' => 'Riunione',
                        default => ucfirst($event->type),
                    })
                    ->start($event->start_time)
                    ->end($event->end_time)
                    ->url(\App\Filament\Resources\EventResource::getUrl('view', ['record' => $event]))
                    ->backgroundColor(match($event->type) {
                        'allenamento' => '#3b82f6', // blue
                        'partita' => '#10b981', // green
                        'torneo' => '#10b981', // green
                        'riunione' => '#f59e0b', // amber
                        default => '#6b7280',
                    })
                )
            ->toArray();
    }

    protected function headerActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->mountUsing(
                    function (Form $form, array $arguments) {
                        $form->fill([
                            'start_time' => $arguments['start'] ?? null,
                            'end_time' => $arguments['end'] ?? null,
                        ]);
                    }
                ),
        ];
    }

    protected function modalActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    public function getFormSchema(): array
    {
        return [
            Forms\Components\Select::make('team_id')
                ->relationship('team', 'name')
                ->label('Squadra')
                ->searchable()
                ->preload()
                ->required(),
            Forms\Components\Select::make('field_id')
                ->relationship('field', 'name')
                ->label('Campo')
                ->searchable()
                ->preload(),
            Forms\Components\Select::make('type')
                ->label('Tipo')
                ->options([
                    'allenamento' => 'Allenamento',
                    'partita' => 'Partita',
                    'torneo' => 'Torneo',
                    'riunione' => 'Riunione',
                ])
                ->required()
                ->default('allenamento')
                ->live(),
            Forms\Components\TextInput::make('title')
                ->label('Titolo Partita/Torneo')
                ->placeholder('Es. Partita di Campionato vs Squadra X')
                ->maxLength(255)
                ->hidden(fn (Forms\Get $get) => !in_array($get('type'), ['partita', 'torneo'])),
            Forms\Components\DateTimePicker::make('start_time')
                ->label('Inizio')
                ->required(),
            Forms\Components\DateTimePicker::make('end_time')
                ->label('Fine')
                ->required(),
            Forms\Components\Textarea::make('description')
                ->label('Descrizione')
                ->columnSpanFull(),
        ];
    }
}
