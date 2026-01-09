<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ConvocationResource\Pages;
use App\Filament\Resources\ConvocationResource\RelationManagers;
use App\Models\Convocation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\Event;

class ConvocationResource extends Resource
{
    protected static ?string $model = Convocation::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-plus';
    
    protected static ?string $modelLabel = 'Convocazione';
    protected static ?string $pluralModelLabel = 'Convocazioni';
    protected static ?string $navigationLabel = 'Convocazioni';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('event_id')
                    ->label('Evento')
                    ->relationship('event', 'id', fn (Builder $query) => $query->whereIn('type', ['partita', 'torneo']))
                    ->getOptionLabelFromRecordUsing(fn ($record) => ($record->team->name ?? '') . ' - ' . $record->start_time?->format('d/m/Y H:i'))
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\Select::make('athlete_id')
                    ->label('Atleta')
                    ->relationship('athlete', 'name')
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
                    ->default('convocato')
                    ->required(),
                Forms\Components\Textarea::make('notes')
                    ->label('Note')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('athlete.name')
                    ->label('Atleta')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Stato')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'convocato' => 'warning',
                        'accettato' => 'success',
                        'rifiutato' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('event.start_time')
                    ->label('Data Evento')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('event.title')
                    ->label('Titolo Partita')
                    ->searchable()
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('event.team.name')
                    ->label('Squadra')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('notes')
                    ->label('Note')
                    ->searchable()
                    ->limit(30)
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creata il')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('event.start_time', 'desc')
            ->striped()
            ->paginated([10, 25, 50])
            ->recordUrl(null)
            ->filters([
                Tables\Filters\SelectFilter::make('event.team')
                    ->label('Squadra')
                    ->relationship('event.team', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Stato')
                    ->options([
                        'convocato' => 'Convocato',
                        'accettato' => 'Accettato',
                        'rifiutato' => 'Rifiutato',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn () => auth()->user()?->hasAnyRole(['super_admin', 'dirigente', 'allenatore'])),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn () => auth()->user()?->hasAnyRole(['super_admin', 'dirigente', 'allenatore'])),
            ])
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
            //
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        
        $user = auth()->user();
        // I genitori vedono solo convocazioni dei propri figli
        if ($user && $user->hasRole('genitore') && !$user->hasRole(['super_admin', 'dirigente', 'allenatore'])) {
            $athleteIds = $user->athletes()->pluck('id');
            $query->whereIn('athlete_id', $athleteIds);
        }
        
        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListConvocations::route('/'),
            'create' => Pages\CreateConvocation::route('/create'),
            'edit' => Pages\EditConvocation::route('/{record}/edit'),
        ];
    }
}
