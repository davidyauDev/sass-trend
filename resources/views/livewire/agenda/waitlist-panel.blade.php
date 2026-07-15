<div class="agenda-waitlist-root">
    <button
        type="button"
        class="agenda-icon-button agenda-waitlist-trigger"
        aria-label="Abrir lista de espera"
        wire:click="openPanel"
    >
        <flux:icon.calendar-days class="size-5" />
        @if ($this->counts['waiting'] > 0)
            <span class="agenda-waitlist-trigger__badge">{{ $this->counts['waiting'] > 99 ? '99+' : $this->counts['waiting'] }}</span>
        @endif
    </button>

    @if ($open)
        <div class="agenda-waitlist-overlay" wire:key="agenda-waitlist-panel" x-data @keydown.escape.window="$wire.closePanel()">
            <button type="button" class="agenda-waitlist-backdrop" aria-label="Cerrar lista de espera" wire:click="closePanel"></button>

            <aside class="agenda-waitlist-drawer" data-testid="waitlist-panel">
                <div class="agenda-waitlist-rail">
                    <button type="button" aria-label="Cerrar" wire:click="closePanel">
                        <flux:icon.x-mark class="size-6" />
                    </button>
                </div>

                <div class="agenda-waitlist-content">
                    <header class="agenda-waitlist-header">
                        <h2>Lista de espera</h2>
                        <div>
                            <button type="button" class="agenda-waitlist-more" aria-label="Más opciones">
                                <flux:icon.ellipsis-vertical class="size-5" />
                            </button>
                            <button type="button" class="agenda-waitlist-add" wire:click="openCreate">Agregar</button>
                        </div>
                    </header>

                    @if ($creating)
                        <form wire:submit="save" class="agenda-waitlist-form">
                            <div class="agenda-waitlist-form__heading">
                                <div>
                                    <span>Nueva solicitud</span>
                                    <h3>Agregar a la lista de espera</h3>
                                </div>
                                <button type="button" wire:click="cancelCreate" aria-label="Cancelar">
                                    <flux:icon.x-mark class="size-5" />
                                </button>
                            </div>

                            <div class="agenda-waitlist-form__fields">
                                <flux:select wire:model="form.clientId" label="Cliente *">
                                    <option value="">Seleccionar cliente</option>
                                    @foreach ($this->clients as $client)
                                        <option value="{{ $client->id }}">{{ $client->fullName() }}</option>
                                    @endforeach
                                </flux:select>
                                <flux:select wire:model="form.branchId" label="Sucursal *">
                                    <option value="">Seleccionar sucursal</option>
                                    @foreach ($this->branches as $branch)
                                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                    @endforeach
                                </flux:select>
                                <flux:select wire:model="form.serviceId" label="Servicio *">
                                    <option value="">Seleccionar servicio</option>
                                    @foreach ($this->services as $service)
                                        <option value="{{ $service->id }}">{{ $service->name }}</option>
                                    @endforeach
                                </flux:select>
                                <flux:select wire:model="form.professionalId" label="Miembro del equipo">
                                    <option value="">Cualquier miembro del equipo</option>
                                    @foreach ($this->professionals as $professional)
                                        <option value="{{ $professional->id }}">{{ $professional->fullName() }}</option>
                                    @endforeach
                                </flux:select>
                                <flux:input wire:model="form.desiredDate" label="Fecha deseada *" type="date" />
                                <div class="agenda-waitlist-form__times">
                                    <flux:input wire:model="form.availableFrom" label="Desde *" type="time" />
                                    <flux:input wire:model="form.availableUntil" label="Hasta *" type="time" />
                                </div>
                                <flux:textarea wire:model="form.notes" label="Notas" rows="3" />
                            </div>

                            <footer class="agenda-waitlist-form__actions">
                                <button type="button" wire:click="cancelCreate">Cancelar</button>
                                <button type="submit" wire:loading.attr="disabled" wire:target="save">
                                    <span wire:loading.remove wire:target="save">Agregar</span>
                                    <span wire:loading wire:target="save">Agregando...</span>
                                </button>
                            </footer>
                        </form>
                    @else
                        <div class="agenda-waitlist-filters">
                            <label>
                                <select wire:model.live="dateFilter" aria-label="Filtrar por fecha">
                                    <option value="upcoming">Próximas</option>
                                    <option value="today">Hoy</option>
                                    <option value="week">Próximos 7 días</option>
                                </select>
                                <flux:icon.chevron-down class="size-4" />
                            </label>
                            <label>
                                <select wire:model.live="sort" aria-label="Ordenar lista de espera">
                                    <option value="oldest">Creado (más antiguo)</option>
                                    <option value="newest">Creado (más reciente)</option>
                                </select>
                                <flux:icon.adjustments-horizontal class="size-4" />
                            </label>
                        </div>

                        <nav class="agenda-waitlist-tabs" aria-label="Estados de lista de espera">
                            <button type="button" wire:click="$set('tab', 'waiting')" @class(['is-active' => $tab === 'waiting'])>
                                En espera <span>{{ $this->counts['waiting'] }}</span>
                            </button>
                            <button type="button" wire:click="$set('tab', 'expired')" @class(['is-active' => $tab === 'expired'])>
                                Vencidas <span>{{ $this->counts['expired'] }}</span>
                            </button>
                            <button type="button" wire:click="$set('tab', 'booked')" @class(['is-active' => $tab === 'booked'])>
                                Reservadas <span>{{ $this->counts['booked'] }}</span>
                            </button>
                        </nav>

                        <div class="agenda-waitlist-body">
                            @forelse ($this->entries as $entry)
                                @if ($loop->first && $tab === 'waiting')
                                    <h3 class="agenda-waitlist-available">
                                        Disponible para reservar
                                        <span>{{ $this->entries->count() }}</span>
                                    </h3>
                                @endif

                                <article class="agenda-waitlist-card" wire:key="waitlist-entry-{{ $entry->id }}">
                                    <header>
                                        <div>
                                            <strong>{{ $entry->client->fullName() }}</strong>
                                            <span>{{ $entry->client->email ?: $entry->client->phone }}</span>
                                        </div>
                                        <span class="agenda-waitlist-avatar">
                                            {{ mb_strtoupper(mb_substr($entry->client->fullName(), 0, 1)) }}
                                        </span>
                                    </header>

                                    <p class="agenda-waitlist-window">
                                        <flux:icon.calendar-days class="size-5" />
                                        {{ $entry->desired_date->translatedFormat('M j') }},
                                        {{ \Carbon\CarbonImmutable::parse($entry->available_from)->format('H:i') }} –
                                        {{ \Carbon\CarbonImmutable::parse($entry->available_until)->format('H:i') }}
                                    </p>

                                    <div class="agenda-waitlist-card__service">
                                        <div>
                                            <strong>{{ $entry->service->name }}</strong>
                                            <span>
                                                {{ $this->durationLabel($entry->service->duration_minutes) }} ·
                                                {{ $entry->professional?->fullName() ?? 'Cualquier miembro del equipo' }}
                                            </span>
                                        </div>
                                        <strong>PEN {{ number_format((float) $entry->service->price, 0) }}</strong>
                                    </div>

                                    @if ($tab === 'waiting')
                                        <footer>
                                            <button type="button" class="agenda-waitlist-book" wire:click="bookNow({{ $entry->id }})">Reservar ahora</button>
                                            <button type="button" class="agenda-waitlist-more" aria-label="Más opciones para {{ $entry->client->fullName() }}">
                                                <flux:icon.ellipsis-vertical class="size-5" />
                                            </button>
                                        </footer>
                                    @endif
                                </article>
                            @empty
                                <div class="agenda-waitlist-empty">
                                    <span class="agenda-waitlist-empty__icon" aria-hidden="true">
                                        <i></i><i></i>
                                        <flux:icon.calendar-days class="size-8" />
                                    </span>
                                    <h3>Sin entradas en la lista de espera</h3>
                                    <p>No tienes clientes en esta sección de la lista.</p>
                                </div>
                            @endforelse
                        </div>
                    @endif
                </div>
            </aside>
        </div>
    @endif
</div>
