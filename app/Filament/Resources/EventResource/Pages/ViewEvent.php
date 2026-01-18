<?php

namespace App\Filament\Resources\EventResource\Pages;

use App\Filament\Resources\EventResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;
use Barryvdh\DomPDF\Facade\Pdf;

class ViewEvent extends ViewRecord
{
    protected static string $resource = EventResource::class;

    protected function getHeaderActions(): array
    {
        $actions = [];
        
        // Solo super_admin, dirigente e allenatore possono modificare
        if (auth()->user()?->hasAnyRole(['super_admin', 'dirigente', 'allenatore'])) {
            $actions[] = Actions\EditAction::make();
        }
        
        // Esporta convocazioni PDF solo per partite/tornei e solo per chi può modificare
        if (auth()->user()?->hasAnyRole(['super_admin', 'dirigente', 'allenatore']) && 
            in_array($this->record->type ?? '', ['partita', 'torneo'])) {
            $actions[] = Actions\Action::make('export_convocations_pdf')
                ->label('Esporta Convocazioni PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->action(function () {
                    $event = $this->record;
                    
                    $event->load(['team', 'field']);
                    $convocations = $event->convocations()->with(['athlete.parent'])->get();
                    
                    $pdf = Pdf::loadView('pdf.convocations', [
                        'event' => $event,
                        'convocations' => $convocations,
                    ]);
                    
                    $filename = 'convocazioni_' . $event->team->name . '_' . $event->start_time->format('Y-m-d') . '.pdf';
                    
                    return response()->streamDownload(function () use ($pdf) {
                        echo $pdf->output();
                    }, $filename, [
                        'Content-Type' => 'application/pdf',
                    ]);
                });
        }
        
        return $actions;
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('Dettagli Evento')
                    ->schema([
                        Components\TextEntry::make('type')
                            ->label('Tipo')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'allenamento' => 'info',
                                'partita' => 'success',
                                'torneo' => 'success',
                                'riunione' => 'warning',
                                default => 'gray',
                            }),
                        Components\TextEntry::make('team.name')
                            ->label('Squadra')
                            ->badge()
                            ->color('primary'),
                        Components\TextEntry::make('title')
                            ->label('Titolo Partita/Torneo')
                            ->visible(fn ($record) => in_array($record->type, ['partita', 'torneo']))
                            ->placeholder('N/D'),
                        Components\TextEntry::make('field.name')
                            ->label('Campo')
                            ->placeholder('N/D'),
                    ])
                    ->columns(2),
                
                Components\Section::make('Date e Orari')
                    ->schema([
                        Components\TextEntry::make('start_time')
                            ->label('Inizio')
                            ->dateTime('d/m/Y H:i')
                            ->weight('bold'),
                        Components\TextEntry::make('end_time')
                            ->label('Fine')
                            ->dateTime('d/m/Y H:i'),
                        Components\TextEntry::make('duration')
                            ->label('Durata')
                            ->getStateUsing(function ($record) {
                                if ($record->start_time && $record->end_time) {
                                    $diff = $record->start_time->diff($record->end_time);
                                    $hours = $diff->h;
                                    $minutes = $diff->i;
                                    return $hours > 0 ? "{$hours}h {$minutes}m" : "{$minutes}m";
                                }
                                return 'N/D';
                            }),
                    ])
                    ->columns(3),
                
                Components\Section::make('Descrizione')
                    ->schema([
                        Components\TextEntry::make('description')
                            ->label('Note')
                            ->placeholder('Nessuna descrizione')
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($record) => !empty($record->description)),
            ]);
    }
}
