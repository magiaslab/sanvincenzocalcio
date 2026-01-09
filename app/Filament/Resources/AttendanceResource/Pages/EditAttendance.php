<?php

namespace App\Filament\Resources\AttendanceResource\Pages;

use App\Filament\Resources\AttendanceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAttendance extends EditRecord
{
    protected static string $resource = AttendanceResource::class;

    public function mount(int | string $record): void
    {
        parent::mount($record);
        
        // Solo super_admin, dirigente e allenatore possono modificare
        if (!auth()->user()?->hasAnyRole(['super_admin', 'dirigente', 'allenatore'])) {
            abort(403, 'Non hai i permessi per modificare presenze.');
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
