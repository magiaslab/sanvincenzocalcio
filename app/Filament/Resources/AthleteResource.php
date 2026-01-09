<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AthleteResource\Pages;
use App\Filament\Resources\AthleteResource\RelationManagers;
use App\Models\Athlete;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AthleteResource extends Resource
{
    protected static ?string $model = Athlete::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    
    protected static ?string $modelLabel = 'Atleta';
    protected static ?string $pluralModelLabel = 'Atleti';

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'dirigente', 'allenatore', 'genitore']) ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Dati Anagrafici')
                    ->schema([
                        Forms\Components\Select::make('parent_id')
                            ->relationship('parent', 'name')
                            ->label('Genitore')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->disabled(fn () => auth()->user()?->hasRole('genitore') && !auth()->user()?->hasAnyRole(['super_admin', 'dirigente', 'allenatore'])),
                        Forms\Components\TextInput::make('name')
                            ->label('Nome Atleta')
                            ->required()
                            ->maxLength(255)
                            ->disabled(fn () => auth()->user()?->hasRole('genitore') && !auth()->user()?->hasAnyRole(['super_admin', 'dirigente', 'allenatore'])),
                        Forms\Components\DatePicker::make('dob')
                            ->label('Data di Nascita')
                            ->required()
                            ->displayFormat('d/m/Y')
                            ->native(false)
                            ->disabled(fn () => auth()->user()?->hasRole('genitore') && !auth()->user()?->hasAnyRole(['super_admin', 'dirigente', 'allenatore'])),
                        Forms\Components\Select::make('teams')
                            ->relationship('teams', 'name')
                            ->label('Squadre')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->disabled(fn () => auth()->user()?->hasRole('genitore') && !auth()->user()?->hasAnyRole(['super_admin', 'dirigente', 'allenatore'])),
                    ])->columns(2),
                
                Forms\Components\Section::make('Certificato Medico')
                    ->schema([
                        Forms\Components\DatePicker::make('medical_cert_expiry')
                            ->label('Scadenza Certificato Medico')
                            ->displayFormat('d/m/Y')
                            ->native(false)
                            ->disabled(fn () => !auth()->user()?->hasAnyRole(['super_admin', 'genitore'])),
                        Forms\Components\FileUpload::make('medical_cert_file')
                            ->label('File Certificato')
                            ->directory('certificates')
                            ->disk('public')
                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'])
                            ->downloadable()
                            ->openable()
                            ->previewable()
                            ->maxSize(5120)
                            ->helperText('Carica il certificato medico in formato PDF o immagine (max 5MB). Superadmin e genitori possono caricare file.')
                            ->disabled(fn () => !auth()->user()?->hasAnyRole(['super_admin', 'genitore']))
                            ->visible(fn () => auth()->user()?->hasAnyRole(['super_admin', 'dirigente', 'allenatore', 'genitore']))
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Atleta')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('parent.name')
                    ->label('Genitore')
                    ->sortable()
                    ->searchable()
                    ->toggleable()
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('teams.name')
                    ->label('Squadre')
                    ->badge()
                    ->separator(',')
                    ->default('Nessuna squadra')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('dob')
                    ->label('Data Nascita')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('medical_cert_expiry')
                    ->label('Scadenza Cert.')
                    ->date('d/m/Y')
                    ->sortable()
                    ->color(fn ($state) => $state && $state->isPast() ? 'danger' : ($state && $state->isBefore(now()->addMonths(3)) ? 'warning' : 'success'))
                    ->icon(fn ($state) => $state && $state->isPast() ? 'heroicon-o-exclamation-triangle' : null)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('medical_cert_file')
                    ->label('File Cert.')
                    ->boolean()
                    ->trueIcon('heroicon-o-document-check')
                    ->falseIcon('heroicon-o-document-x-mark')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name', 'asc')
            ->striped()
            ->paginated([10, 25, 50])
            ->filters([
                Tables\Filters\SelectFilter::make('teams')
                    ->label('Squadra')
                    ->relationship('teams', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\TernaryFilter::make('medical_cert_file')
                    ->label('File Certificato')
                    ->placeholder('Tutti')
                    ->trueLabel('Con file')
                    ->falseLabel('Senza file')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('medical_cert_file'),
                        false: fn ($query) => $query->whereNull('medical_cert_file'),
                    ),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('download_cert')
                    ->label('Scarica Certificato')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('info')
                    ->url(fn (Athlete $record) => $record->medical_cert_file ? \Storage::disk('public')->url($record->medical_cert_file) : null)
                    ->openUrlInNewTab()
                    ->visible(fn (Athlete $record) => $record->medical_cert_file !== null),
                Tables\Actions\EditAction::make()
                    ->visible(function (Athlete $record) {
                        $user = auth()->user();
                        if (!$user) return false;
                        
                        // Superadmin, dirigente e allenatore possono sempre modificare
                        if ($user->hasAnyRole(['super_admin', 'dirigente', 'allenatore'])) {
                            return true;
                        }
                        
                        // I genitori possono modificare solo i propri figli
                        if ($user->hasRole('genitore')) {
                            return $record->parent_id === $user->id;
                        }
                        
                        return false;
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ])
                    ->visible(fn () => auth()->user()?->hasAnyRole(['super_admin', 'dirigente']) ?? false),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        
        $user = auth()->user();
        if ($user && $user->hasRole('genitore') && ! $user->hasRole(['super_admin', 'dirigente', 'allenatore'])) {
            $query->where('parent_id', $user->id);
        }
        
        return $query;
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\KitItemsRelationManager::class,
            RelationManagers\AttendancesRelationManager::class,
            RelationManagers\ConvocationsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAthletes::route('/'),
            'create' => Pages\CreateAthlete::route('/create'),
            'view' => Pages\ViewAthlete::route('/{record}'),
            'edit' => Pages\EditAthlete::route('/{record}/edit'),
        ];
    }
}
