<?php

namespace App\Filament\Resources\TeamResource\Pages;

use App\Filament\Resources\TeamResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateTeam extends CreateRecord
{
    protected static string $resource = TeamResource::class;

    public function mount(): void
    {
        parent::mount();
        
        // Solo super_admin e dirigente possono creare
        if (!auth()->user()?->hasAnyRole(['super_admin', 'dirigente'])) {
            abort(403, 'Non hai i permessi per creare squadre.');
        }
    }
}
