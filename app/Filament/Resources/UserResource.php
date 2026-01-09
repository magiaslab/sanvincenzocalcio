<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        
        $user = auth()->user();
        if ($user && $user->hasRole('allenatore')) {
            $query->whereHas('roles', function ($q) {
                $q->where('name', 'genitore');
            });
        }
        
        return $query;
    }

    protected static ?string $navigationIcon = 'heroicon-o-users';
    
    protected static ?string $modelLabel = 'Utente';
    protected static ?string $pluralModelLabel = 'Utenti';
    protected static ?string $navigationGroup = 'Amministrazione';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Anagrafica')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nome Completo')
                            ->required(),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true),
                        Forms\Components\TextInput::make('password')
                            ->password()
                            ->label('Password')
                            ->helperText('Lascia vuoto per generare una password casuale (solo per genitori)')
                            ->dehydrateStateUsing(function ($state, $get) {
                                if (empty($state)) {
                                    // Se è un genitore e la password è vuota, non hasharla qui
                                    // Verrà gestita in CreateUser
                                    $roles = $get('roles') ?? [];
                                    if (in_array('genitore', $roles)) {
                                        return null; // Verrà generata in CreateUser
                                    }
                                }
                                return $state ? Hash::make($state) : null;
                            })
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(function (string $context, $get): bool {
                                // Non richiedere password se è un genitore (verrà generata)
                                if ($context === 'create') {
                                    $roles = $get('roles') ?? [];
                                    return !in_array('genitore', $roles);
                                }
                                return false;
                            }),
                        Forms\Components\TextInput::make('phone')
                            ->label('Telefono')
                            ->tel(),
                        Forms\Components\TextInput::make('qualification')
                            ->label('Qualifica')
                            ->placeholder('Es. Allenatore UEFA B'),
                    ])->columns(2),
                
                Forms\Components\Section::make('Ruolo e Permessi')
                    ->schema([
                        Forms\Components\CheckboxList::make('roles')
                            ->label('Ruoli')
                            ->relationship('roles', 'name')
                            ->columns(2)
                            ->required(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->copyable()
                    ->icon('heroicon-m-envelope'),
                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Ruoli')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'super_admin' => 'danger',
                        'dirigente' => 'warning',
                        'allenatore' => 'success',
                        'genitore' => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Telefono')
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
                Tables\Filters\SelectFilter::make('roles')
                    ->label('Ruolo')
                    ->relationship('roles', 'name'),
            ])
            ->actions([
                Tables\Actions\Action::make('resend_credentials')
                    ->label('Re-invia Credenziali')
                    ->icon('heroicon-o-envelope')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading('Re-invia Credenziali')
                    ->modalDescription('Vuoi generare una nuova password temporanea e inviarla via email a questo utente?')
                    ->modalSubmitActionLabel('Invia')
                    ->action(function (User $record) {
                        $newPassword = \Illuminate\Support\Str::random(12);
                        $record->password = Hash::make($newPassword);
                        $record->save();

                        try {
                            \Illuminate\Support\Facades\Mail::to($record->email)->send(
                                new \App\Mail\ParentCredentialsMail(
                                    $record,
                                    $newPassword,
                                    url('/admin/login')
                                )
                            );

                            \Filament\Notifications\Notification::make()
                                ->success()
                                ->title('Credenziali inviate')
                                ->body("Nuova password inviata via email. Password temporanea: {$newPassword}")
                                ->persistent()
                                ->send();
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->danger()
                                ->title('Errore')
                                ->body("Errore nell'invio email. Password generata: {$newPassword}")
                                ->persistent()
                                ->send();
                        }
                    })
                    ->visible(fn (User $record) => $record->hasRole('genitore')),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            RelationManagers\AthletesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}

