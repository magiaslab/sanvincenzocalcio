<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AttendanceResource\Pages;
use App\Filament\Resources\AttendanceResource\RelationManagers;
use App\Models\Attendance;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\Event;

class AttendanceResource extends Resource
{
    protected static ?string $model = Attendance::class;

    protected static ?string $navigationIcon = 'heroicon-o-check-circle';
    
    protected static ?string $modelLabel = 'Presenza';
    protected static ?string $pluralModelLabel = 'Presenze';
    protected static ?string $navigationLabel = 'Presenze';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('event_id')
                    ->label('Evento')
                    ->relationship('event', 'id', fn (Builder $query) => $query->where('type', 'allenamento'))
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
                Forms\Components\Toggle::make('is_present')
                    ->label('Presente')
                    ->default(true)
                    ->required(),
                Forms\Components\TextInput::make('reason')
                    ->label('Motivazione Assenza')
                    ->visible(fn ($get) => !$get('is_present')),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('athlete.name')
                    ->label('Atleta')
                    ->sortable()
                    ->searchable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('event.start_time')
                    ->label('Data Evento')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_present')
                    ->label('Presente')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                Tables\Columns\TextColumn::make('event.team.name')
                    ->label('Squadra')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('reason')
                    ->label('Motivazione')
                    ->searchable()
                    ->limit(30)
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('event.start_time', 'desc')
            ->striped()
            ->paginated([10, 25, 50])
            ->filters([
                Tables\Filters\SelectFilter::make('event.team')
                    ->label('Squadra')
                    ->relationship('event.team', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\Filter::make('date_range')
                    ->form([
                        Forms\Components\DatePicker::make('start_date')
                            ->label('Data Inizio'),
                        Forms\Components\DatePicker::make('end_date')
                            ->label('Data Fine'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['start_date'],
                                fn (Builder $query, $date): Builder => $query->whereHas('event', fn ($q) => $q->where('start_time', '>=', $date)),
                            )
                            ->when(
                                $data['end_date'],
                                fn (Builder $query, $date): Builder => $query->whereHas('event', fn ($q) => $q->where('start_time', '<=', $date . ' 23:59:59')),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn () => auth()->user()?->hasAnyRole(['super_admin', 'dirigente', 'allenatore'])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
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
        // I genitori vedono solo presenze dei propri figli
        if ($user && $user->hasRole('genitore') && !$user->hasRole(['super_admin', 'dirigente', 'allenatore'])) {
            $athleteIds = $user->athletes()->pluck('id');
            $query->whereIn('athlete_id', $athleteIds);
        }
        
        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAttendances::route('/'),
            'create' => Pages\CreateAttendance::route('/create'),
            'edit' => Pages\EditAttendance::route('/{record}/edit'),
        ];
    }
}
