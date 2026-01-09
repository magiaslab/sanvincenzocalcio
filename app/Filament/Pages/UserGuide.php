<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Concerns\InteractsWithInfolists;
use Filament\Infolists\Contracts\HasInfolists;

class UserGuide extends Page implements HasInfolists
{
    use InteractsWithInfolists;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';
    protected static string $view = 'filament.pages.user-guide';
    protected static ?string $title = 'Guida Utente';
    protected static ?string $navigationLabel = 'Guida';
    protected static bool $shouldRegisterNavigation = false; // Non nel menu laterale, solo nel menu avatar

    public function infolist(Infolist $infolist): Infolist
    {
        $user = auth()->user();
        $role = $this->getUserRole($user);

        return $infolist
            ->schema([
                Section::make('Benvenuto nella Guida Utente')
                    ->description('Questa guida ti aiuterà a utilizzare tutte le funzionalità disponibili per il tuo ruolo.')
                    ->schema([
                        TextEntry::make('role')
                            ->label('Il tuo ruolo')
                            ->state($role)
                            ->badge()
                            ->color('primary'),
                    ])
                    ->columns(1),
                
                ...$this->getRoleSpecificSections($user),
            ]);
    }

    protected function getUserRole($user): string
    {
        if ($user->hasRole('super_admin')) {
            return 'Super Admin';
        } elseif ($user->hasRole('dirigente')) {
            return 'Dirigente';
        } elseif ($user->hasRole('allenatore')) {
            return 'Allenatore';
        } elseif ($user->hasRole('genitore')) {
            return 'Genitore';
        }
        return 'Utente';
    }

    protected function getRoleSpecificSections($user): array
    {
        if ($user->hasRole('super_admin')) {
            return $this->getSuperAdminSections();
        } elseif ($user->hasRole('dirigente')) {
            return $this->getDirigenteSections();
        } elseif ($user->hasRole('allenatore')) {
            return $this->getAllenatoreSections();
        } elseif ($user->hasRole('genitore')) {
            return $this->getGenitoreSections();
        }
        return [];
    }

    protected function getSuperAdminSections(): array
    {
        return [
            Section::make('Dashboard')
                ->description('La dashboard ti mostra una panoramica completa del sistema')
                ->schema([
                    TextEntry::make('dashboard_widgets')
                        ->label('Widget Disponibili')
                        ->state('• Prossimo Allenamento\n• Prossima Partita\n• Statistiche Atleti\n• Statistiche Squadre\n• Grafico Presenze\n• Certificati in Scadenza')
                        ->markdown(),
                    TextEntry::make('dashboard_actions')
                        ->label('Azioni Disponibili')
                        ->state('• Visualizza tutti gli eventi\n• Statistiche complete\n• Gestione completa di tutte le risorse')
                        ->markdown(),
                ]),
            
            Section::make('Gestione Atleti')
                ->schema([
                    TextEntry::make('athletes_create')
                        ->label('Creare un Atleta')
                        ->state('Vai su "Atleti" → "Nuovo Atleta" → Compila i dati anagrafici → Assegna genitore e squadre → Salva'),
                    TextEntry::make('athletes_edit')
                        ->label('Modificare un Atleta')
                        ->state('Vai su "Atleti" → Seleziona l\'atleta → Clicca "Modifica" → Aggiorna i dati → Salva'),
                    TextEntry::make('athletes_certificates')
                        ->label('Gestire Certificati Medici')
                        ->state('Nella modifica atleta, sezione "Certificato Medico" → Carica file PDF/immagine → Imposta data scadenza → Salva'),
                ]),
            
            Section::make('Gestione Squadre')
                ->schema([
                    TextEntry::make('teams_manage')
                        ->label('Gestire Squadre')
                        ->state('Vai su "Squadre" → Crea/Modifica squadra → Assegna allenatore e staff → Assegna atleti → Salva'),
                ]),
            
            Section::make('Gestione Eventi')
                ->schema([
                    TextEntry::make('events_create')
                        ->label('Creare un Evento')
                        ->state('Vai su "Eventi" → "Nuovo Evento" → Seleziona tipo (allenamento/partita/torneo) → Assegna squadra e campo → Imposta data/ora → Salva'),
                    TextEntry::make('events_calendar')
                        ->label('Visualizzare Calendario')
                        ->state('Vai su "Eventi" → Visualizza il calendario in basso → Usa i filtri per squadra/atleta'),
                ]),
            
            Section::make('Gestione Presenze')
                ->schema([
                    TextEntry::make('attendances_single')
                        ->label('Registrare Presenza Singola')
                        ->state('Vai su "Presenze" → "Nuova Presenza" → Seleziona atleta ed evento → Imposta stato (presente/assente) → Salva'),
                    TextEntry::make('attendances_bulk')
                        ->label('Registrare Presenze Multiple')
                        ->state('Vai su "Registra Presenze Multiple" → Seleziona evento → Seleziona atleti presenti/assenti → Salva'),
                    TextEntry::make('attendances_export')
                        ->label('Esportare Statistiche')
                        ->state('Vai su "Atleti" → Seleziona atleta → "Esporta Presenze PDF" → Scarica il report'),
                ]),
            
            Section::make('Gestione Convocazioni')
                ->schema([
                    TextEntry::make('convocations_create')
                        ->label('Creare Convocazione')
                        ->state('Vai su "Convocazioni" → "Nuova Convocazione" → Seleziona atleta ed evento → Aggiungi note → Salva'),
                    TextEntry::make('convocations_bulk')
                        ->label('Convocazioni Multiple')
                        ->state('Vai su "Convoca Atleti Multipli" → Seleziona evento → Seleziona atleti → Aggiungi note → Salva'),
                ]),
            
            Section::make('Statistiche e Reportistica')
                ->schema([
                    TextEntry::make('statistics_access')
                        ->label('Accedere alle Statistiche')
                        ->state('Vai su "Statistiche" nel menu → Usa i filtri per periodo e squadra → Visualizza widget e grafici'),
                    TextEntry::make('statistics_export')
                        ->label('Esportare Report')
                        ->state('Nella pagina Statistiche → Clicca "Esporta Report PDF" → Scarica il report completo'),
                ]),
            
            Section::make('Gestione Utenti e Permessi')
                ->schema([
                    TextEntry::make('users_manage')
                        ->label('Gestire Utenti')
                        ->state('Vai su "Utenti" → Crea/Modifica utente → Assegna ruolo → Salva'),
                    TextEntry::make('roles_manage')
                        ->label('Gestire Ruoli e Permessi')
                        ->state('Vai su "Ruoli" → Crea/Modifica ruolo → Assegna permessi → Salva'),
                ]),
            
            Section::make('Impostazioni')
                ->schema([
                    TextEntry::make('settings_general')
                        ->label('Impostazioni Generali')
                        ->state('Vai su "Impostazioni Generali" → Modifica nome sito, logo, colori → Salva'),
                ]),
        ];
    }

