<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class VerifyPermissionsSeeder extends Seeder
{
    /**
     * Verifica e crea tutti i permessi necessari per ogni ruolo
     */
    public function run(): void
    {
        $this->command->info('🔍 Verifica Permessi e Ruoli...');

        // Definisci tutti i permessi necessari
        $allPermissions = [
            // Dashboard
            'page_Dashboard',
            'page_Statistics',
            
            // Teams
            'view_any_team',
            'view_team',
            'create_team',
            'update_team',
            'delete_team',
            
            // Athletes
            'view_any_athlete',
            'view_athlete',
            'create_athlete',
            'update_athlete',
            'delete_athlete',
            
            // Events
            'view_any_event',
            'view_event',
            'create_event',
            'update_event',
            'delete_event',
            
            // Attendances
            'view_any_attendance',
            'view_attendance',
            'create_attendance',
            'update_attendance',
            'delete_attendance',
            
            // Convocations
            'view_any_convocation',
            'view_convocation',
            'create_convocation',
            'update_convocation',
            'delete_convocation',
            
            // Users
            'view_any_user',
            'view_user',
            'create_user',
            'update_user',
            'delete_user',
            
            // Payments
            'view_any_payment',
            'view_payment',
            'create_payment',
            'update_payment',
            'delete_payment',
            
            // KitItems
            'view_any_kit_item',
            'view_kit_item',
            'create_kit_item',
            'update_kit_item',
            'delete_kit_item',
            
            // Fields
            'view_any_field',
            'view_field',
            'create_field',
            'update_field',
            'delete_field',
            
            // Roles (solo super_admin)
            'view_any_role',
            'view_role',
            'create_role',
            'update_role',
            'delete_role',
        ];

        // Crea tutti i permessi
        foreach ($allPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $this->command->info('✅ Permessi creati/verificati');

        // Definisci permessi per ogni ruolo
        $rolePermissions = [
            'super_admin' => $allPermissions, // Tutti i permessi
            
            'dirigente' => [
                'page_Dashboard',
                'page_Statistics',
                'view_any_team', 'view_team', 'create_team', 'update_team', 'delete_team',
                'view_any_athlete', 'view_athlete', 'create_athlete', 'update_athlete', 'delete_athlete',
                'view_any_event', 'view_event', 'create_event', 'update_event', 'delete_event',
                'view_any_attendance', 'view_attendance', 'create_attendance', 'update_attendance', 'delete_attendance',
                'view_any_convocation', 'view_convocation', 'create_convocation', 'update_convocation', 'delete_convocation',
                'view_any_user', 'view_user', 'create_user', 'update_user', 'delete_user',
                'view_any_payment', 'view_payment', 'create_payment', 'update_payment', 'delete_payment',
                'view_any_kit_item', 'view_kit_item', 'create_kit_item', 'update_kit_item', 'delete_kit_item',
                'view_any_field', 'view_field', 'create_field', 'update_field', 'delete_field',
            ],
            
            'allenatore' => [
                'page_Dashboard',
                'page_Statistics',
                'view_any_team', 'view_team',
                'view_any_athlete', 'view_athlete',
                'view_any_event', 'view_event', 'create_event', 'update_event', 'delete_event',
                'view_any_attendance', 'view_attendance', 'create_attendance', 'update_attendance', 'delete_attendance',
                'view_any_convocation', 'view_convocation', 'create_convocation', 'update_convocation', 'delete_convocation',
                'view_any_user', 'view_user',
            ],
            
            'genitore' => [
                'page_Dashboard',
                'page_Statistics',
                'view_any_athlete', // Solo i propri figli (filtrato da getEloquentQuery)
                'view_athlete', // Solo i propri figli
                'view_any_event', // Solo eventi delle squadre dei propri figli (filtrato)
                'view_event', // Solo eventi delle squadre dei propri figli
                'view_any_attendance', // Solo presenze dei propri figli (filtrato)
                'view_attendance', // Solo presenze dei propri figli
                'view_any_convocation', // Solo convocazioni dei propri figli (filtrato)
                'view_convocation', // Solo convocazioni dei propri figli
                'view_any_payment', // Solo pagamenti dei propri figli (filtrato)
                'view_payment', // Solo pagamenti dei propri figli
                'view_any_kit_item', // Solo kit dei propri figli (tramite RelationManager)
                'view_kit_item', // Solo kit dei propri figli
                'view_any_team', // Solo squadre dei propri figli (filtrato)
                'view_team', // Solo squadre dei propri figli
            ],
        ];

        // Assegna permessi a ogni ruolo
        foreach ($rolePermissions as $roleName => $permissions) {
            $role = Role::where('name', $roleName)->first();
            
            if (!$role) {
                $this->command->warn("⚠️  Ruolo '{$roleName}' non trovato. Creazione...");
                $role = Role::create(['name' => $roleName, 'guard_name' => 'web']);
            }

            $perms = Permission::whereIn('name', $permissions)->get();
            $role->syncPermissions($perms);
            
            $this->command->info("✅ Permessi assegnati a '{$roleName}': " . $perms->count());
        }

        $this->command->info('🎉 Verifica permessi completata!');
    }
}

