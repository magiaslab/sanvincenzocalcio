<?php

namespace App\Filament\Resources\EventResource\Pages;

use App\Filament\Resources\EventResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateEvent extends CreateRecord
{
    protected static string $resource = EventResource::class;

    public function mount(): void
    {
        parent::mount();
        
        // Solo super_admin, dirigente e allenatore possono creare
        if (!auth()->user()?->hasAnyRole(['super_admin', 'dirigente', 'allenatore'])) {
            abort(403, 'Non hai i permessi per creare eventi.');
        }
    }
}
