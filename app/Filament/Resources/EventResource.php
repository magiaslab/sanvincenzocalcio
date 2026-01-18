<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EventResource\Pages;
use App\Filament\Resources\EventResource\RelationManagers;
use App\Models\Event;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\Team;

class EventResource extends Resource
{
    protected static ?string $model = Event::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar';
    
    protected static ?string $modelLabel = 'Evento';
    protected static ?string $pluralModelLabel = 'Eventi';

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'dirigente', 'allenatore', 'genitore']) ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Dettagli Evento')
                    ->schema([
                        Forms\Components\Select::make('team_id')
                            ->relationship('team', 'name')
                            ->label('Squadra')
                            ->searchable()
                            ->preload()
                            ->required(),
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
                            ->live()
                            ->native(false)
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if (!in_array($state, ['partita', 'torneo'])) {
                                    $set('title', null);
                                }
                            }),
                        Forms\Components\TextInput::make('title')
                            ->label('Titolo Partita/Torneo')
                            ->placeholder('Es. Partita di Campionato vs Squadra X')
                            ->maxLength(255)
                            ->hidden(fn (Forms\Get $get) => !in_array($get('type'), ['partita', 'torneo']))
                            ->dehydrated(fn (Forms\Get $get) => in_array($get('type'), ['partita', 'torneo'])),
                        Forms\Components\Select::make('field_id')
                            ->relationship('field', 'name')
                            ->label('Campo')
                            ->searchable()
                            ->preload(),
                    ])->columns(2),
                
                Forms\Components\Section::make('Date e Orari')
                    ->schema([
                        Forms\Components\DateTimePicker::make('start_time')
                            ->label('Inizio')
                            ->required()
                            ->displayFormat('d/m/Y H:i')
                            ->native(false)
                            ->seconds(false),
                        Forms\Components\DateTimePicker::make('end_time')
                            ->label('Fine')
                            ->required()
                            ->displayFormat('d/m/Y H:i')
                            ->native(false)
                            ->seconds(false),
                    ])->columns(2),
                
                Forms\Components\Section::make('Note')
                    ->schema([
                        Forms\Components\Textarea::make('description')
                            ->label('Descrizione')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'allenamento' => 'info',
                        'partita' => 'success',
                        'torneo' => 'success',
                        'riunione' => 'warning',
                        default => 'gray',
                    })
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('team.name')
                    ->label('Squadra')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('start_time')
                    ->label('Data/Ora')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('title')
                    ->label('Titolo Partita')
                    ->searchable()
                    ->limit(30)
                    ->default('N/D')
                    ->formatStateUsing(fn ($state, $record) => $state ?: 'N/D')
                    ->visible(fn ($record) => $record && in_array($record->type ?? '', ['partita', 'torneo'])),
                Tables\Columns\TextColumn::make('field.name')
                    ->label('Campo')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('end_time')
                    ->label('Fine')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('start_time', 'desc')
            ->striped()
            ->paginated([10, 25, 50])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn () => auth()->user()?->hasAnyRole(['super_admin', 'dirigente', 'allenatore'])),
            ])
            ->recordUrl(fn ($record) => EventResource::getUrl('view', ['record' => $record]))
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ])
                    ->visible(fn () => auth()->user()?->hasAnyRole(['super_admin', 'dirigente', 'allenatore']) ?? false),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ConvocationsRelationManager::class,
            RelationManagers\AttendancesRelationManager::class,
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        
        $user = auth()->user();
        // I genitori vedono solo eventi delle squadre dei propri figli
        if ($user && $user->hasRole('genitore') && !$user->hasRole(['super_admin', 'dirigente', 'allenatore'])) {
            $athleteIds = $user->athletes()->pluck('id');
            $teamIds = \App\Models\Team::whereHas('athletes', function ($q) use ($athleteIds) {
                $q->whereIn('athletes.id', $athleteIds);
            })->pluck('id');
            
            $query->whereIn('team_id', $teamIds);
        }
        
        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEvents::route('/'),
            'create' => Pages\CreateEvent::route('/create'),
            'view' => Pages\ViewEvent::route('/{record}'),
            'edit' => Pages\EditEvent::route('/{record}/edit'),
        ];
    }
}
