<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TeamResource\Pages;
use App\Filament\Resources\TeamResource\RelationManagers;
use App\Models\Team;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TeamResource extends Resource
{
    protected static ?string $model = Team::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    
    protected static ?string $modelLabel = 'Squadra';
    protected static ?string $pluralModelLabel = 'Squadre';

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'dirigente', 'allenatore', 'genitore']) ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Dettagli Squadra')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nome Squadra')
                            ->required(),
                        Forms\Components\TextInput::make('category')
                            ->label('Categoria'),
                    ])->columns(2),

                Forms\Components\Section::make('Staff Tecnico')
                    ->schema([
                        Forms\Components\Select::make('coach_id')
                            ->label('Allenatore')
                            ->relationship('coach', 'name')
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('manager_id')
                            ->label('Dirigente Accompagnatore')
                            ->relationship('manager', 'name')
                            ->searchable()
                            ->preload(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Squadra')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('category')
                    ->label('Categoria')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('coach.name')
                    ->label('Allenatore')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('manager.name')
                    ->label('Dirigente')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name', 'asc')
            ->striped()
            ->paginated([10, 25, 50])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn () => auth()->user()?->hasAnyRole(['super_admin', 'dirigente'])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ])
                    ->visible(fn () => auth()->user()?->hasAnyRole(['super_admin', 'dirigente']) ?? false),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\AthletesRelationManager::class,
            RelationManagers\EventsRelationManager::class,
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        
        $user = auth()->user();
        // I genitori vedono solo squadre dei propri figli
        if ($user && $user->hasRole('genitore') && !$user->hasRole(['super_admin', 'dirigente', 'allenatore'])) {
            $athleteIds = $user->athletes()->pluck('id');
            $query->whereHas('athletes', function ($q) use ($athleteIds) {
                $q->whereIn('athletes.id', $athleteIds);
            });
        }
        
        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTeams::route('/'),
            'create' => Pages\CreateTeam::route('/create'),
            'edit' => Pages\EditTeam::route('/{record}/edit'),
        ];
    }
}
