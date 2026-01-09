<?php

namespace App\Filament\Resources\AthleteResource\Pages;

use App\Filament\Resources\AthleteResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;

class EditAthlete extends EditRecord
{
    protected static string $resource = AthleteResource::class;
    
    protected ?array $teamsToSync = null;

    public function mount(int | string $record): void
    {
        parent::mount($record);
        
        $user = auth()->user();
        $athlete = $this->record;
        
        // I genitori possono modificare solo i propri figli
        if ($user && $user->hasRole('genitore') && !$user->hasAnyRole(['super_admin', 'dirigente', 'allenatore'])) {
            if ($athlete->parent_id !== $user->id) {
                abort(403, 'Non hai i permessi per modificare questo atleta.');
            }
        }
        
        // Solo super_admin, dirigente e allenatore possono modificare tutti i campi
        // I genitori possono modificare solo il certificato medico
        if ($user && !$user->hasAnyRole(['super_admin', 'dirigente', 'allenatore'])) {
            // I genitori possono modificare solo il certificato medico
            if (!$user->hasRole('genitore') || $athlete->parent_id !== $user->id) {
                abort(403, 'Non hai i permessi per modificare questo atleta.');
            }
        }
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $user = auth()->user();
        $athlete = $this->record;
        
        // Se è un genitore, può modificare solo il certificato medico
        if ($user && $user->hasRole('genitore') && !$user->hasAnyRole(['super_admin', 'dirigente', 'allenatore'])) {
            // Mantieni solo i campi che il genitore può modificare
            $allowedFields = ['medical_cert_file', 'medical_cert_expiry'];
            $data = array_intersect_key($data, array_flip($allowedFields));
        }
        
        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $user = auth()->user();
        $athlete = $this->record;
        
        // Salva temporaneamente 'teams' se presente (è una relazione many-to-many)
        $teams = $data['teams'] ?? null;
        unset($data['teams']);
        
        // Se è un genitore, può modificare solo il certificato medico
        if ($user && $user->hasRole('genitore') && !$user->hasAnyRole(['super_admin', 'dirigente', 'allenatore'])) {
            // Mantieni solo i campi che il genitore può modificare
            $allowedFields = ['medical_cert_file', 'medical_cert_expiry'];
            $data = array_intersect_key($data, array_flip($allowedFields));
            
            // Mantieni i dati esistenti per gli altri campi
            $existingData = $athlete->only(['parent_id', 'name', 'dob']);
            $data = array_merge($existingData, $data);
        }
        
        // Salva teams in una proprietà temporanea per sincronizzarlo dopo
        if ($teams !== null && $user && $user->hasAnyRole(['super_admin', 'dirigente', 'allenatore'])) {
            $this->teamsToSync = $teams;
        }
        
        return $data;
    }

    protected function afterSave(): void
    {
        parent::afterSave();
        
        // Sincronizza le squadre solo se l'utente ha i permessi
        if (isset($this->teamsToSync)) {
            $this->record->teams()->sync($this->teamsToSync);
            unset($this->teamsToSync);
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn () => auth()->user()?->hasAnyRole(['super_admin', 'dirigente']) ?? false),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('Contatti Genitore')
                    ->schema([
                        Components\TextEntry::make('parent.name')
                            ->label('Nome Genitore'),
                        Components\TextEntry::make('parent.email')
                            ->label('Email')
                            ->icon('heroicon-m-envelope'),
                        Components\TextEntry::make('parent.phone')
                            ->label('Telefono')
                            ->icon('heroicon-m-phone')
                            ->default('Non disponibile'),
                    ])
                    ->columns(3)
                    ->visible(fn ($record) => $record->parent),
            ]);
    }
}