    protected function getDirigenteSections(): array
    {
        return [
            Section::make('Dashboard')
                ->description('La dashboard ti mostra una panoramica del sistema')
                ->schema([
                    TextEntry::make('dashboard_widgets')
                        ->label('Widget Disponibili')
                        ->state("- Prossimo Allenamento\n- Prossima Partita\n- Statistiche Atleti\n- Statistiche Squadre\n- Grafico Presenze")
                        ->markdown(),
                ]),
            
            Section::make('Gestione Atleti')
                ->schema([
                    TextEntry::make('athletes_manage')
                        ->label('Gestire Atleti')
                        ->state('Vai su "Atleti" → Crea/Modifica atleti → Assegna genitori e squadre → Gestisci certificati medici'),
                ]),
            
            Section::make('Gestione Squadre')
                ->schema([
                    TextEntry::make('teams_manage')
                        ->label('Gestire Squadre')
                        ->state('Vai su "Squadre" → Crea/Modifica squadre → Assegna allenatori e atleti'),
                ]),
            
            Section::make('Gestione Eventi')
                ->schema([
                    TextEntry::make('events_manage')
                        ->label('Gestire Eventi')
                        ->state('Vai su "Eventi" → Crea/Modifica eventi → Assegna squadre e campi → Visualizza calendario'),
                ]),
            
            Section::make('Gestione Presenze')
                ->schema([
                    TextEntry::make('attendances_manage')
                        ->label('Gestire Presenze')
                        ->state('Vai su "Presenze" → Registra presenze singole o multiple → Esporta statistiche'),
                ]),
            
            Section::make('Gestione Convocazioni')
                ->schema([
                    TextEntry::make('convocations_manage')
                        ->label('Gestire Convocazioni')
                        ->state('Vai su "Convocazioni" → Crea convocazioni singole o multiple → Gestisci stati'),
                ]),
            
            Section::make('Gestione Pagamenti')
                ->schema([
                    TextEntry::make('payments_manage')
                        ->label('Gestire Pagamenti')
                        ->state('Vai su "Pagamenti" → Registra pagamenti atleti → Gestisci quote e kit'),
                ]),
            
            Section::make('Statistiche')
                ->schema([
                    TextEntry::make('statistics_access')
                        ->label('Visualizzare Statistiche')
                        ->state('Vai su "Statistiche" → Usa i filtri → Esporta report PDF'),
                ]),
        ];
    }

