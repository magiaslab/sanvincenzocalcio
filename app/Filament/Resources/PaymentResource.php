<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentResource\Pages;
use App\Filament\Resources\PaymentResource\RelationManagers;
use App\Models\Payment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    
    protected static ?string $modelLabel = 'Pagamento';
    protected static ?string $pluralModelLabel = 'Pagamenti';

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        
        $user = auth()->user();
        // I genitori vedono solo pagamenti dei propri figli
        if ($user && $user->hasRole('genitore') && !$user->hasRole(['super_admin', 'dirigente', 'allenatore'])) {
            $athleteIds = $user->athletes()->pluck('id');
            $query->whereIn('athlete_id', $athleteIds);
        }
        
        return $query;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'dirigente', 'genitore']) ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('athlete_id')
                    ->relationship('athlete', 'name')
                    ->label('Atleta')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\TextInput::make('amount')
                    ->label('Importo')
                    ->required()
                    ->numeric()
                    ->prefix('€'),
                Forms\Components\Select::make('reason')
                    ->label('Causale')
                    ->options([
                        'quota_annuale' => 'Quota Annuale',
                        'quota_mensile' => 'Quota Mensile',
                        'kit' => 'Kit Materiale',
                        'visita_medica' => 'Visita Medica',
                        'altro' => 'Altro',
                    ])
                    ->required(),
                Forms\Components\DatePicker::make('paid_at')
                    ->label('Data Pagamento')
                    ->required()
                    ->default(now()),
                Forms\Components\Select::make('method')
                    ->label('Metodo')
                    ->options([
                        'contanti' => 'Contanti',
                        'bonifico' => 'Bonifico',
                        'pos' => 'POS',
                        'stripe' => 'Stripe',
                    ])
                    ->required()
                    ->default('contanti'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('athlete.name')
                    ->label('Atleta')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Importo')
                    ->money('EUR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('reason')
                    ->label('Causale')
                    ->searchable(),
                Tables\Columns\TextColumn::make('paid_at')
                    ->label('Data')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('method')
                    ->label('Metodo')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn () => auth()->user()?->hasAnyRole(['super_admin', 'dirigente']) ?? false),
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayments::route('/'),
            'create' => Pages\CreatePayment::route('/create'),
            'edit' => Pages\EditPayment::route('/{record}/edit'),
        ];
    }
}
