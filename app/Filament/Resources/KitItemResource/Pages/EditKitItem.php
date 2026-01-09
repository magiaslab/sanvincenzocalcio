<?php

namespace App\Filament\Resources\KitItemResource\Pages;

use App\Filament\Resources\KitItemResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditKitItem extends EditRecord
{
    protected static string $resource = KitItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
