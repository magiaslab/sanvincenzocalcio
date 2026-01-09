<?php

namespace App\Filament\Resources\TeamResource\Pages;

use App\Filament\Resources\TeamResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Forms;

class EditTeam extends EditRecord
{
    protected static string $resource = TeamResource::class;

    public function mount(int | string $record): void
    {
        parent::mount($record);
        
        // Solo super_admin e dirigente possono modificare
        if (!auth()->user()?->hasAnyRole(['super_admin', 'dirigente'])) {
            abort(403, 'Non hai i permessi per modificare squadre.');
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('export_attendances_pdf')
                ->label('Esporta Presenze Squadra PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->form([
                    Forms\Components\DatePicker::make('start_date')
                        ->label('Data Inizio')
                        ->default(now()->subMonths(3)->startOfMonth()),
                    Forms\Components\DatePicker::make('end_date')
                        ->label('Data Fine')
                        ->default(now()),
                ])
                ->action(function (array $data) {
                    $team = $this->record;
                    $startDate = $data['start_date'] ?? null;
                    $endDate = $data['end_date'] ?? null;
                    
                    // Carica atleti della squadra con le loro presenze
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
                }),
            Actions\DeleteAction::make(),
        ];
    }
}
