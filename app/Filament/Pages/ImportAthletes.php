<?php

namespace App\Filament\Pages;

use App\Models\Athlete;
use App\Models\Team;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Spatie\Permission\Models\Role;

class ImportAthletes extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-down-tray';
    protected static ?string $navigationLabel = 'Importa Atleti';
    protected static ?string $title = 'Importa Atleti da CSV';
    protected static ?string $navigationGroup = 'Operazioni Rapide';
    protected static string $view = 'filament.pages.import-athletes';
    protected static ?string $slug = 'import-athletes';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('download_template')
                ->label('Scarica Template CSV')
                ->icon('heroicon-o-document-arrow-down')
                ->color('info')
                ->action(function () {
                    $templatePath = $this->generateTemplateCsv();
                    return response()->download($templatePath, 'template_import_atleti.csv', [
                        'Content-Type' => 'text/csv',
                    ])->deleteFileAfterSend(true);
                }),
            Action::make('import')
                ->label('Importa CSV')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->submit('import'),
        ];
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Importa File CSV')
                    ->description('Carica un file CSV con i dati degli atleti. Usa il template per vedere il formato richiesto.')
                    ->schema([
                        Forms\Components\FileUpload::make('csv_file')
                            ->label('File CSV')
                            ->acceptedFileTypes(['text/csv', 'text/plain', 'application/vnd.ms-excel'])
                            ->directory('imports')
                            ->required()
                            ->helperText('Il file deve essere in formato CSV con encoding UTF-8'),
                        Forms\Components\Checkbox::make('skip_duplicates')
                            ->label('Salta duplicati (basato su email genitore e nome atleta)')
                            ->default(true)
                            ->helperText('Se selezionato, gli atleti già esistenti verranno saltati'),
                        Forms\Components\Checkbox::make('create_parents')
                            ->label('Crea automaticamente i genitori se non esistono')
                            ->default(true)
                            ->helperText('Se selezionato, i genitori verranno creati automaticamente con password generata'),
                    ]),
            ])
            ->statePath('data');
    }

    public function import(): void
    {
        $data = $this->form->getState();
        $filePath = $data['csv_file'];
        $skipDuplicates = $data['skip_duplicates'] ?? true;
        $createParents = $data['create_parents'] ?? true;

        if (!Storage::exists($filePath)) {
            Notification::make()
                ->danger()
                ->title('File non trovato')
                ->body('Il file CSV non è stato trovato.')
                ->send();
            return;
        }

        $fullPath = Storage::path($filePath);
        $results = $this->processCsvFile($fullPath, $skipDuplicates, $createParents);

        $message = "Import completato:\n";
        $message .= "✓ Importati: {$results['imported']} atleti\n";
        $message .= "⊘ Saltati: {$results['skipped']} atleti\n";
        $message .= "✗ Errori: {$results['errors']} righe";

        if ($results['errors'] > 0 && !empty($results['error_messages'])) {
            $message .= "\n\nPrimi errori:\n" . implode("\n", array_slice($results['error_messages'], 0, 5));
            if (count($results['error_messages']) > 5) {
                $message .= "\n... e altri " . (count($results['error_messages']) - 5) . " errori";
            }
        }

        $notification = Notification::make()
            ->title('Import completato')
            ->body($message);

        if ($results['errors'] > 0) {
            $notification->warning();
        } else {
            $notification->success();
        }

        $notification->send();

        // Pulisci il form
        $this->form->fill();
    }

    protected function processCsvFile(string $filePath, bool $skipDuplicates, bool $createParents): array
    {
        $results = [
            'imported' => 0,
            'errors' => 0,
            'skipped' => 0,
            'error_messages' => [],
        ];

        $parentRole = Role::where('name', 'genitore')->first();
        if (!$parentRole) {
            $results['errors']++;
            $results['error_messages'][] = 'Ruolo "genitore" non trovato nel sistema';
            return $results;
        }

        $handle = fopen($filePath, 'r');
        if (!$handle) {
            $results['errors']++;
            $results['error_messages'][] = 'Impossibile aprire il file CSV';
            return $results;
        }

        // Leggi l'header
        $headers = fgetcsv($handle);
        if (!$headers) {
            fclose($handle);
            $results['errors']++;
            $results['error_messages'][] = 'File CSV vuoto o non valido';
            return $results;
        }

        // Normalizza gli header (rimuovi spazi, converti in lowercase)
        $headers = array_map(function ($header) {
            return strtolower(trim($header));
        }, $headers);

        $rowNumber = 1;
        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;
            
            // Salta righe vuote
            if (empty(array_filter($row))) {
                continue;
            }

            try {
                $data = array_combine($headers, $row);
                
                // Valida e processa i dati
                $result = $this->processAthleteRow($data, $skipDuplicates, $createParents, $parentRole, $rowNumber);
                
                if ($result['status'] === 'imported') {
                    $results['imported']++;
                } elseif ($result['status'] === 'skipped') {
                    $results['skipped']++;
                } else {
                    $results['errors']++;
                    $results['error_messages'][] = "Riga {$rowNumber}: {$result['message']}";
                }
            } catch (\Exception $e) {
                $results['errors']++;
                $results['error_messages'][] = "Riga {$rowNumber}: Errore - {$e->getMessage()}";
            }
        }

        fclose($handle);
        return $results;
    }

    protected function processAthleteRow(array $data, bool $skipDuplicates, bool $createParents, Role $parentRole, int $rowNumber): array
    {
        // Valida campi obbligatori
        $requiredFields = ['nome_atleta', 'data_nascita', 'nome_genitore', 'email_genitore'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field] ?? null)) {
                return ['status' => 'error', 'message' => "Campo obbligatorio mancante: {$field}"];
            }
        }

        $athleteName = trim($data['nome_atleta']);
        $parentEmail = trim($data['email_genitore']);
        $parentName = trim($data['nome_genitore']);

        // Verifica se l'atleta esiste già
        if ($skipDuplicates) {
            $existingAthlete = Athlete::where('name', $athleteName)
                ->whereHas('parent', function ($q) use ($parentEmail) {
                    $q->where('email', $parentEmail);
                })
                ->first();

            if ($existingAthlete) {
                return ['status' => 'skipped', 'message' => 'Atleta già esistente'];
            }
        }

        // Trova o crea il genitore
        $parent = User::where('email', $parentEmail)->first();

        if (!$parent && $createParents) {
            $parent = User::create([
                'name' => $parentName,
                'email' => $parentEmail,
                'password' => Hash::make(Str::random(12)), // Password generata
                'phone' => $data['telefono_genitore'] ?? null,
            ]);
            $parent->assignRole($parentRole);
        } elseif (!$parent) {
            return ['status' => 'error', 'message' => "Genitore non trovato: {$parentEmail}"];
        }

        // Parse data di nascita
        $dob = $this->parseDate($data['data_nascita']);
        if (!$dob) {
            return ['status' => 'error', 'message' => 'Data di nascita non valida. Usa formato YYYY-MM-DD o DD/MM/YYYY'];
        }

        // Parse scadenza certificato (opzionale)
        $certExpiry = null;
        if (!empty($data['scadenza_certificato'] ?? null)) {
            $certExpiry = $this->parseDate($data['scadenza_certificato']);
        }

        // Crea l'atleta
        $athlete = Athlete::create([
            'parent_id' => $parent->id,
            'name' => $athleteName,
            'dob' => $dob,
            'medical_cert_expiry' => $certExpiry,
            'medical_cert_file' => null,
        ]);

        // Assegna squadre (se specificate)
        if (!empty($data['squadre'] ?? null)) {
            $teamNames = array_map('trim', explode(',', $data['squadre']));
            $teamNames = array_filter($teamNames); // Rimuovi valori vuoti
            
            if (!empty($teamNames)) {
                $teamIds = Team::whereIn('name', $teamNames)->pluck('id')->toArray();
                $foundTeams = Team::whereIn('name', $teamNames)->pluck('name')->toArray();
                $missingTeams = array_diff($teamNames, $foundTeams);
                
                if (!empty($teamIds)) {
                    $athlete->teams()->sync($teamIds);
                }
                
                // Avvisa se alcune squadre non sono state trovate (ma non blocca l'import)
                if (!empty($missingTeams)) {
                    // Log warning ma continua
                }
            }
        }

        return ['status' => 'imported', 'message' => 'Atleta importato con successo'];
    }

    protected function parseDate(?string $dateString): ?Carbon
    {
        if (empty($dateString)) {
            return null;
        }

        // Prova formato YYYY-MM-DD
        try {
            return Carbon::createFromFormat('Y-m-d', trim($dateString));
        } catch (\Exception $e) {
            // Ignora
        }

        // Prova formato DD/MM/YYYY
        try {
            return Carbon::createFromFormat('d/m/Y', trim($dateString));
        } catch (\Exception $e) {
            // Ignora
        }

        // Prova formato DD-MM-YYYY
        try {
            return Carbon::createFromFormat('d-m-Y', trim($dateString));
        } catch (\Exception $e) {
            // Ignora
        }

        return null;
    }

    protected function generateTemplateCsv(): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'template_import_atleti_');
        $handle = fopen($tempFile, 'w');

        // Scrivi BOM UTF-8 per Excel
        fwrite($handle, "\xEF\xBB\xBF");

        // Scrivi header
        fputcsv($handle, [
            'Nome Atleta',
            'Data Nascita',
            'Nome Genitore',
            'Email Genitore',
            'Telefono Genitore',
            'Squadre',
            'Scadenza Certificato Medico',
        ]);

        // Scrivi righe di esempio
        fputcsv($handle, [
            'Mario Rossi',
            '2015-05-15',
            'Giuseppe Rossi',
            'giuseppe.rossi@example.com',
            '3331234567',
            'Primi Calci, Esordienti',
            '2025-12-31',
        ]);

        fputcsv($handle, [
            'Sofia Bianchi',
            '2016/03/20',
            'Maria Bianchi',
            'maria.bianchi@example.com',
            '3339876543',
            'Primi Calci',
            '',
        ]);

        fclose($handle);
        return $tempFile;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'dirigente']) ?? false;
    }
}

