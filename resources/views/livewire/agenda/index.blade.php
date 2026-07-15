<section
    @class(['agenda-page', 'fixed inset-0 z-50' => $isFullscreen])
    wire:poll.30s
    data-testid="agenda-page"
>
    <header class="agenda-toolbar">
        <div class="agenda-toolbar-group">
            <button type="button" class="agenda-control" wire:click="today">Hoy</button>

            <div class="agenda-month-switcher">
                <button type="button" aria-label="Periodo anterior" wire:click="previous">
                    <flux:icon.chevron-left class="size-4" />
                </button>
                <span>{{ \Carbon\CarbonImmutable::parse($this->selectedDate)->translatedFormat('F \d\e Y') }}</span>
                <button type="button" aria-label="Periodo siguiente" wire:click="next">
                    <flux:icon.chevron-right class="size-4" />
                </button>
            </div>

            <label class="agenda-control">
                <span class="sr-only">Profesional</span>
                <select wire:model.live="professionalFilterId" aria-label="Equipo programado">
                    <option value="">Equipo programado</option>
                    @foreach ($this->professionalsCatalog as $professional)
                        <option value="{{ $professional->id }}">{{ $professional->fullName() }}</option>
                    @endforeach
                </select>
                <flux:icon.chevron-down class="size-4" />
            </label>

            <details class="agenda-filters" x-data @agenda-open-filters.window="$el.open = true">
                <summary class="agenda-icon-button" aria-label="Abrir filtros">
                    <flux:icon.adjustments-horizontal class="size-5" />
                </summary>
                <div class="agenda-filters-panel">
                    <div class="agenda-filters-title">
                        <strong>Filtros</strong>
                        <button type="button" wire:click="clearFilters">Restablecer</button>
                    </div>
                    <label>
                        <span>Sucursal</span>
                        <select wire:model.live="branchFilterId">
                            <option value="">Todas las sucursales</option>
                            @foreach ($this->branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>
                        <span>Recurso</span>
                        <select wire:model.live="resourceFilterId">
                            <option value="">Todos los recursos</option>
                            @foreach ($this->resourcesCatalog as $resource)
                                <option value="{{ $resource->id }}">{{ $resource->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="agenda-filter-check">
                        <input type="checkbox" wire:model.live="onlyAvailable">
                        <span>Solo disponibles</span>
                    </label>
                </div>
            </details>
        </div>

        <div class="agenda-toolbar-group agenda-toolbar-actions">
            <button type="button" class="agenda-icon-button" aria-label="Pantalla completa" wire:click="toggleFullscreen">
                <flux:icon.cog-6-tooth class="size-5" />
            </button>
            <button type="button" class="agenda-icon-button" aria-label="Ir a hoy" wire:click="today">
                <flux:icon.calendar-days class="size-5" />
            </button>
            <button type="button" class="agenda-icon-button" aria-label="Actualizar" wire:click="$refresh">
                <flux:icon.arrow-path class="size-5" />
            </button>

            <label class="agenda-control agenda-view-select">
                <select wire:model.live="viewMode" aria-label="Vista del calendario">
                    <option value="day">Día</option>
                    <option value="week">Semana</option>
                    <option value="month">Mes</option>
                    <option value="list">Lista</option>
                </select>
                <flux:icon.chevron-down class="size-4" />
            </label>

            <button type="button" class="agenda-primary-button" wire:click="openCreateModal">
                Agregar
                <flux:icon.chevron-down class="size-4" />
            </button>
        </div>
    </header>

    @if ($this->viewMode === 'month')
        <div class="agenda-grid-scroll">
            <div class="agenda-calendar">
                <div class="agenda-weekdays" aria-hidden="true">
                    <div>Domingo</div>
                    <div>Lunes</div>
                    <div>Martes</div>
                    <div>Miércoles</div>
                    <div>Jueves</div>
                    <div>Viernes</div>
                    <div>Sábado</div>
                </div>

                <div
                    class="agenda-month-grid"
                    style="--agenda-weeks: {{ count($this->monthGrid) / 7 }}"
                    data-testid="agenda-month-grid"
                >
                    @foreach ($this->monthGrid as $cell)
                        <article
                            wire:key="agenda-day-{{ $cell['key'] }}"
                            x-data="{ quickOpen: false }"
                            @agenda-quick-open.window="quickOpen = $event.detail.date === '{{ $cell['key'] }}'"
                            @keydown.escape.window="quickOpen = false"
                            @click="$dispatch('agenda-quick-open', { date: '{{ $cell['key'] }}' })"
                            @class([
                                'agenda-day',
                                'agenda-day--outside' => ! $cell['is_in_month'] || $cell['is_unavailable'],
                                'agenda-day--selected' => $cell['is_selected'] && ! $cell['is_today'],
                            ])
                        >
                            <button
                                type="button"
                                @class(['agenda-day-number', 'agenda-day-number--today' => $cell['is_today']])
                            >
                                {{ $cell['day'] === 1 ? $cell['date']->translatedFormat('j \d\e F') : $cell['day'] }}
                            </button>

                            <div
                                x-show="quickOpen"
                                x-cloak
                                @click.stop
                                @click.outside="quickOpen = false"
                                @class([
                                    'agenda-quick-menu',
                                    'agenda-quick-menu--left' => ($loop->index % 7) >= 2,
                                    'agenda-quick-menu--up' => $loop->index >= count($this->monthGrid) - 14,
                                ])
                            >
                                <div class="agenda-quick-header">
                                    <strong>{{ ucfirst($cell['date']->translatedFormat('l, j \d\e F')) }}</strong>
                                    <button type="button" aria-label="Cerrar acciones rápidas" @click="quickOpen = false">
                                        <flux:icon.x-mark class="size-4" />
                                    </button>
                                </div>

                                <div class="agenda-quick-actions">
                                    <button type="button" wire:click="openCreateModalForDate('{{ $cell['key'] }}')" @click="quickOpen = false">
                                        <flux:icon.calendar-days class="size-5" />
                                        <span>Agregar cita</span>
                                    </button>
                                    <button type="button" wire:click="openScheduleBlockModalForDate('{{ $cell['key'] }}')" @click="quickOpen = false">
                                        <flux:icon.calendar-days class="size-5" />
                                        <span>Agregar tiempo bloqueado</span>
                                    </button>
                                    <button type="button" wire:click="openDayView('{{ $cell['key'] }}')" @click="quickOpen = false">
                                        <flux:icon.rectangle-stack class="size-5" />
                                        <span>Vista diurna</span>
                                    </button>
                                    <button type="button" class="agenda-quick-settings" @click="quickOpen = false; $dispatch('agenda-open-filters')">
                                        Configuración de acciones rápidas
                                    </button>
                                </div>
                            </div>

                            <div class="agenda-events">
                                @foreach ($cell['appointments'] as $appointment)
                                    <button
                                        type="button"
                                        class="agenda-event"
                                        wire:key="agenda-appointment-{{ $appointment->id }}"
                                        wire:click.stop="openDrawer({{ $appointment->id }})"
                                        title="{{ $appointment->title }}"
                                    >
                                        {{ $appointment->starts_at?->format('H:i') }} {{ $appointment->client->fullName() }}
                                    </button>
                                @endforeach

                                @foreach ($cell['blocks'] as $block)
                                    <div class="agenda-block">
                                        {{ $block['starts_at']->format('H:i') }} · {{ $block['label'] }}
                                    </div>
                                @endforeach
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        </div>
    @elseif ($this->viewMode === 'list')
        <div class="agenda-list-view">
            @forelse ($this->appointments as $appointment)
                <button type="button" class="agenda-list-item" wire:click="openDrawer({{ $appointment->id }})">
                    <time>{{ $appointment->starts_at?->translatedFormat('D d M · H:i') }}</time>
                    <strong>{{ $appointment->client->fullName() }}</strong>
                    <span>{{ $appointment->service->name }} · {{ $appointment->branch->name }}</span>
                    <span>{{ $appointment->status?->name }}</span>
                </button>
            @empty
                <div class="agenda-empty-view">No hay citas en este periodo.</div>
            @endforelse
        </div>
    @else
        <div class="agenda-range-view" style="--agenda-columns: {{ count($this->rangeDays) }}">
            @foreach ($this->rangeDays as $day)
                <article class="agenda-range-day">
                    <button type="button" wire:click="$set('selectedDate', '{{ $day['key'] }}')">
                        <small>{{ $day['short_label'] }}</small>
                        <strong>{{ $day['date']->format('d') }}</strong>
                    </button>
                    <div class="agenda-events">
                        @forelse ($day['appointments'] as $appointment)
                            <button type="button" class="agenda-event" wire:click="openDrawer({{ $appointment->id }})">
                                {{ $appointment->starts_at?->format('H:i') }} {{ $appointment->client->fullName() }}
                            </button>
                        @empty
                            <span class="agenda-range-empty">Sin citas</span>
                        @endforelse
                    </div>
                </article>
            @endforeach
        </div>
    @endif

    @if ($this->selectedAppointment)
        <aside class="agenda-detail" data-testid="appointment-detail">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <flux:badge :color="$this->selectedAppointment->status?->color ?? 'zinc'">
                        {{ $this->selectedAppointment->status?->name ?? 'Pendiente' }}
                    </flux:badge>
                    <h2 class="mt-3 text-xl font-bold">{{ $this->selectedAppointment->title }}</h2>
                    <p class="mt-1 text-sm text-zinc-400">{{ $this->selectedAppointment->reference_code }}</p>
                </div>
                <button type="button" class="agenda-icon-button" aria-label="Cerrar detalle" wire:click="closeDrawer">
                    <flux:icon.x-mark class="size-5" />
                </button>
            </div>

            <div class="mt-8 grid gap-5 text-sm">
                <div><span class="text-zinc-500">Cliente</span><p class="mt-1 font-semibold">{{ $this->selectedAppointment->client->fullName() }}</p></div>
                <div><span class="text-zinc-500">Servicio</span><p class="mt-1 font-semibold">{{ $this->selectedAppointment->service->name }}</p></div>
                <div><span class="text-zinc-500">Fecha y hora</span><p class="mt-1 font-semibold">{{ $this->selectedAppointment->starts_at?->translatedFormat('d F Y · H:i') }} - {{ $this->selectedAppointment->ends_at?->format('H:i') }}</p></div>
                <div><span class="text-zinc-500">Sucursal</span><p class="mt-1 font-semibold">{{ $this->selectedAppointment->branch->name }}</p></div>
                <div><span class="text-zinc-500">Profesional</span><p class="mt-1 font-semibold">{{ $this->selectedAppointment->professional?->fullName() ?? 'Sin asignar' }}</p></div>
            </div>

            <div class="mt-8 flex gap-2 border-t border-zinc-800 pt-5">
                <flux:button variant="primary" icon="pencil-square" wire:click="openEditModal({{ $this->selectedAppointment->id }})">Editar</flux:button>
                <flux:button variant="ghost" wire:click="completeAppointment">Completar</flux:button>
            </div>
        </aside>
    @endif

    <flux:modal name="schedule-block-form" class="w-full max-w-xl">
        <form wire:submit="saveScheduleBlock" class="space-y-6">
            <div>
                <flux:heading size="lg">Agregar tiempo bloqueado</flux:heading>
                <flux:text class="mt-1 text-sm text-zinc-500">Reserva un periodo no disponible en la agenda.</flux:text>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="blockStartsAt" label="Inicio *" type="datetime-local" />
                <flux:input wire:model="blockEndsAt" label="Fin *" type="datetime-local" />
                <flux:select wire:model="blockType" label="Tipo de bloqueo *" class="sm:col-span-2">
                    <option value="unavailable">No disponible</option>
                    <option value="personal">Asunto personal</option>
                    <option value="lunch_break">Descanso / almuerzo</option>
                    <option value="maintenance">Mantenimiento</option>
                </flux:select>
                <div class="sm:col-span-2">
                    <flux:textarea wire:model="blockReason" label="Motivo" rows="3" />
                </div>
                <div class="sm:col-span-2">
                    <flux:switch wire:model="blockAllDay" label="Todo el día" align="left" />
                </div>
            </div>

            <div class="flex justify-end gap-3 border-t border-zinc-700 pt-4">
                <flux:modal.close><flux:button type="button" variant="ghost">Cancelar</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary">Agregar bloqueo</flux:button>
            </div>
        </form>
    </flux:modal>

    @if ($appointmentPanelOpen)
        <div class="agenda-appointment-overlay" wire:key="appointment-panel" x-data @keydown.escape.window="$wire.closeModal()">
            <button type="button" class="agenda-appointment-backdrop" aria-label="Cerrar panel de cita" wire:click="closeModal"></button>

            <aside class="agenda-appointment-drawer" data-testid="appointment-panel">
                <div class="agenda-appointment-rail">
                    <button type="button" aria-label="Cerrar" wire:click="closeModal">
                        <flux:icon.x-mark class="size-6" />
                    </button>
                    <button type="button" aria-label="Pantalla completa" wire:click="toggleFullscreen">
                        <flux:icon.arrows-pointing-out class="size-5" />
                    </button>
                    <button type="button" aria-label="Ir a hoy" wire:click="today">
                        <flux:icon.cog-6-tooth class="size-5" />
                    </button>
                </div>

                <div class="agenda-appointment-stepbar">
                    <div class="agenda-step-icon">
                        <flux:icon.user-plus class="size-5" />
                    </div>
                    <strong>Agregar<br>cliente</strong>
                    <p>O déjelo vacío para clientes sin cita previa.</p>
                </div>

                <div class="agenda-appointment-content">
                    @if ($appointmentStep === 'picker')
                        <div class="agenda-service-step">
                            <h2>Seleccione un servicio</h2>

                            <label class="agenda-service-search">
                                <flux:icon.magnifying-glass class="size-5" />
                                <input
                                    type="search"
                                    wire:model.live.debounce.250ms="serviceSearch"
                                    placeholder="Buscar por nombre del servicio"
                                    aria-label="Buscar por nombre del servicio"
                                    autofocus
                                >
                            </label>

                            <div class="agenda-service-groups">
                                @forelse ($this->servicesCatalog->groupBy(fn ($service) => $service->category?->name ?? 'Otros') as $category => $services)
                                    <section class="agenda-service-group" wire:key="service-category-{{ md5($category) }}">
                                        <h3>
                                            {{ $category }}
                                            <span>{{ $services->count() }}</span>
                                        </h3>

                                        <div>
                                            @foreach ($services as $service)
                                                <button
                                                    type="button"
                                                    wire:key="appointment-service-{{ $service->id }}"
                                                    wire:click="selectAppointmentService({{ $service->id }})"
                                                >
                                                    <span>
                                                        <strong>{{ $service->name }}</strong>
                                                        <small>{{ $this->serviceDurationLabel($service->duration_minutes) }}</small>
                                                    </span>
                                                    <b>S/ {{ number_format((float) $service->price, 2) }}</b>
                                                </button>
                                            @endforeach
                                        </div>
                                    </section>
                                @empty
                                    <div class="agenda-service-empty">
                                        No encontramos servicios con “{{ $serviceSearch }}”.
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    @elseif ($appointmentStep === 'services')
                        <div class="agenda-wizard-stage">
                            <nav class="agenda-wizard-breadcrumb" aria-label="Progreso de la cita">
                                <strong>Servicios</strong>
                                <flux:icon.chevron-right class="size-4" />
                                <span>Tiempo</span>
                            </nav>

                            <h2>Servicios</h2>

                            <div class="agenda-selected-services">
                                @foreach ($this->selectedServices as $service)
                                    <article wire:key="selected-service-{{ $service->id }}">
                                        <div class="agenda-selected-service-info">
                                            <span>
                                                <strong>{{ $service->name }}</strong>
                                                <small>{{ $this->serviceDurationLabel($service->duration_minutes) }}</small>
                                            </span>
                                            <b>S/ {{ number_format((float) $service->price, 2) }}</b>
                                        </div>

                                        <div class="agenda-service-member-row">
                                            <label>
                                                <flux:icon.user class="size-4" />
                                                <select wire:model="selectedServiceProfessionals.{{ $service->id }}" aria-label="Profesional para {{ $service->name }}">
                                                    <option value="">Cualquier miembro del equipo</option>
                                                    @foreach ($this->professionalsCatalog as $professional)
                                                        <option value="{{ $professional->id }}">{{ $professional->fullName() }}</option>
                                                    @endforeach
                                                </select>
                                                <flux:icon.chevron-down class="size-4" />
                                            </label>
                                            <button type="button" wire:click="removeAppointmentService({{ $service->id }})" aria-label="Quitar {{ $service->name }}">
                                                <flux:icon.x-mark class="size-4" />
                                            </button>
                                        </div>
                                    </article>
                                @endforeach
                            </div>

                            <button type="button" class="agenda-add-service-button" wire:click="showServiceStep">
                                <flux:icon.plus-circle class="size-4" />
                                Agregar servicio
                            </button>

                            <div class="agenda-wizard-footer">
                                <div class="agenda-wizard-total">
                                    <span>Total</span>
                                    <b>S/ {{ number_format($this->selectedServicesTotal, 2) }}</b>
                                    <strong>Para pagar <flux:icon.chevron-right class="size-4" /></strong>
                                    <strong>S/ {{ number_format($this->selectedServicesTotal, 2) }}</strong>
                                </div>
                                <button type="button" wire:click="continueToAppointmentTime">Continuar</button>
                            </div>
                        </div>
                    @elseif ($appointmentStep === 'time')
                        <div class="agenda-wizard-stage agenda-time-stage">
                            <nav class="agenda-wizard-breadcrumb" aria-label="Progreso de la cita">
                                <button type="button" wire:click="showServicesSummary">Servicios</button>
                                <flux:icon.chevron-right class="size-4" />
                                <strong>Tiempo</strong>
                            </nav>

                            <h2>Seleccione una hora</h2>

                            <div class="agenda-time-team">
                                <flux:icon.user class="size-4" />
                                <span>Cualquier miembro del equipo</span>
                                <flux:icon.chevron-down class="size-4" />
                            </div>

                            <div class="agenda-date-heading">
                                <strong>{{ ucfirst(\Carbon\CarbonImmutable::parse($appointmentTimeDate)->translatedFormat('F')) }}</strong>
                            </div>

                            <div class="agenda-date-options">
                                @foreach ($this->appointmentDateOptions as $dateOption)
                                    <button
                                        type="button"
                                        wire:key="appointment-date-{{ $dateOption['date'] }}"
                                        wire:click="selectAppointmentDate('{{ $dateOption['date'] }}')"
                                        @class(['is-selected' => $dateOption['is_selected']])
                                    >
                                        <strong>{{ $dateOption['day'] }}</strong>
                                        <small>{{ $dateOption['weekday'] }}</small>
                                    </button>
                                @endforeach
                            </div>

                            <div class="agenda-slots-heading">
                                <strong>Horarios disponibles</strong>
                                <button type="button">
                                    <flux:icon.calendar-days class="size-4" />
                                    Seleccione del calendario
                                </button>
                            </div>

                            <div class="agenda-time-slots">
                                @forelse ($slotSearchResults as $slot)
                                    <button
                                        type="button"
                                        wire:key="appointment-slot-{{ md5($slot['starts_at']) }}"
                                        wire:click="selectAppointmentSlot('{{ $slot['starts_at'] }}', '{{ $slot['ends_at'] }}')"
                                        @class(['is-selected' => $selectedSlotStart === $slot['starts_at']])
                                    >
                                        {{ $slot['label'] }}
                                    </button>
                                @empty
                                    <div class="agenda-service-empty">No hay horarios disponibles para este día.</div>
                                @endforelse
                            </div>

                            <div class="agenda-wizard-footer">
                                <div class="agenda-wizard-total">
                                    <span>Total</span>
                                    <b>S/ {{ number_format($this->selectedServicesTotal, 2) }}</b>
                                    <strong>Para pagar <flux:icon.chevron-right class="size-4" /></strong>
                                    <strong>S/ {{ number_format($this->selectedServicesTotal, 2) }}</strong>
                                </div>
                                <button
                                    type="button"
                                    wire:click="continueToAppointmentDetails"
                                    @disabled($selectedSlotStart === '')
                                >
                                    Continuar
                                </button>
                            </div>
                        </div>
                    @else
                        <form wire:submit="save" class="agenda-appointment-details">
                            <div class="agenda-details-header">
                                @if (! $form->appointmentId)
                                    <button type="button" wire:click="showServiceStep" aria-label="Volver a servicios">
                                        <flux:icon.chevron-left class="size-5" />
                                    </button>
                                @endif
                                <div>
                                    <span>{{ $form->appointmentId ? 'Editar cita' : 'Nueva cita' }}</span>
                                    <h2>{{ $form->title }}</h2>
                                </div>
                            </div>

                            <div class="agenda-details-form">
                                <flux:select wire:model="form.client_id" label="Cliente *">
                                    <option value="">Seleccionar cliente</option>
                                    @foreach ($this->clientsCatalog as $client)
                                        <option value="{{ $client->id }}">{{ $client->fullName() }}</option>
                                    @endforeach
                                </flux:select>
                                <flux:select wire:model="form.branch_id" label="Sucursal *">
                                    <option value="">Seleccionar sucursal</option>
                                    @foreach ($this->branches as $branch)
                                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                    @endforeach
                                </flux:select>
                                <flux:input wire:model="form.starts_at" label="Inicio *" type="datetime-local" />
                                <flux:input wire:model="form.ends_at" label="Fin *" type="datetime-local" />
                                <flux:select wire:model="form.professional_id" label="Profesional">
                                    <option value="">Sin asignar</option>
                                    @foreach ($this->professionalsCatalog as $professional)
                                        <option value="{{ $professional->id }}">{{ $professional->fullName() }}</option>
                                    @endforeach
                                </flux:select>
                                <flux:select wire:model="form.resource_id" label="Recurso">
                                    <option value="">Sin recurso</option>
                                    @foreach ($this->resourcesCatalog as $resource)
                                        <option value="{{ $resource->id }}">{{ $resource->name }}</option>
                                    @endforeach
                                </flux:select>
                                <flux:select wire:model="form.status_slug" label="Estado *">
                                    @foreach ($this->appointmentStatuses as $status)
                                        <option value="{{ $status->slug }}">{{ $status->name }}</option>
                                    @endforeach
                                </flux:select>
                                <flux:input wire:model="form.price" label="Precio *" type="number" step="0.01" min="0" />
                                <div class="agenda-details-notes">
                                    <flux:textarea wire:model="form.notes" label="Notas" rows="3" />
                                </div>
                            </div>

                            <div class="agenda-details-footer">
                                <button type="button" wire:click="closeModal">Cancelar</button>
                                <button type="submit">{{ $form->appointmentId ? 'Guardar cambios' : 'Crear cita' }}</button>
                            </div>
                        </form>
                    @endif
                </div>
            </aside>
        </div>
    @endif
</section>
