<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Filtri --}}
        <x-filament::section>
            <x-slot name="heading">
                Filtri
            </x-slot>
            <x-slot name="description">
                Seleziona il periodo e la squadra per visualizzare le statistiche
            </x-slot>
            
            {{ $this->form }}
        </x-filament::section>
    </div>
</x-filament-panels::page>
