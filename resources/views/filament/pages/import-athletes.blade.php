<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">
            Importa Atleti da CSV
        </x-slot>

        <x-slot name="description">
            Carica un file CSV per importare atleti nel sistema. Il file deve contenere le colonne specificate nel template.
        </x-slot>

        <form wire:submit="import">
            {{ $this->form }}

            <x-filament-panels::form.actions
                :actions="$this->getFormActions()"
            />
        </form>
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">
            Istruzioni
        </x-slot>

        <div class="space-y-4">
            <div>
                <h3 class="font-semibold mb-2">Formato CSV Richiesto</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Il file CSV deve contenere le seguenti colonne (nell'ordine specificato):
                </p>
                <ul class="list-disc list-inside mt-2 space-y-1 text-sm text-gray-600 dark:text-gray-400">
                    <li><strong>Nome Atleta</strong> (obbligatorio) - Nome completo dell'atleta</li>
                    <li><strong>Data Nascita</strong> (obbligatorio) - Formato: YYYY-MM-DD, DD/MM/YYYY o DD-MM-YYYY</li>
                    <li><strong>Nome Genitore</strong> (obbligatorio) - Nome completo del genitore</li>
                    <li><strong>Email Genitore</strong> (obbligatorio) - Email del genitore (usata per identificare/creare il genitore)</li>
                    <li><strong>Telefono Genitore</strong> (opzionale) - Numero di telefono</li>
                    <li><strong>Squadre</strong> (opzionale) - Nomi delle squadre separate da virgola (es: "Primi Calci, Esordienti")</li>
                    <li><strong>Scadenza Certificato Medico</strong> (opzionale) - Formato: YYYY-MM-DD, DD/MM/YYYY o DD-MM-YYYY</li>
                </ul>
            </div>

            <div>
                <h3 class="font-semibold mb-2">Note Importanti</h3>
                <ul class="list-disc list-inside space-y-1 text-sm text-gray-600 dark:text-gray-400">
                    <li>Il file deve essere in formato CSV con encoding UTF-8</li>
                    <li>La prima riga deve contenere gli header delle colonne</li>
                    <li>Se un genitore esiste già (basato sull'email), verrà utilizzato quello esistente</li>
                    <li>Se "Crea automaticamente i genitori" è selezionato, i nuovi genitori avranno una password generata automaticamente</li>
                    <li>Le squadre devono corrispondere esattamente ai nomi delle squadre nel sistema</li>
                    <li>Gli atleti duplicati (stesso nome e stesso genitore) verranno saltati se l'opzione è attiva</li>
                </ul>
            </div>
        </div>
    </x-filament::section>
</x-filament-panels::page>



