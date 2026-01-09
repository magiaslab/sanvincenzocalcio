<?php

namespace App\Filament\Resources\EventResource\RelationManagers;

use App\Models\Event;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Notifications\Notification;
use App\Models\Athlete;
use App\Models\Convocation;
use Barryvdh\DomPDF\Facade\Pdf;

class ConvocationsRelationManager extends RelationManager
{
    protected static string $relationship = 'convocations';
    
    protected static ?string $title = 'Convocazioni';
    protected static ?string $modelLabel = 'Convocazione';

    public static function canViewForRecord($ownerRecord, string $pageClass): bool
    {
        // Mostra solo per eventi di tipo "partita" o "torneo"
        return in_array($ownerRecord->type, ['partita', 'torneo']);
    }

    public function form(Form $form): Form
    {
        $event = $this->getOwnerRecord();
        
        return $form
            ->schema([
                Forms\Components\Select::make('athlete_id')
                    ->label('Atleta')
                    ->relationship(
                        'athlete',
                        'name',
                        fn (Builder $query) => $query->whereHas('teams', fn ($q) => $q->where('teams.id', $event->team_id))
                    )
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->name . ' (' . ($record->parent->name ?? 'N/D') . ')')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\Select::make('status')
                    ->label('Stato')
                    ->options([
                        'convocato' => 'Convocato',
                        'accettato' => 'Accettato',
                        'rifiutato' => 'Rifiutato',
                    ])
                    ->required()
                    ->default('convocato'),
                Forms\Components\Textarea::make('notes')
                    ->label('Note')
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        $event = $this->getOwnerRecord();
        
        return $table
            ->recordTitleAttribute('athlete.name')
            ->columns([
                Tables\Columns\TextColumn::make('athlete.name')
                    ->label('Atleta')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => 'Genitore: ' . ($record->athlete->parent->name ?? 'N/D')),
                Tables\Columns\TextColumn::make('event.title')
                    ->label('Titolo Partita')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Stato')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'convocato' => 'warning',
                        'accettato' => 'success',
                        'rifiutato' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('notes')
                    ->label('Note')
                    ->limit(50),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Stato')
                    ->options([
                        'convocato' => 'Convocato',
                        'accettato' => 'Accettato',
                        'rifiutato' => 'Rifiutato',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\Action::make('export_pdf')
                    ->label('Esporta PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->action(function () use ($event) {
                        $event->load(['team', 'field']);
                        $convocations = $event->convocations()->with(['athlete.parent'])->get();
                        
                        $pdf = Pdf::loadView('pdf.convocations', [
                            'event' => $event,
                            'convocations' => $convocations,
                        ]);
                        
                        $filename = 'convocazioni_' . $event->team->name . '_' . $event->start_time->format('Y-m-d') . '.pdf';
                        
                        return response()->streamDownload(function () use ($pdf) {
                            echo $pdf->output();
                        }, $filename, [
                            'Content-Type' => 'application/pdf',
                        ]);
                    })
                    ->visible(fn () => in_array($event->type, ['partita', 'torneo'])),
                Tables\Actions\CreateAction::make(),
                Tables\Actions\Action::make('convola_tutti')
                    ->label('Convola Tutti gli Atleti')
                    ->icon('heroicon-o-user-plus')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Convola Atleti')
                    ->modalDescription('Seleziona gli atleti da convocare per questo evento.')
                    ->modalSubmitActionLabel('Convola')
                    ->form([
                        Forms\Components\Select::make('athletes')
                            ->label('Seleziona Atleti')
                            ->multiple()
                            ->options(
                                Athlete::whereHas('teams', fn ($q) => $q->where('teams.id', $event->team_id))
                                    ->pluck('name', 'id')
                                    ->toArray()
                            )
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('status')
                            ->label('Stato Iniziale')
                            ->options([
                                'convocato' => 'Convocato',
                                'accettato' => 'Accettato',
                                'rifiutato' => 'Rifiutato',
                            ])
                            ->default('convocato')
                            ->required(),
                    ])
                    ->action(function (array $data) use ($event) {
                        $created = 0;
                        
                        foreach ($data['athletes'] as $athleteId) {
                            $existing = Convocation::where('event_id', $event->id)
                                ->where('athlete_id', $athleteId)
                                ->first();
                            
                            if (!$existing) {
                                Convocation::create([
                                    'event_id' => $event->id,
                                    'athlete_id' => $athleteId,
                                    'status' => $data['status'],
                                ]);
                                $created++;
                            }
                        }
                        
                        Notification::make()
                            ->success()
                            ->title('Convocazioni create')
                            ->body("Create {$created} " . ($created === 1 ? 'convocazione' : 'convocazioni') . '.')
                            ->send();
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('aggiorna_stato')
                        ->label('Aggiorna Stato')
                        ->icon('heroicon-o-arrow-path')
                        ->requiresConfirmation()
                        ->modalHeading('Aggiorna Stato Convocazioni')
                        ->modalDescription('Sei sicuro di voler aggiornare lo stato delle convocazioni selezionate?')
                        ->modalSubmitActionLabel('Aggiorna')
                        ->form([
                            Forms\Components\Select::make('status')
                                ->label('Nuovo Stato')
                                ->options([
                                    'convocato' => 'Convocato',
                                    'accettato' => 'Accettato',
                                    'rifiutato' => 'Rifiutato',
                                ])
                                ->required(),
                        ])
                        ->action(function ($records, array $data) {
                            foreach ($records as $record) {
                                $record->update(['status' => $data['status']]);
                            }
                            
                            Notification::make()
                                ->success()
                                ->title('Stati aggiornati')
                                ->send();
                        }),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
