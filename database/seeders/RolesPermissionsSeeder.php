<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Recupera il ruolo Allenatore
        $allenatore = Role::where('name', 'allenatore')->first();

        if ($allenatore) {
            // Rimuovi tutti i permessi esistenti per resettare
            $allenatore->syncPermissions([]);

            // Definisci i permessi consentiti
            $allowedPermissions = [
                // Dashboard
                'page_Dashboard',
                
                // Squadre (Teams) - Solo visualizzazione e gestione tecnica (es. eventi)
                'view_any_team',
                'view_team',
                
                // Eventi (Events) - Gestione completa calendario
                'view_any_event',
                'view_event',
                'create_event',
                'update_event',
                'delete_event',
                
                // Convocazioni e Presenze
                'view_any_convocation',
                'view_convocation',
                'create_convocation',
                'update_convocation',
                'delete_convocation',
                'view_any_attendance',
                'view_attendance',
                'create_attendance',
                'update_attendance',
                'delete_attendance',

                // Atleti - Visualizzazione
                'view_any_athlete',
                'view_athlete',

                // Utenti (Genitori) - Visualizzazione
                'view_any_user',
                'view_user',
            ];

            // Assegna solo i permessi che esistono nel DB
            $perms = Permission::whereIn('name', $allowedPermissions)->get();
            $allenatore->syncPermissions($perms);
        }
    }
}
