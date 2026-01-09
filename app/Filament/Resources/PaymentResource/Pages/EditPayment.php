<?php

namespace App\Filament\Resources\PaymentResource\Pages;

use App\Filament\Resources\PaymentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPayment extends EditRecord
{
    protected static string $resource = PaymentResource::class;

    public function mount(int | string $record): void
    {
        parent::mount($record);
        
        // Solo super_admin e dirigente possono modificare
        if (!auth()->user()?->hasAnyRole(['super_admin', 'dirigente'])) {
            abort(403, 'Non hai i permessi per modificare pagamenti.');
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
