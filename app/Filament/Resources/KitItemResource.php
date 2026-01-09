<?php

namespace App\Filament\Resources;

use App\Filament\Resources\KitItemResource\Pages;
use App\Filament\Resources\KitItemResource\RelationManagers;
use App\Models\KitItem;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class KitItemResource extends Resource
{
    protected static ?string $model = KitItem::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';
    
    protected static ?string $modelLabel = 'Articolo Kit';
    protected static ?string $pluralModelLabel = 'Articoli Kit';

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nome Articolo')
                    ->required(),
                Forms\Components\Textarea::make('description')
                    ->label('Descrizione')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('default_price')
                    ->label('Prezzo Base')
                    ->required()
                    ->numeric()
                    ->prefix('€')
                    ->default(0),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Articolo')
                    ->searchable(),
                Tables\Columns\TextColumn::make('default_price')
                    ->label('Prezzo')
                    ->money('EUR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListKitItems::route('/'),
            'create' => Pages\CreateKitItem::route('/create'),
            'edit' => Pages\EditKitItem::route('/{record}/edit'),
        ];
    }
}
