<?php

namespace App\Filament\Resources\AthleteResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AttendancesRelationManager extends RelationManager
{
    protected static string $relationship = 'attendances';
    
    protected static ?string $title = 'Presenze';
    protected static ?string $modelLabel = 'Presenza';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('event_id')
                    ->label('Evento')
                    ->relationship('event', 'id', fn (Builder $query) => $query->where('type', 'allenamento'))
                    ->getOptionLabelFromRecordUsing(fn ($record) => ($record->team->name ?? '') . ' - ' . ($record->title ? $record->title . ' - ' : '') . $record->start_time?->format('d/m/Y H:i'))
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

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('event.start_time')
            ->columns([
                Tables\Columns\TextColumn::make('event.team.name')
                    ->label('Squadra')
                    ->sortable(),
                Tables\Columns\TextColumn::make('event.start_time')
                    ->label('Data Evento')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_present')
                    ->label('Presente')
                    ->boolean(),
                Tables\Columns\TextColumn::make('reason')
                    ->label('Motivazione')
                    ->limit(50),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('event.team')
                    ->label('Squadra')
                    ->relationship('event.team', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\TernaryFilter::make('is_present')
                    ->label('Presenza')
                    ->placeholder('Tutti')
                    ->trueLabel('Presenti')
                    ->falseLabel('Assenti'),
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
