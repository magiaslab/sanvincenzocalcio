<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\Payment;

class AthletesRelationManager extends RelationManager
{
    protected static string $relationship = 'athletes';
    
    protected static ?string $title = 'Figli / Atleti';
    protected static ?string $modelLabel = 'Atleta';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nome Atleta'),
                Tables\Columns\TextColumn::make('teams.name')
                    ->label('Squadre')
                    ->badge()
                    ->separator(',')
                    ->default('Nessuna squadra'),
                Tables\Columns\TextColumn::make('dob')
                    ->label('Data Nascita')
                    ->date(),
                Tables\Columns\TextColumn::make('payments_sum_amount')
                    ->label('Totale Pagato')
                    ->money('EUR')
                    ->state(function ($record) {
                        return $record->payments()->sum('amount');
                    }),
                 Tables\Columns\TextColumn::make('kit_items_count')
                    ->label('Articoli Kit')
                    ->counts('kitItems'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                // Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->url(fn ($record) => route('filament.admin.resources.athletes.edit', $record)),
            ])
            ->bulkActions([
                // Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DeleteBulkAction::make(),
                // ]),
            ]);
    }
}
