# Riepilogo Permessi e Visibilità per Genitore

## ✅ Permessi Assegnati (15 permessi totali)

- `page_Dashboard` - Accesso alla dashboard
- `view_any_athlete` / `view_athlete` - Solo i propri figli (filtrato da `getEloquentQuery`)
- `view_any_event` / `view_event` - Solo eventi delle squadre dei propri figli (filtrato)
- `view_any_attendance` / `view_attendance` - Solo presenze dei propri figli (filtrato)
- `view_any_convocation` / `view_convocation` - Solo convocazioni dei propri figli (filtrato)
- `view_any_payment` / `view_payment` - Solo pagamenti dei propri figli (filtrato)
- `view_any_kit_item` / `view_kit_item` - Solo kit dei propri figli (tramite RelationManager)
- `view_any_team` / `view_team` - Solo squadre dei propri figli (filtrato)

## 📋 Visibilità e Funzionalità

### ✅ Visibile nel Menu
- Dashboard
- Atleti (solo propri figli)
- Eventi (solo eventi delle squadre dei figli)
- Presenze (solo presenze dei propri figli)
- Convocazioni (solo convocazioni dei propri figli)
- Pagamenti (solo pagamenti dei propri figli)
- Squadre (solo squadre dei propri figli)

### ❌ NON Visibile nel Menu
- Utenti
- Kit (catalogo generale - visibile solo tramite RelationManager in Atleti)
- Campi
- Impostazioni Generali
- Filament Shield
- Importa Atleti
- Registra Presenze Multiple
- Convoca Atleti Multipli

### 🔒 Azioni Disponibili

#### AthleteResource
- ✅ **Visualizza** - Scheda completa atleta
- ✅ **Esporta Presenze PDF** - Statistiche presenze del figlio
- ❌ **Crea** - Non disponibile
- ❌ **Modifica** - Non disponibile
- ❌ **Elimina** - Non disponibile

#### EventResource
- ✅ **Visualizza** - Dettagli evento
- ✅ **Calendario** - Con filtri per squadra e atleta
- ❌ **Crea** - Non disponibile
- ❌ **Modifica** - Non disponibile
- ❌ **Elimina** - Non disponibile

#### AttendanceResource
- ✅ **Visualizza** - Lista presenze dei propri figli
- ✅ **Esporta Presenze PDF** - Solo per i propri figli (tramite ViewAthlete)
- ❌ **Crea** - Non disponibile
- ❌ **Modifica** - Non disponibile
- ❌ **Elimina** - Non disponibile

#### ConvocationResource
- ✅ **Visualizza** - Lista convocazioni dei propri figli
- ❌ **Crea** - Non disponibile
- ❌ **Modifica** - Non disponibile
- ❌ **Elimina** - Non disponibile

#### PaymentResource
- ✅ **Visualizza** - Lista pagamenti dei propri figli
- ❌ **Crea** - Non disponibile
- ❌ **Modifica** - Non disponibile
- ❌ **Elimina** - Non disponibile

#### TeamResource
- ✅ **Visualizza** - Lista squadre dei propri figli
- ❌ **Crea** - Non disponibile
- ❌ **Modifica** - Non disponibile
- ❌ **Elimina** - Non disponibile

### 📊 Relation Managers (in AthleteResource)

#### KitItemsRelationManager
- ✅ **Visualizza** - Kit assegnati al figlio
- ❌ **Attach** - Non disponibile
- ❌ **Modifica** - Non disponibile
- ❌ **Detach** - Non disponibile

#### AttendancesRelationManager
- ✅ **Visualizza** - Presenze del figlio
- ✅ **Statistiche** - Tramite export PDF
- ❌ **Crea** - Non disponibile
- ❌ **Modifica** - Non disponibile
- ❌ **Elimina** - Non disponibile

#### ConvocationsRelationManager
- ✅ **Visualizza** - Convocazioni del figlio
- ❌ **Crea** - Non disponibile
- ❌ **Modifica** - Non disponibile
- ❌ **Elimina** - Non disponibile

## 🔍 Filtri Applicati

### AthleteResource
- Query filtrata: `where('parent_id', $user->id)`
- Mostra solo atleti dove il genitore è l'utente corrente

### EventResource
- Query filtrata: Eventi delle squadre dove i figli sono iscritti
- Filtro calendario: Solo squadre dei propri figli

### AttendanceResource
- Query filtrata: `whereIn('athlete_id', $athleteIds)`
- Mostra solo presenze degli atleti del genitore

### ConvocationResource
- Query filtrata: `whereIn('athlete_id', $athleteIds)`
- Mostra solo convocazioni degli atleti del genitore

### PaymentResource
- Query filtrata: `whereIn('athlete_id', $athleteIds)`
- Mostra solo pagamenti degli atleti del genitore

### TeamResource
- Query filtrata: Squadre che hanno almeno un atleta del genitore
- Mostra solo squadre dei propri figli

## 📱 Statistiche Presenze

I genitori possono:
- ✅ Visualizzare le presenze dei propri figli nella tabella
- ✅ Esportare statistiche presenze in PDF per ogni figlio (tramite ViewAthlete)
- ✅ Vedere percentuali, totali presenze/assenze
- ✅ Filtrare per periodo (data inizio/fine)

## ✅ Verifica Completata

- [x] Permessi assegnati correttamente (15 permessi)
- [x] Filtri applicati a tutte le risorse
- [x] Azioni CRUD rimosse per genitori
- [x] Navigation visibility configurata
- [x] Relation Managers con solo visualizzazione
- [x] Statistiche presenze disponibili
- [x] Export PDF funzionante
- [x] Filtri calendario corretti



