<?php

namespace App\Filament\Pages;

use App\Models\Athlete;
use App\Models\Attendance;
use App\Models\Event;
use App\Models\Team;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Actions\Action;

class BulkRegisterAttendances extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-check-circle';
    protected static ?string $navigationLabel = 'Registra Presenze Multiple';
    protected static ?string $title = 'Registra Presenze Multiple';
    protected static ?string $navigationGroup = 'Operazioni Rapide';
    protected static string $view = 'filament.pages.bulk-register-attendances';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('submit')
                ->label('Registra Presenze')
                ->submit('submit'),
        ];
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Selezione Evento')
                    ->schema([
                        Forms\Components\Select::make('team_id')
                            ->label('Squadra')
                            ->options(Team::all()->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn ($state, Forms\Set $set) => $set('event_id', null)),
                        Forms\Components\Select::make('event_id')
                            ->label('Evento (Allenamento)')
                            ->options(function ($get) {
                                $teamId = $get('team_id');
                                if (!$teamId) {
                                    return [];
                                }
                                return Event::where('team_id', $teamId)
                                    ->where('type', 'allenamento')
                                    ->get()
                                    ->mapWithKeys(fn ($event) => [
                                        $event->id => ($event->team->name ?? '') . ' - ' . $event->start_time?->format('d/m/Y H:i')
                                    ]);
                            })
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live(),
                    ])->columns(2),

                Forms\Components\Section::make('Selezione Atleti')
                    ->schema([
                        Forms\Components\CheckboxList::make('athletes')
                            ->label('Atleti')
                            ->options(function ($get) {
                                $teamId = $get('team_id');
                                if (!$teamId) {
                                    return [];
                                }
                                return Athlete::whereHas('teams', fn ($q) => $q->where('teams.id', $teamId))
                                    ->pluck('name', 'id')
                                    ->toArray();
                            })
                            ->required()
                            ->columns(3)
                            ->gridDirection('row')
                            ->searchable(),
                    ])
                    ->visible(fn ($get) => !empty($get('team_id'))),

                Forms\Components\Section::make('Impostazioni Presenze')
                    ->schema([
                        Forms\Components\Toggle::make('all_present')
                            ->label('Segna tutti come Presenti')
                            ->default(true)
                            ->live(),
                        Forms\Components\TextInput::make('reason')
                            ->label('Motivazione Assenza (se applicabile)')
                            ->visible(fn ($get) => !$get('all_present')),
                    ])
                    ->visible(fn ($get) => !empty($get('team_id')) && !empty($get('event_id'))),
            ])
            ->statePath('data');
    }

    public function submit(): void
    {
        $data = $this->form->getState();
        $eventId = $data['event_id'];
        $athletes = $data['athletes'] ?? [];
        $allPresent = $data['all_present'] ?? true;
        $reason = $data['reason'] ?? null;

        if (empty($athletes)) {
            Notification::make()
                ->danger()
                ->title('Errore')
                ->body('Seleziona almeno un atleta.')
                ->send();
            return;
        }

        $created = 0;
        $skipped = 0;

        foreach ($athletes as $athleteId) {
            // Verifica se esiste già
            $existing = Attendance::where('event_id', $eventId)
                ->where('athlete_id', $athleteId)
                ->first();

            if ($existing) {
                $skipped++;
                continue;
            }

            Attendance::create([
                'event_id' => $eventId,
                'athlete_id' => $athleteId,
                'is_present' => $allPresent,
                'reason' => $allPresent ? null : $reason,
            ]);

            $created++;
        }

        $message = "Create {$created} presenze.";
        if ($skipped > 0) {
            $message .= " {$skipped} già esistenti.";
        }

        Notification::make()
            ->success()
            ->title('Presenze registrate')
            ->body($message)
            ->send();

        $this->form->fill([
            'team_id' => $data['team_id'],
            'event_id' => null,
            'athletes' => [],
            'all_present' => true,
        ]);
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'allenatore', 'dirigente']) ?? false;
    }
}

