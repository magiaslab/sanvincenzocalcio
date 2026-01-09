<?php

namespace App\Filament\Resources\ConvocationResource\Pages;

use App\Filament\Resources\ConvocationResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateConvocation extends CreateRecord
{
    protected static string $resource = ConvocationResource::class;

    public function mount(): void
    {
        parent::mount();
        
        // Solo super_admin, dirigente e allenatore possono creare
        if (!auth()->user()?->hasAnyRole(['super_admin', 'dirigente', 'allenatore'])) {
            abort(403, 'Non hai i permessi per creare convocazioni.');
        }
    }
}
