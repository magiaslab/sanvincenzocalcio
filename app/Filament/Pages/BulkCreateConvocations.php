<?php

namespace App\Filament\Pages;

use App\Models\Athlete;
use App\Models\Convocation;
use App\Models\Event;
use App\Models\Team;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Actions\Action;

class BulkCreateConvocations extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-user-plus';
    protected static ?string $navigationLabel = 'Convocazioni Multiple';
    protected static ?string $title = 'Convocazioni Multiple';
    protected static ?string $navigationGroup = 'Operazioni Rapide';
    protected static string $view = 'filament.pages.bulk-create-convocations';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('submit')
                ->label('Crea Convocazioni')
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
                            ->label('Evento (Partita/Torneo)')
                            ->options(function ($get) {
                                $teamId = $get('team_id');
                                if (!$teamId) {
                                    return [];
                                }
                                return Event::where('team_id', $teamId)
                                    ->whereIn('type', ['partita', 'torneo'])
                                    ->get()
                                    ->mapWithKeys(fn ($event) => [
                                        $event->id => ($event->team->name ?? '') . ' - ' . ($event->title ? $event->title . ' - ' : '') . $event->start_time?->format('d/m/Y H:i')
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

                Forms\Components\Section::make('Impostazioni Convocazioni')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Stato Iniziale')
                            ->options([
                                'convocato' => 'Convocato',
                                'accettato' => 'Accettato',
                                'rifiutato' => 'Rifiutato',
                            ])
                            ->default('convocato')
                            ->required(),
                        Forms\Components\Textarea::make('notes')
                            ->label('Note (opzionale)')
                            ->columnSpanFull(),
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
        $status = $data['status'] ?? 'convocato';
        $notes = $data['notes'] ?? null;

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
            $existing = Convocation::where('event_id', $eventId)
                ->where('athlete_id', $athleteId)
                ->first();

            if ($existing) {
                $skipped++;
                continue;
            }

            Convocation::create([
                'event_id' => $eventId,
                'athlete_id' => $athleteId,
                'status' => $status,
                'notes' => $notes,
            ]);

            $created++;
        }

        $message = "Create {$created} convocazioni.";
        if ($skipped > 0) {
            $message .= " {$skipped} già esistenti.";
        }

        Notification::make()
            ->success()
            ->title('Convocazioni create')
            ->body($message)
            ->send();

        $this->form->fill([
            'team_id' => $data['team_id'],
            'event_id' => null,
            'athletes' => [],
            'status' => 'convocato',
        ]);
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'allenatore', 'dirigente']) ?? false;
    }
}

