<?php

namespace App\Filament\Resources\EventResource\Pages;

use App\Filament\Resources\EventResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Barryvdh\DomPDF\Facade\Pdf;

class EditEvent extends EditRecord
{
    protected static string $resource = EventResource::class;

    public function mount(int | string $record): void
    {
        parent::mount($record);
        
        // Solo super_admin, dirigente e allenatore possono modificare
        if (!auth()->user()?->hasAnyRole(['super_admin', 'dirigente', 'allenatore'])) {
            abort(403, 'Non hai i permessi per modificare eventi.');
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('export_convocations_pdf')
                ->label('Esporta Convocazioni PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->action(function () {
                    $event = $this->record;
                    
                    if (!in_array($event->type, ['partita', 'torneo'])) {
                        \Filament\Notifications\Notification::make()
                            ->warning()
                            ->title('Errore')
                            ->body('Le convocazioni PDF sono disponibili solo per partite e tornei.')
                            ->send();
                        return;
                    }
                    
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
                })
                ->visible(fn () => in_array($this->record->type ?? '', ['partita', 'torneo'])),
            Actions\DeleteAction::make(),
        ];
    }
}
