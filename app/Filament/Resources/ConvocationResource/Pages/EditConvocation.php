<?php

namespace App\Filament\Resources\ConvocationResource\Pages;

use App\Filament\Resources\ConvocationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditConvocation extends EditRecord
{
    protected static string $resource = ConvocationResource::class;

    public function mount(int | string $record): void
    {
        parent::mount($record);
        
        // Solo super_admin, dirigente e allenatore possono modificare
        if (!auth()->user()?->hasAnyRole(['super_admin', 'dirigente', 'allenatore'])) {
            abort(403, 'Non hai i permessi per modificare convocazioni.');
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
