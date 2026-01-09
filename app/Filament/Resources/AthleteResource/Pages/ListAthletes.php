<?php

namespace App\Filament\Resources\AthleteResource\Pages;

use App\Filament\Resources\AthleteResource;
use App\Filament\Pages\ImportAthletes;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAthletes extends ListRecords
{
    protected static string $resource = AthleteResource::class;

    protected function getHeaderActions(): array
    {
        $actions = [];
        
        // Solo super_admin e dirigente possono importare
        if (auth()->user()?->hasAnyRole(['super_admin', 'dirigente'])) {
            $actions[] = Actions\Action::make('import')
                ->label('Importa da CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('info')
                ->url(ImportAthletes::getUrl());
        }
        
        // Solo super_admin, dirigente e allenatore possono creare
        if (auth()->user()?->hasAnyRole(['super_admin', 'dirigente', 'allenatore'])) {
            $actions[] = Actions\CreateAction::make();
        }
        
        return $actions;
    }
}
