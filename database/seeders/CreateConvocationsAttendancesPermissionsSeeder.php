<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class CreateConvocationsAttendancesPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Crea permessi per Convocazioni
        $convocationPermissions = [
            'view_any_convocation',
            'view_convocation',
            'create_convocation',
            'update_convocation',
            'delete_convocation',
        ];

        // Crea permessi per Presenze
        $attendancePermissions = [
            'view_any_attendance',
            'view_attendance',
            'create_attendance',
            'update_attendance',
            'delete_attendance',
        ];

        foreach (array_merge($convocationPermissions, $attendancePermissions) as $permissionName) {
            Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
        }

        // Assegna i permessi al ruolo Allenatore
        $allenatore = Role::where('name', 'allenatore')->first();
        if ($allenatore) {
            $allPermissions = Permission::whereIn('name', array_merge($convocationPermissions, $attendancePermissions))->get();
            $allenatore->givePermissionTo($allPermissions);
        }
    }
}
