<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Filtri Calendario --}}
        <x-filament::section>
            <x-slot name="heading">
                Filtri Calendario
            </x-slot>

            <form wire:submit.prevent class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <x-filament::input.wrapper>
                    <x-filament::input.select
                        wire:model.live="teamFilter"
                        label="Filtra per Squadra"
                    >
                        <option value="">Tutte le squadre</option>
                        @php
                            $teams = auth()->user()?->hasRole('genitore') && !auth()->user()?->hasAnyRole(['super_admin', 'dirigente', 'allenatore'])
                                ? \App\Models\Team::whereHas('athletes', function($q) { 
                                    $q->whereIn('athletes.id', auth()->user()->athletes()->pluck('id')); 
                                })->get()
                                : \App\Models\Team::all();
                        @endphp
                        @foreach($teams as $team)
                            <option value="{{ $team->id }}">{{ $team->name }}</option>
                        @endforeach
                    </x-filament::input.select>
                </x-filament::input.wrapper>

                @if(auth()->user()?->hasAnyRole(['super_admin', 'dirigente']))
                <x-filament::input.wrapper>
                    <x-filament::input.select
                        wire:model.live="coachFilter"
                        label="Filtra per Allenatore"
                    >
                        <option value="">Tutti gli allenatori</option>
                        @foreach(\App\Models\User::whereHas('roles', fn($q) => $q->where('name', 'allenatore'))->get() as $coach)
                            <option value="{{ $coach->id }}">{{ $coach->name }}</option>
                        @endforeach
                    </x-filament::input.select>
                </x-filament::input.wrapper>
                @endif

                @if(auth()->user()?->hasRole('genitore'))
                <x-filament::input.wrapper>
                    <x-filament::input.select
                        wire:model.live="athleteFilter"
                        label="Filtra per Atleta"
                    >
                        <option value="">Tutti gli atleti</option>
                        @foreach(auth()->user()->athletes as $athlete)
                            <option value="{{ $athlete->id }}">{{ $athlete->name }}@if($athlete->teams->count() > 0) ({{ $athlete->teams->pluck('name')->join(', ') }})@endif</option>
                        @endforeach
                    </x-filament::input.select>
                </x-filament::input.wrapper>
                @endif
            </form>
        </x-filament::section>

        {{-- Tabella Eventi --}}
        {{ $this->table }}
    </div>
</x-filament-panels::page>

