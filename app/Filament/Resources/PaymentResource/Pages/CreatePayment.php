<?php

namespace App\Filament\Resources\PaymentResource\Pages;

use App\Filament\Resources\PaymentResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePayment extends CreateRecord
{
    protected static string $resource = PaymentResource::class;

    public function mount(): void
    {
        parent::mount();
        
        // Solo super_admin e dirigente possono creare
        if (!auth()->user()?->hasAnyRole(['super_admin', 'dirigente'])) {
            abort(403, 'Non hai i permessi per creare pagamenti.');
        }
    }
}
