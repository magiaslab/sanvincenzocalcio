<?php

namespace App\Filament\Resources\AttendanceResource\Pages;

use App\Filament\Resources\AttendanceResource;
use App\Filament\Pages\BulkRegisterAttendances;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Forms;
use App\Models\Team;

class ListAttendances extends ListRecords
{
    protected static string $resource = AttendanceResource::class;

    protected function getHeaderActions(): array
    {
        $actions = [];
        
        // Solo super_admin, dirigente e allenatore possono esportare per squadra
        if (auth()->user()?->hasAnyRole(['super_admin', 'dirigente', 'allenatore'])) {
            $actions[] = Actions\Action::make('export_team_pdf')
                ->label('Esporta Presenze Squadra PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('info')
                ->form([
                    Forms\Components\Select::make('team_id')
                        ->label('Squadra')
                        ->options(function () {
                            $user = auth()->user();
                            if ($user && $user->hasRole('genitore') && !$user->hasAnyRole(['super_admin', 'dirigente', 'allenatore'])) {
                                $athleteIds = $user->athletes()->pluck('id');
                                return Team::whereHas('athletes', function ($q) use ($athleteIds) {
                                    $q->whereIn('athletes.id', $athleteIds);
                                })->pluck('name', 'id');
                            }
                            return Team::all()->pluck('name', 'id');
                        })
                        ->searchable()
                        ->preload()
                        ->required(),
                    Forms\Components\DatePicker::make('start_date')
                        ->label('Data Inizio')
                        ->default(now()->subMonths(3)->startOfMonth()),
                    Forms\Components\DatePicker::make('end_date')
                        ->label('Data Fine')
                        ->default(now()),
                ])
                ->action(function (array $data) {
                    $team = Team::findOrFail($data['team_id']);
                    $startDate = $data['start_date'] ?? null;
                    $endDate = $data['end_date'] ?? null;
                    
                    // Carica atleti della squadra
                    $athletes = $team->athletes;
                    
                    // Conta allenamenti totali nel periodo
                    $trainingsQuery = $team->events()->where('type', 'allenamento');
                    if ($startDate) {
                        $trainingsQuery->where('start_time', '>=', $startDate);
                    }
                    if ($endDate) {
                        $trainingsQuery->where('start_time', '<=', $endDate . ' 23:59:59');
                    }
                    $totalTrainings = $trainingsQuery->count();
                    
                    // Calcola presenze totali
                    $totalPresences = 0;
                    $totalAbsences = 0;
                    
                    foreach ($athletes as $athlete) {
                        $query = $athlete->attendances()
                            ->whereHas('event', function($q) use ($team, $startDate, $endDate) {
                                $q->where('team_id', $team->id)
                                  ->where('type', 'allenamento');
                                if ($startDate) {
                                    $q->where('start_time', '>=', $startDate);
                                }
                                if ($endDate) {
                                    $q->where('start_time', '<=', $endDate . ' 23:59:59');
                                }
                            });
                        
                        $totalPresences += $query->where('is_present', true)->count();
                        $totalAbsences += $query->where('is_present', false)->count();
                    }
                    
                    $pdf = Pdf::loadView('pdf.attendances-team', [
                        'team' => $team,
                        'athletes' => $athletes,
                        'totalTrainings' => $totalTrainings,
                        'totalPresences' => $totalPresences,
                        'totalAbsences' => $totalAbsences,
                        'startDate' => $startDate,
                        'endDate' => $endDate,
                    ]);
                    
                    $filename = 'presenze_squadra_' . str_replace(' ', '_', $team->name) . '_' . now()->format('Y-m-d') . '.pdf';
                    
                    return response()->streamDownload(function () use ($pdf) {
                        echo $pdf->output();
                    }, $filename, [
                        'Content-Type' => 'application/pdf',
                    ]);
                });
            
            $actions[] = Actions\Action::make('bulk_register')
                ->label('Registra Presenze Multiple')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->url(fn () => BulkRegisterAttendances::getUrl());
            
            $actions[] = Actions\CreateAction::make();
        }
        
        return $actions;
    }
}
