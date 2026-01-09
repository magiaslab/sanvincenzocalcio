<?php

namespace App\Filament\Resources\ConvocationResource\Pages;

use App\Filament\Resources\ConvocationResource;
use App\Filament\Pages\BulkCreateConvocations;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListConvocations extends ListRecords
{
    protected static string $resource = ConvocationResource::class;

    protected function getHeaderActions(): array
    {
        $actions = [];
        
        // Solo super_admin, dirigente e allenatore possono creare
        if (auth()->user()?->hasAnyRole(['super_admin', 'dirigente', 'allenatore'])) {
            $actions[] = Actions\Action::make('bulk_create')
                ->label('Convola Atleti Multiple')
                ->icon('heroicon-o-user-plus')
                ->color('success')
                ->url(fn () => BulkCreateConvocations::getUrl());
            
            $actions[] = Actions\CreateAction::make();
        }
        
        return $actions;
    }
}
