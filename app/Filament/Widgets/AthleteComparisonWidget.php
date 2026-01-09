<?php

namespace App\Filament\Widgets;

use App\Models\Athlete;
use App\Models\Attendance;
use App\Models\Event;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\DB;

class AthleteComparisonWidget extends BaseWidget
{
    protected static ?int $sort = 5;
    protected int | string | array $columnSpan = 'full';
    protected static ?string $heading = 'Confronto Statistiche Atleti';

    public function table(Table $table): Table
    {
        $user = auth()->user();
        
        $query = Athlete::query()
            ->with(['teams', 'parent']);
        
        // Filtra in base al ruolo
        if ($user && $user->hasRole('genitore') && !$user->hasAnyRole(['super_admin', 'dirigente', 'allenatore'])) {
            $query->where('parent_id', $user->id);
        } elseif ($user && $user->hasRole('allenatore') && !$user->hasAnyRole(['super_admin', 'dirigente'])) {
            $teamIds = \App\Models\Team::where('coach_id', $user->id)->pluck('id');
            $query->whereHas('teams', function ($q) use ($teamIds) {
                $q->whereIn('teams.id', $teamIds);
            });
        }
        
        return $table
            ->query($query)
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Atleta')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('teams.name')
                    ->label('Squadre')
                    ->badge()
                    ->separator(',')
                    ->default('Nessuna squadra'),
                Tables\Columns\TextColumn::make('total_trainings')
                    ->label('Allenamenti')
                    ->getStateUsing(function (Athlete $record) {
                        return $record->attendances()
                            ->whereHas('event', function ($q) {
                                $q->where('type', 'allenamento');
                            })
                            ->count();
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_presences')
                    ->label('Presenze')
                    ->getStateUsing(function (Athlete $record) {
                        return $record->attendances()
                            ->whereHas('event', function ($q) {
                                $q->where('type', 'allenamento');
                            })
                            ->where('is_present', true)
                            ->count();
                    })
                    ->color('success')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_absences')
                    ->label('Assenze')
                    ->getStateUsing(function (Athlete $record) {
                        return $record->attendances()
                            ->whereHas('event', function ($q) {
                                $q->where('type', 'allenamento');
                            })
                            ->where('is_present', false)
                            ->count();
                    })
                    ->color('danger')
                    ->sortable(),
                Tables\Columns\TextColumn::make('presence_percentage')
                    ->label('Percentuale')
                    ->getStateUsing(function (Athlete $record) {
                        $total = $record->attendances()
                            ->whereHas('event', function ($q) {
                                $q->where('type', 'allenamento');
                            })
                            ->count();
                        $presences = $record->attendances()
                            ->whereHas('event', function ($q) {
                                $q->where('type', 'allenamento');
                            })
                            ->where('is_present', true)
                            ->count();
                        return $total > 0 ? round(($presences / $total) * 100, 1) . '%' : '0%';
                    })
                    ->color(function (Athlete $record) {
                        $total = $record->attendances()
                            ->whereHas('event', function ($q) {
                                $q->where('type', 'allenamento');
                            })
                            ->count();
                        $presences = $record->attendances()
                            ->whereHas('event', function ($q) {
                                $q->where('type', 'allenamento');
                            })
                            ->where('is_present', true)
                            ->count();
                        $percentage = $total > 0 ? ($presences / $total) * 100 : 0;
                        return $percentage >= 80 ? 'success' : ($percentage >= 60 ? 'warning' : 'danger');
                    }),
            ])
            ->defaultSort('name', 'asc')
            ->striped()
            ->paginated([10, 25, 50]);
    }
}
