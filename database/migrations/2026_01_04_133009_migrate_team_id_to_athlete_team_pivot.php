<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Migra i dati esistenti da team_id alla tabella pivot
        $athletes = DB::table('athletes')
            ->whereNotNull('team_id')
            ->get();

        foreach ($athletes as $athlete) {
            // Inserisci nella tabella pivot solo se non esiste già
            DB::table('athlete_team')->insertOrIgnore([
                'athlete_id' => $athlete->id,
                'team_id' => $athlete->team_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Non facciamo nulla nel down, manteniamo i dati nella pivot
    }
};
