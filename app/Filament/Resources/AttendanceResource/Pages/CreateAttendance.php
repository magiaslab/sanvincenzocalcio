<?php

namespace App\Filament\Resources\AttendanceResource\Pages;

use App\Filament\Resources\AttendanceResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateAttendance extends CreateRecord
{
    protected static string $resource = AttendanceResource::class;

    public function mount(): void
    {
        parent::mount();
        
        // Solo super_admin, dirigente e allenatore possono creare
        if (!auth()->user()?->hasAnyRole(['super_admin', 'dirigente', 'allenatore'])) {
            abort(403, 'Non hai i permessi per creare presenze.');
        }
    }
}
