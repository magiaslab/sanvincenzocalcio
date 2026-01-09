<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\AthleteStatisticsWidget;
use App\Filament\Widgets\TeamStatisticsWidget;
use App\Filament\Widgets\AttendanceChartWidget;
use Filament\Pages\Page;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use App\Models\Team;
use App\Models\Athlete;
use App\Models\Attendance;
use App\Models\Event;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class Statistics extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static string $view = 'filament.pages.statistics';
    protected static ?string $navigationLabel = 'Statistiche';
    protected static ?string $title = 'Statistiche e Reportistica';
    protected static ?int $navigationSort = 10;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'start_date' => now()->subMonths(3)->startOfMonth(),
            'end_date' => now(),
            'team_id' => null,
        ]);
    }

    public function form(Form $form): Form
    {
        $user = auth()->user();
        
        return $form
            ->schema([
                Select::make('team_id')
                    ->label('Squadra')
                    ->options(function () use ($user) {
                        $query = Team::query();
                        
                        if ($user && $user->hasRole('genitore') && !$user->hasAnyRole(['super_admin', 'dirigente', 'allenatore'])) {
                            $athleteIds = $user->athletes()->pluck('id');
                            $query->whereHas('athletes', function ($q) use ($athleteIds) {
                                $q->whereIn('athletes.id', $athleteIds);
                            });
                        } elseif ($user && $user->hasRole('allenatore') && !$user->hasAnyRole(['super_admin', 'dirigente'])) {
                            $query->where('coach_id', $user->id);
                        }
                        
                        return $query->pluck('name', 'id');
                    })
                    ->searchable()
                    ->preload()
                    ->placeholder('Tutte le squadre'),
                DatePicker::make('start_date')
                    ->label('Data Inizio')
                    ->displayFormat('d/m/Y')
                    ->native(false)
                    ->required(),
                DatePicker::make('end_date')
                    ->label('Data Fine')
                    ->displayFormat('d/m/Y')
                    ->native(false)
                    ->required(),
            ])
            ->statePath('data')
            ->columns(3);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export_pdf')
                ->label('Esporta Report PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->action('exportPdf'),
            Action::make('export_excel')
                ->label('Esporta Report Excel')
                ->icon('heroicon-o-table-cells')
                ->color('info')
                ->action('exportExcel'),
        ];
    }

    public function exportPdf()
    {
        $data = $this->form->getState();
        $startDate = Carbon::parse($data['start_date']);
        $endDate = Carbon::parse($data['end_date'])->endOfDay();
        
        $user = auth()->user();
        $statistics = $this->calculateStatistics($startDate, $endDate, $data['team_id'] ?? null);
        
        $pdf = Pdf::loadView('pdf.statistics-report', [
            'statistics' => $statistics,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'team' => $data['team_id'] ? Team::find($data['team_id']) : null,
        ]);
        
        $filename = 'report_statistiche_' . now()->format('Y-m-d') . '.pdf';
        
        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $filename, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    public function exportExcel()
    {
        $data = $this->form->getState();
        $startDate = Carbon::parse($data['start_date']);
        $endDate = Carbon::parse($data['end_date'])->endOfDay();
        
        $statistics = $this->calculateStatistics($startDate, $endDate, $data['team_id'] ?? null);
        
        // Per ora restituiamo un messaggio - l'export Excel richiede una libreria aggiuntiva
        \Filament\Notifications\Notification::make()
            ->title('Export Excel')
            ->body('La funzionalità di export Excel sarà disponibile a breve.')
            ->info()
            ->send();
    }

    protected function calculateStatistics($startDate, $endDate, $teamId = null)
    {
        $user = auth()->user();
        
        // Query base per atleti
        $athletesQuery = Athlete::query();
        
        if ($teamId) {
            $athletesQuery->whereHas('teams', function ($q) use ($teamId) {
                $q->where('teams.id', $teamId);
            });
        }
        
        if ($user && $user->hasRole('genitore') && !$user->hasAnyRole(['super_admin', 'dirigente', 'allenatore'])) {
            $athletesQuery->where('parent_id', $user->id);
        } elseif ($user && $user->hasRole('allenatore') && !$user->hasAnyRole(['super_admin', 'dirigente'])) {
            $teamIds = Team::where('coach_id', $user->id)->pluck('id');
            $athletesQuery->whereHas('teams', function ($q) use ($teamIds) {
                $q->whereIn('teams.id', $teamIds);
            });
        }
        
        $athletes = $athletesQuery->get();
        $athleteIds = $athletes->pluck('id');
        
        // Statistiche per atleta
        $athleteStats = [];
        foreach ($athletes as $athlete) {
            $attendances = $athlete->attendances()
                ->whereHas('event', function ($q) use ($startDate, $endDate, $teamId) {
                    $q->where('type', 'allenamento')
                      ->whereBetween('start_time', [$startDate, $endDate]);
                    if ($teamId) {
                        $q->where('team_id', $teamId);
                    }
                })
                ->get();
            
            $total = $attendances->count();
            $presences = $attendances->where('is_present', true)->count();
            $absences = $attendances->where('is_present', false)->count();
            $percentage = $total > 0 ? round(($presences / $total) * 100, 1) : 0;
            
            $athleteStats[] = [
                'athlete' => $athlete,
                'total' => $total,
                'presences' => $presences,
                'absences' => $absences,
                'percentage' => $percentage,
            ];
        }
        
        // Statistiche generali
        $totalTrainings = Event::where('type', 'allenamento')
            ->whereBetween('start_time', [$startDate, $endDate])
            ->when($teamId, function ($q) use ($teamId) {
                $q->where('team_id', $teamId);
            })
            ->count();
        
        $totalPresences = Attendance::whereIn('athlete_id', $athleteIds)
            ->whereHas('event', function ($q) use ($startDate, $endDate, $teamId) {
                $q->where('type', 'allenamento')
                  ->whereBetween('start_time', [$startDate, $endDate]);
                if ($teamId) {
                    $q->where('team_id', $teamId);
                }
            })
            ->where('is_present', true)
            ->count();
        
        $totalAbsences = Attendance::whereIn('athlete_id', $athleteIds)
            ->whereHas('event', function ($q) use ($startDate, $endDate, $teamId) {
                $q->where('type', 'allenamento')
                  ->whereBetween('start_time', [$startDate, $endDate]);
                if ($teamId) {
                    $q->where('team_id', $teamId);
                }
            })
            ->where('is_present', false)
            ->count();
        
        $totalAttendances = $totalPresences + $totalAbsences;
        $overallPercentage = $totalAttendances > 0 ? round(($totalPresences / $totalAttendances) * 100, 1) : 0;
        
        return [
            'athletes' => $athleteStats,
            'total_athletes' => $athletes->count(),
            'total_trainings' => $totalTrainings,
            'total_presences' => $totalPresences,
            'total_absences' => $totalAbsences,
            'overall_percentage' => $overallPercentage,
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            AthleteStatisticsWidget::class,
            TeamStatisticsWidget::class,
            AttendanceChartWidget::class,
            \App\Filament\Widgets\AthleteComparisonWidget::class,
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'dirigente', 'allenatore', 'genitore']) ?? false;
    }
}
