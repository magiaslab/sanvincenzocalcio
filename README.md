# ⚽ San Vincenzo Calcio - Sistema di Gestione

Sistema completo di gestione per società calcistiche sviluppato con Laravel e Filament.

## 🚀 Caratteristiche Principali

- **Gestione Atleti**: CRUD completo con relazioni genitori e squadre
- **Gestione Squadre**: Assegnazione allenatori e staff tecnico
- **Gestione Eventi**: Allenamenti, partite e tornei con calendario interattivo
- **Presenze e Convocazioni**: Registrazione presenze e gestione convocazioni
- **Statistiche Avanzate**: Dashboard con grafici e reportistica PDF
- **Certificati Medici**: Upload e gestione scadenze certificati
- **Sistema RBAC**: 4 ruoli con permessi granulari (Super Admin, Dirigente, Allenatore, Genitore)
- **Export PDF**: Report statistiche e presenze
- **Ottimizzazione Mobile**: Interfaccia responsive e ottimizzata per dispositivi mobili

## 🛠️ Stack Tecnologico

- **Backend**: PHP 8.2+, Laravel 12.0
- **Frontend**: Filament 3.2, Tailwind CSS 4.0, Livewire 3.x
- **Database**: SQLite (sviluppo) / MySQL/PostgreSQL (produzione)
- **Librerie**: Spatie Permission, DomPDF, FullCalendar, Chart.js
- **Email**: Resend.com

## 📋 Requisiti

- PHP >= 8.2
- Composer
- Node.js e npm
- SQLite/MySQL/PostgreSQL

## 🔧 Installazione

1. **Clona il repository**
```bash
git clone https://github.com/TUO_USERNAME/sanvincenzocalcio.git
cd sanvincenzocalcio
```

2. **Installa le dipendenze**
```bash
composer install
npm install
```

3. **Configura l'ambiente**
```bash
cp .env.example .env
php artisan key:generate
```

4. **Configura il database nel file `.env`**
```env
DB_CONNECTION=sqlite
# oppure
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nome_database
DB_USERNAME=username
DB_PASSWORD=password
```

5. **Esegui le migrazioni e i seeder**
```bash
php artisan migrate
php artisan db:seed --class=RolesPermissionsSeeder
php artisan db:seed --class=VerifyPermissionsSeeder
```

6. **Crea un utente super admin**
```bash
php artisan tinker
```
```php
$user = \App\Models\User::create([
    'name' => 'Admin',
    'email' => 'admin@example.com',
    'password' => bcrypt('password'),
]);
$user->assignRole('super_admin');
```

7. **Compila gli asset**
```bash
npm run build
```

8. **Avvia il server**
```bash
php artisan serve
```

Accedi a `http://localhost:8000/admin` e fai login con le credenziali create.

## 📚 Documentazione

- [Riepilogo Funzionalità](RIEPILOGO_FUNZIONALITA.md) - Documentazione completa delle funzionalità implementate
- [Riepilogo Permessi Genitore](RIEPILOGO_PERMESSI_GENITORE.md) - Guida permessi per ruolo genitore
- [Verifica Permessi Mobile](VERIFICA_PERMESSI_MOBILE.md) - Ottimizzazioni mobile

## 👥 Ruoli e Permessi

Il sistema supporta 4 ruoli principali:

- **Super Admin**: Accesso completo a tutte le funzionalità
- **Dirigente**: Gestione completa esclusi ruoli
- **Allenatore**: Gestione tecnica (eventi, presenze, convocazioni)
- **Genitore**: Visualizzazione dati propri figli e gestione certificati

## 🎯 Funzionalità Principali

### Dashboard
- Widget prossimi eventi (allenamenti e partite)
- Statistiche atleti e squadre
- Grafici presenze nel tempo
- Notifiche certificati in scadenza

### Gestione Atleti
- CRUD completo atleti
- Upload certificati medici
- Statistiche presenze
- Export PDF statistiche

### Gestione Eventi
- Calendario interattivo
- Filtri avanzati
- Gestione allenamenti, partite e tornei

### Statistiche e Reportistica
- Dashboard statistiche avanzate
- Grafici presenze nel tempo
- Report PDF stagionali
- Confronto statistiche atleti/squadre

## 🔒 Sicurezza

- Sistema RBAC completo con Spatie Laravel Permission
- Policy-based authorization
- Protezione CSRF su tutte le form
- Validazione e sanitizzazione input
- File upload sicuri con validazione

## 📱 Mobile

L'interfaccia è completamente ottimizzata per dispositivi mobili:
- Tabelle responsive con colonne toggleabili
- Form ottimizzati per touch
- Layout adattivo
- Date picker mobile-friendly

## 🤝 Contribuire

Le pull request sono benvenute! Per cambiamenti importanti, apri prima una issue per discutere cosa vorresti cambiare.

## 📄 Licenza

Questo progetto è open source e disponibile sotto la [MIT License](LICENSE).

## 👨‍💻 Sviluppatore

Sviluppato per San Vincenzo Calcio

---

*Ultimo aggiornamento: Gennaio 2025*