    protected function getAllenatoreSections(): array
    {
        return [
            Section::make('Dashboard')
                ->description('La dashboard mostra le informazioni delle tue squadre')
                ->schema([
                    TextEntry::make('dashboard_widgets')
                        ->label('Widget Disponibili')
                        ->state("- Prossimo Allenamento\n- Prossima Partita\n- Statistiche delle tue squadre\n- Grafico Presenze")
                        ->markdown(),
                ]),
            
            Section::make('Visualizzazione Dati')
                ->schema([
                    TextEntry::make('view_teams')
                        ->label('Visualizzare Squadre')
                        ->state('Vai su "Squadre" → Visualizza solo le tue squadre'),
                    TextEntry::make('view_athletes')
                        ->label('Visualizzare Atleti')
                        ->state('Vai su "Atleti" → Visualizza solo gli atleti delle tue squadre'),
                ]),
            
            Section::make('Gestione Eventi')
                ->schema([
                    TextEntry::make('events_create')
                        ->label('Creare Eventi')
                        ->state('Vai su "Eventi" → "Nuovo Evento" → Crea allenamenti e partite per le tue squadre'),
                    TextEntry::make('events_calendar')
                        ->label('Visualizzare Calendario')
                        ->state('Vai su "Eventi" → Visualizza il calendario filtrato per le tue squadre'),
                ]),
            
            Section::make('Gestione Presenze')
                ->schema([
                    TextEntry::make('attendances_register')
                        ->label('Registrare Presenze')
                        ->state('Vai su "Presenze" o "Registra Presenze Multiple" → Registra presenze per i tuoi atleti'),
                ]),
            
            Section::make('Gestione Convocazioni')
                ->schema([
                    TextEntry::make('convocations_create')
                        ->label('Creare Convocazioni')
                        ->state('Vai su "Convocazioni" o "Convoca Atleti Multipli" → Convoca i tuoi atleti'),
                ]),
            
            Section::make('Statistiche')
                ->schema([
                    TextEntry::make('statistics_view')
                        ->label('Visualizzare Statistiche')
                        ->state('Vai su "Statistiche" → Visualizza statistiche delle tue squadre'),
                ]),
        ];
    }

    protected function getGenitoreSections(): array
    {
        return [
            Section::make('Dashboard')
                ->description('La dashboard mostra le informazioni dei tuoi figli')
                ->schema([
                    TextEntry::make('dashboard_widgets')
                        ->label('Widget Disponibili')
                        ->state("- Prossimo Allenamento\n- Prossima Partita\n- Statistiche dei tuoi figli")
                        ->markdown(),
                ]),
            
            Section::make('Visualizzazione Dati Figli')
                ->schema([
                    TextEntry::make('view_athletes')
                        ->label('Visualizzare Dati Figli')
                        ->state('Vai su "Atleti" → Visualizza solo i tuoi figli → Clicca per vedere i dettagli completi'),
                    TextEntry::make('view_teams')
                        ->label('Visualizzare Squadre')
                        ->state('Vai su "Squadre" → Visualizza solo le squadre dei tuoi figli'),
                ]),
            
            Section::make('Gestione Certificati Medici')
                ->schema([
                    TextEntry::make('certificates_upload')
                        ->label('Caricare Certificato')
                        ->state('Vai su "Atleti" → Seleziona tuo figlio → "Modifica" → Sezione "Certificato Medico" → Carica file → Imposta scadenza → Salva'),
                    TextEntry::make('certificates_view')
                        ->label('Visualizzare Certificato')
                        ->state('Nella scheda atleta, sezione "Certificato Medico" → Visualizza scadenza e scarica file'),
                ]),
            
            Section::make('Visualizzazione Eventi')
                ->schema([
                    TextEntry::make('events_view')
                        ->label('Visualizzare Eventi')
                        ->state('Vai su "Eventi" → Visualizza solo gli eventi delle squadre dei tuoi figli → Usa il calendario per navigare'),
                ]),
            
            Section::make('Visualizzazione Presenze')
                ->schema([
                    TextEntry::make('attendances_view')
                        ->label('Visualizzare Presenze')
                        ->state('Vai su "Presenze" → Visualizza solo le presenze dei tuoi figli → Esporta PDF statistiche dalla scheda atleta'),
                ]),
            
            Section::make('Visualizzazione Convocazioni')
                ->schema([
                    TextEntry::make('convocations_view')
                        ->label('Visualizzare Convocazioni')
                        ->state('Vai su "Convocazioni" → Visualizza solo le convocazioni dei tuoi figli'),
                ]),
            
            Section::make('Visualizzazione Pagamenti')
                ->schema([
                    TextEntry::make('payments_view')
                        ->label('Visualizzare Pagamenti')
                        ->state('Vai su "Pagamenti" → Visualizza solo i pagamenti dei tuoi figli'),
                ]),
            
            Section::make('Statistiche')
                ->schema([
                    TextEntry::make('statistics_view')
                        ->label('Visualizzare Statistiche')
                        ->state('Vai su "Statistiche" → Visualizza statistiche dei tuoi figli → Esporta report PDF'),
                ]),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false; // Solo nel menu avatar
    }
}
