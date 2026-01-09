<?php

namespace App\Filament\Resources\AthleteResource\Pages;

use App\Filament\Resources\AthleteResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Forms;

class ViewAthlete extends ViewRecord
{
    protected static string $resource = AthleteResource::class;

    protected function getHeaderActions(): array
    {
        $actions = [
            Actions\Action::make('export_attendances_pdf')
                ->label('Esporta Presenze PDF')
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
                    $athlete = $this->record;
                    $startDate = $data['start_date'] ?? null;
                    $endDate = $data['end_date'] ?? null;
                    
                    $query = $athlete->attendances()
                        ->whereHas('event', function($q) {
                            $q->where('type', 'allenamento');
                        })
                        ->with(['event.team']);
                    
                    if ($startDate) {
                        $query->whereHas('event', function($q) use ($startDate) {
                            $q->where('start_time', '>=', $startDate);
                        });
                    }
                    if ($endDate) {
                        $query->whereHas('event', function($q) use ($endDate) {
                            $q->where('start_time', '<=', $endDate . ' 23:59:59');
                        });
                    }
                    
                    $attendances = $query->orderBy('created_at', 'desc')->get();
                    
                    $totalTrainings = $attendances->count();
                    $totalPresences = $attendances->where('is_present', true)->count();
                    $totalAbsences = $attendances->where('is_present', false)->count();
                    $percentage = $totalTrainings > 0 ? round(($totalPresences / $totalTrainings) * 100, 1) : 0;
                    $percentageClass = $percentage >= 80 ? 'percentage-high' : ($percentage >= 60 ? 'percentage-medium' : 'percentage-low');
                    
                    $pdf = Pdf::loadView('pdf.attendances-athlete', [
                        'athlete' => $athlete,
                        'attendances' => $attendances,
                        'totalTrainings' => $totalTrainings,
                        'totalPresences' => $totalPresences,
                        'totalAbsences' => $totalAbsences,
                        'percentage' => $percentage,
                        'percentageClass' => $percentageClass,
                        'startDate' => $startDate,
                        'endDate' => $endDate,
                    ]);
                    
                    $filename = 'presenze_' . str_replace(' ', '_', $athlete->name) . '_' . now()->format('Y-m-d') . '.pdf';
                    
                    return response()->streamDownload(function () use ($pdf) {
                        echo $pdf->output();
                    }, $filename, [
                        'Content-Type' => 'application/pdf',
                    ]);
                }),
        ];
        
        // Solo super_admin, dirigente e allenatore possono modificare
        if (auth()->user()?->hasAnyRole(['super_admin', 'dirigente', 'allenatore'])) {
            $actions[] = Actions\EditAction::make();
        }
        
        return $actions;
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('Dati Anagrafici')
                    ->schema([
                        Components\TextEntry::make('name')
                            ->label('Nome Atleta')
                            ->size(Components\TextEntry\TextEntrySize::Large),
                        Components\TextEntry::make('parent.name')
                            ->label('Genitore'),
                        Components\TextEntry::make('teams.name')
                            ->label('Squadre')
                            ->badge()
                            ->separator(',')
                            ->default('Nessuna squadra'),
                        Components\TextEntry::make('dob')
                            ->label('Data di Nascita')
                            ->date('d/m/Y'),
                        Components\TextEntry::make('medical_cert_expiry')
                            ->label('Scadenza Certificato Medico')
                            ->date('d/m/Y')
                            ->color(fn ($state) => $state && $state->isPast() ? 'danger' : ($state && $state->isBefore(now()->addMonths(3)) ? 'warning' : 'success'))
                            ->icon(fn ($state) => $state && $state->isPast() ? 'heroicon-o-exclamation-triangle' : null),
                        Components\TextEntry::make('medical_cert_file')
                            ->label('File Certificato')
                            ->formatStateUsing(fn ($state) => $state ? 'File caricato' : 'Nessun file caricato')
                            ->icon(fn ($state) => $state ? 'heroicon-o-document-check' : 'heroicon-o-document-x-mark')
                            ->color(fn ($state) => $state ? 'success' : 'gray')
                            ->url(fn ($record) => $record->medical_cert_file ? \Storage::disk('public')->url($record->medical_cert_file) : null)
                            ->openUrlInNewTab()
                            ->copyable(fn ($record) => $record->medical_cert_file ? \Storage::disk('public')->url($record->medical_cert_file) : null)
                            ->copyMessage('Link copiato!'),
                    ])
                    ->columns(2),

                Components\Section::make('Contatti Genitore')
                    ->schema([
                        Components\TextEntry::make('parent.name')
                            ->label('Nome Genitore'),
                        Components\TextEntry::make('parent.email')
                            ->label('Email')
                            ->icon('heroicon-m-envelope')
                            ->copyable()
                            ->copyMessage('Email copiata!'),
                        Components\TextEntry::make('parent.phone')
                            ->label('Telefono')
                            ->icon('heroicon-m-phone')
                            ->copyable()
                            ->copyMessage('Telefono copiato!')
                            ->default('Non disponibile'),
                    ])
                    ->columns(3)
                    ->visible(fn ($record) => $record->parent),
            ]);
    }
}
