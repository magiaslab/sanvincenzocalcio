<?php

namespace Database\Seeders;

use App\Models\Team;
use Illuminate\Database\Seeder;

class TeamSeeder extends Seeder
{
    public function run(): void
    {
        $teams = [
            ['name' => 'Primi Calci', 'category' => 'Under 8'],
            ['name' => 'Pulcini', 'category' => 'Under 10'],
            ['name' => 'Esordienti', 'category' => 'Under 12'],
            ['name' => 'Giovanissimi', 'category' => 'Under 15'],
        ];

        foreach ($teams as $team) {
            Team::firstOrCreate($team);
        }
    }
}
