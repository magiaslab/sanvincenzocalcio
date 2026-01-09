<?php

namespace App\Filament\Resources\AthleteResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ConvocationsRelationManager extends RelationManager
{
    protected static string $relationship = 'convocations';
    
    protected static ?string $title = 'Convocazioni';
    protected static ?string $modelLabel = 'Convocazione';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('event_id')
                    ->label('Evento')
                    ->relationship('event', 'id', fn (Builder $query) => $query->whereIn('type', ['partita', 'torneo']))
                    ->getOptionLabelFromRecordUsing(fn ($record) => ($record->team->name ?? '') . ' - ' . ($record->title ? $record->title . ' - ' : '') . $record->start_time?->format('d/m/Y H:i'))
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

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('event.start_time')
            ->columns([
                Tables\Columns\TextColumn::make('event.team.name')
                    ->label('Squadra')
                    ->sortable(),
                Tables\Columns\TextColumn::make('event.title')
                    ->label('Titolo Partita')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('event.start_time')
                    ->label('Data Evento')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
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
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->visible(fn () => auth()->user()?->hasAnyRole(['super_admin', 'dirigente', 'allenatore']) ?? false),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn () => auth()->user()?->hasAnyRole(['super_admin', 'dirigente', 'allenatore']) ?? false),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn () => auth()->user()?->hasAnyRole(['super_admin', 'dirigente', 'allenatore']) ?? false),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ])
                    ->visible(fn () => auth()->user()?->hasAnyRole(['super_admin', 'dirigente', 'allenatore']) ?? false),
            ]);
    }
}
