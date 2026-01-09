<?php

namespace App\Filament\Widgets;

use App\Models\Athlete;
use App\Filament\Resources\AthleteResource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Filament\Notifications\Notification;

class MedicalCertificatesExpiringWidget extends BaseWidget
{
    protected static ?int $sort = 20; // Dopo il calendario

    protected int | string | array $columnSpan = 'full';

    public static function canView(): bool
    {
        return Auth::user()?->hasRole('super_admin') ?? false;
    }

    public function mount(): void
    {
        $this->checkExpiringCertificates();
    }

    protected function checkExpiringCertificates(): void
    {
        $alertDate = Carbon::now()->addDays(15);
        
        // Conta certificati scaduti
        $expiredCount = Athlete::whereNotNull('medical_cert_expiry')
            ->where('medical_cert_expiry', '<', Carbon::now())
            ->count();
        
        // Conta certificati in scadenza (entro 15 giorni ma non ancora scaduti)
        $expiringCount = Athlete::whereNotNull('medical_cert_expiry')
            ->where('medical_cert_expiry', '>=', Carbon::now())
            ->where('medical_cert_expiry', '<=', $alertDate)
            ->count();

        // Mostra notifiche
        if ($expiredCount > 0) {
            Notification::make()
                ->title('Certificati Medici Scaduti')
                ->body("Ci sono {$expiredCount} " . ($expiredCount === 1 ? 'certificato scaduto' : 'certificati scaduti') . " che richiedono attenzione immediata.")
                ->danger()
                ->persistent()
                ->send();
        }

        if ($expiringCount > 0) {
            Notification::make()
                ->title('Certificati Medici in Scadenza')
                ->body("Ci sono {$expiringCount} " . ($expiringCount === 1 ? 'certificato che scade' : 'certificati che scadono') . " nei prossimi 15 giorni.")
                ->warning()
                ->persistent()
                ->send();
        }
    }

    public function table(Table $table): Table
    {
        $alertDate = Carbon::now()->addDays(15);

        return $table
            ->query(
                Athlete::query()
                    ->whereNotNull('medical_cert_expiry')
                    ->where('medical_cert_expiry', '<=', $alertDate)
                    ->with(['parent', 'teams'])
                    ->orderBy('medical_cert_expiry', 'asc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nome Atleta')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('teams.name')
                    ->label('Squadre')
                    ->badge()
                    ->separator(',')
                    ->default('Nessuna squadra')
                    ->searchable(),
                Tables\Columns\TextColumn::make('parent.name')
                    ->label('Genitore')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('medical_cert_expiry')
                    ->label('Scadenza Certificato')
                    ->date('d/m/Y')
                    ->sortable()
                    ->color(function ($record) {
                        $expiry = $record->medical_cert_expiry;
                        if (!$expiry) {
                            return 'gray';
                        }
                        
                        $expiryDate = Carbon::parse($expiry)->startOfDay();
                        $now = Carbon::now()->startOfDay();
                        
                        if ($expiryDate->isPast()) {
                            return 'danger'; // Scaduto
                        }
                        
                        $daysUntilExpiry = (int) $now->diffInDays($expiryDate);
                        
                        if ($daysUntilExpiry <= 7) {
                            return 'danger'; // Scade entro 7 giorni
                        } elseif ($daysUntilExpiry <= 15) {
                            return 'warning'; // Scade entro 15 giorni
                        }
                        
                        return 'success';
                    })
                    ->badge()
                    ->formatStateUsing(function ($state) {
                        if (!$state) {
                            return 'N/D';
                        }
                        
                        $expiry = Carbon::parse($state)->startOfDay();
                        $now = Carbon::now()->startOfDay();
                        
                        if ($expiry->isPast()) {
                            $daysAgo = (int) $now->diffInDays($expiry);
                            return 'Scaduto (' . $daysAgo . ' ' . ($daysAgo === 1 ? 'giorno fa' : 'giorni fa') . ')';
                        } elseif ($expiry->isToday()) {
                            return 'Scade oggi';
                        } else {
                            $daysUntil = (int) $now->diffInDays($expiry);
                            return $expiry->format('d/m/Y') . ' (' . $daysUntil . ' ' . ($daysUntil === 1 ? 'giorno' : 'giorni') . ')';
                        }
                    }),
                Tables\Columns\IconColumn::make('medical_cert_file')
                    ->label('File Presente')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
            ])
            ->defaultSort('medical_cert_expiry', 'asc')
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('Visualizza Atleta')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Athlete $record): string => AthleteResource::getUrl('view', ['record' => $record]))
                    ->openUrlInNewTab(false),
            ])
            ->emptyStateHeading('Nessun certificato in scadenza')
            ->emptyStateDescription('Non ci sono certificati medici in scadenza nei prossimi 15 giorni.')
            ->emptyStateIcon('heroicon-o-shield-check');
    }

    protected static ?string $heading = 'Scadenze Certificati Medici';

    public function getHeading(): string
    {
        $alertDate = Carbon::now()->addDays(15);
        
        $expiringCount = Athlete::whereNotNull('medical_cert_expiry')
            ->where('medical_cert_expiry', '<=', $alertDate)
            ->count();

        $expiredCount = Athlete::whereNotNull('medical_cert_expiry')
            ->where('medical_cert_expiry', '<', Carbon::now())
            ->count();

        $title = 'Scadenze Certificati Medici';
        
        if ($expiredCount > 0) {
            $title .= ' - ' . $expiredCount . ' ' . ($expiredCount === 1 ? 'scaduto' : 'scaduti');
        }
        
        if ($expiringCount > 0 && $expiredCount == 0) {
            $title .= ' - ' . $expiringCount . ' in scadenza';
        }

        return $title;
    }
}

