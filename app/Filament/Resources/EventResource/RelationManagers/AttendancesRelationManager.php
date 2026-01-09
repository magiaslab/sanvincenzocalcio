<?php

namespace App\Filament\Resources\EventResource\RelationManagers;

use App\Models\Athlete;
use App\Models\Attendance;
use App\Models\Event;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Notifications\Notification;

class AttendancesRelationManager extends RelationManager
{
    protected static string $relationship = 'attendances';
    
    protected static ?string $title = 'Presenze';
    protected static ?string $modelLabel = 'Presenza';

    public static function canViewForRecord($ownerRecord, string $pageClass): bool
    {
        // Mostra solo per eventi di tipo "allenamento"
        return $ownerRecord->type === 'allenamento';
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
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\Toggle::make('is_present')
                    ->label('Presente')
                    ->default(true),
                Forms\Components\TextInput::make('reason')
                    ->label('Motivazione Assenza')
                    ->visible(fn ($get) => !$get('is_present')),
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
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_present')
                    ->label('Presente')
                    ->boolean(),
                Tables\Columns\TextColumn::make('reason')
                    ->label('Motivazione')
                    ->limit(50),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_present')
                    ->label('Presenza')
                    ->placeholder('Tutti')
                    ->trueLabel('Presenti')
                    ->falseLabel('Assenti'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
                Tables\Actions\Action::make('registra_tutti')
                    ->label('Registra Tutti gli Atleti')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Registra Presenze')
                    ->modalDescription('Vuoi registrare tutti gli atleti della squadra come presenti?')
                    ->modalSubmitActionLabel('Registra')
                    ->action(function () use ($event) {
                        $teamAthletes = Athlete::whereHas('teams', fn ($q) => $q->where('teams.id', $event->team_id))->get();
                        $created = 0;
                        
                        foreach ($teamAthletes as $athlete) {
                            // Crea presenza solo se non esiste già
                            $existing = Attendance::where('event_id', $event->id)
                                ->where('athlete_id', $athlete->id)
                                ->first();
                            
                            if (!$existing) {
                                Attendance::create([
                                    'event_id' => $event->id,
                                    'athlete_id' => $athlete->id,
                                    'is_present' => true,
                                ]);
                                $created++;
                            }
                        }
                        
                        Notification::make()
                            ->success()
                            ->title('Presenze registrate')
                            ->body("Registrate {$created} presenze per tutti gli atleti della squadra.")
                            ->send();
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkActionGroup::make([
                        Tables\Actions\BulkAction::make('segna_presenti')
                            ->label('Segna come Presenti')
                            ->icon('heroicon-o-check')
                            ->color('success')
                            ->action(function ($records) {
                                foreach ($records as $record) {
                                    $record->update(['is_present' => true, 'reason' => null]);
                                }
                                
                                Notification::make()
                                    ->success()
                                    ->title('Presenze aggiornate')
                                    ->send();
                            }),
                        Tables\Actions\BulkAction::make('segna_assenti')
                            ->label('Segna come Assenti')
                            ->icon('heroicon-o-x-mark')
                            ->color('danger')
                            ->action(function ($records) {
                                foreach ($records as $record) {
                                    $record->update(['is_present' => false]);
                                }
                                
                                Notification::make()
                                    ->success()
                                    ->title('Presenze aggiornate')
                                    ->send();
                            }),
                    ]),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
