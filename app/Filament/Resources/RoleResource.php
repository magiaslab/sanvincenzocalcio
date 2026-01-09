<?php

namespace App\Filament\Resources;

use BezhanSalleh\FilamentShield\Resources\RoleResource as ShieldRoleResource;

class RoleResource extends ShieldRoleResource
{
    protected static ?string $modelLabel = 'Ruolo';
    protected static ?string $pluralModelLabel = 'Ruoli';
    protected static ?string $navigationLabel = 'Ruoli e Permessi';
    protected static ?string $navigationGroup = 'Amministrazione';

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }
}

