<?php

namespace App\Filament\Resources\AthleteResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class KitItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'kitItems';
    
    protected static ?string $title = 'Kit Materiale';
    
    protected static ?string $modelLabel = 'Articolo Kit';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('size')
                    ->label('Taglia'),
                Forms\Components\Toggle::make('is_delivered')
                    ->label('Consegnato'),
                Forms\Components\Toggle::make('is_paid')
                    ->label('Pagato'),
                Forms\Components\DateTimePicker::make('delivered_at')
                    ->label('Data Consegna'),
                Forms\Components\DateTimePicker::make('paid_at')
                    ->label('Data Pagamento'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Articolo'),
                Tables\Columns\TextColumn::make('size')
                    ->label('Taglia'),
                Tables\Columns\IconColumn::make('is_delivered')
                    ->label('Consegnato')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_paid')
                    ->label('Pagato')
                    ->boolean(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->preloadRecordSelect()
                    ->form(fn (Tables\Actions\AttachAction $action): array => [
                        $action->getRecordSelect(),
                        Forms\Components\TextInput::make('size')
                            ->label('Taglia'),
                        Forms\Components\Toggle::make('is_delivered')
                            ->label('Consegnato'),
                        Forms\Components\Toggle::make('is_paid')
                            ->label('Pagato'),
                    ])
                    ->visible(fn () => auth()->user()?->hasAnyRole(['super_admin', 'dirigente']) ?? false),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn () => auth()->user()?->hasAnyRole(['super_admin', 'dirigente']) ?? false),
                Tables\Actions\DetachAction::make()
                    ->visible(fn () => auth()->user()?->hasAnyRole(['super_admin', 'dirigente']) ?? false),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make(),
                ])
                    ->visible(fn () => auth()->user()?->hasAnyRole(['super_admin', 'dirigente']) ?? false),
            ]);
    }
}
