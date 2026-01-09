<?php

namespace Database\Seeders;

use App\Models\Athlete;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AthleteSeeder extends Seeder
{
    public function run(): void
    {
        // Assicurati che il ruolo genitore esista
        if (!Role::where('name', 'genitore')->exists()) {
             Role::create(['name' => 'genitore']);
        }

        // Recupera squadre
        $primiCalci = Team::where('name', 'Primi Calci')->first();
        $esordienti = Team::where('name', 'Esordienti')->first();

        // Crea Genitore 1
        $parent1 = User::firstOrCreate(
            ['email' => 'mario.rossi@example.com'],
            [
                'name' => 'Mario Rossi',
                'password' => Hash::make('password'),
                'phone' => '3331234567',
            ]
        );
        $parent1->assignRole('genitore');

        // Atleti per Genitore 1
        Athlete::create([
            'parent_id' => $parent1->id,
            'team_id' => $primiCalci?->id,
            'name' => 'Luigi Rossi',
            'dob' => '2016-05-10',
            'medical_cert_expiry' => '2026-09-01',
        ]);

        Athlete::create([
            'parent_id' => $parent1->id,
            'team_id' => $esordienti?->id,
            'name' => 'Marco Rossi',
            'dob' => '2012-03-15',
            'medical_cert_expiry' => '2026-01-20',
        ]);

        // Crea Genitore 2
        $parent2 = User::firstOrCreate(
            ['email' => 'giulia.bianchi@example.com'],
            [
                'name' => 'Giulia Bianchi',
                'password' => Hash::make('password'),
                'phone' => '3339876543',
            ]
        );
        $parent2->assignRole('genitore');

        // Atleta per Genitore 2
        Athlete::create([
            'parent_id' => $parent2->id,
            'team_id' => $primiCalci?->id,
            'name' => 'Sofia Bianchi',
            'dob' => '2016-11-20',
            'medical_cert_expiry' => '2026-10-15',
        ]);
        
        // Genitore 3
        $parent3 = User::firstOrCreate(
            ['email' => 'luca.verdi@example.com'],
            [
                'name' => 'Luca Verdi',
                'password' => Hash::make('password'),
                'phone' => '3334567890',
            ]
        );
        $parent3->assignRole('genitore');

        Athlete::create([
            'parent_id' => $parent3->id,
            'team_id' => $esordienti?->id,
            'name' => 'Matteo Verdi',
            'dob' => '2012-07-08',
            'medical_cert_expiry' => '2025-12-31', // Scaduto o quasi
        ]);
    }
}
