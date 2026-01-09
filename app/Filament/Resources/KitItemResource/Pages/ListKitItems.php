<?php

namespace App\Filament\Resources\KitItemResource\Pages;

use App\Filament\Resources\KitItemResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListKitItems extends ListRecords
{
    protected static string $resource = KitItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
