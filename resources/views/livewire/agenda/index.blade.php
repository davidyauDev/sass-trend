<section
    @class([
        'w-full px-4 py-6 sm:px-6 lg:px-8',
        'fixed inset-0 z-50 overflow-auto bg-zinc-950/95' => $isFullscreen,
    ])
    wire:poll.30s
>
    <div class="space-y-6">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
            <div class="min-w-0">
                <flux:badge color="sky" size="sm" inset="left">Agenda empresarial</flux:badge>
                <flux:heading size="xl" level="1" class="mt-3">Agenda de citas</flux:heading>
                <flux:subheading size="lg" class="mt-2 max-w-4xl">
                    Gestiona reservas multi-sede, profesionales, recursos, bloqueos y estados con una vista inspirada en AgendaPro, Google Calendar y Calendly.
                </flux:subheading>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <flux:button variant="ghost" icon="arrow-path" wire:click="$refresh">
                    Actualizar
                </flux:button>

                <flux:button variant="ghost" icon="arrows-pointing-out" wire:click="toggleFullscreen">
                    Pantalla completa
                </flux:button>

                <flux:button variant="primary" icon="plus" wire:click="openCreateModal">
                    Nueva cita
                </flux:button>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
            <flux:card class="border border-zinc-200/80 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <flux:text class="text-xs uppercase tracking-wide text-zinc-400">Citas de hoy</flux:text>
                <flux:heading size="xl" class="mt-2">{{ $this->dashboardStats['appointments_today'] }}</flux:heading>
            </flux:card>

            <flux:card class="border border-zinc-200/80 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <flux:text class="text-xs uppercase tracking-wide text-zinc-400">Confirmadas</flux:text>
                <flux:heading size="xl" class="mt-2">{{ $this->dashboardStats['confirmed_appointments'] }}</flux:heading>
            </flux:card>

            <flux:card class="border border-zinc-200/80 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <flux:text class="text-xs uppercase tracking-wide text-zinc-400">Canceladas</flux:text>
                <flux:heading size="xl" class="mt-2">{{ $this->dashboardStats['cancelled_appointments'] }}</flux:heading>
            </flux:card>

            <flux:card class="border border-zinc-200/80 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <flux:text class="text-xs uppercase tracking-wide text-zinc-400">Ingresos de hoy</flux:text>
                <flux:heading size="xl" class="mt-2">S/ {{ number_format($this->dashboardStats['revenue_today'], 2) }}</flux:heading>
            </flux:card>

            <flux:card class="border border-zinc-200/80 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <flux:text class="text-xs uppercase tracking-wide text-zinc-400">Ocupación</flux:text>
                <div class="mt-2 flex items-end gap-3">
                    <flux:heading size="xl">{{ $this->dashboardStats['occupancy_percentage'] }}%</flux:heading>
                </div>
            </flux:card>
        </div>

        <div @class([
            'grid gap-6 xl:grid-cols-[18rem_minmax(0,1fr)_24rem]',
            'xl:grid-cols-[18rem_minmax(0,1fr)]' => ! $this->selectedAppointment,
        ])>
            <aside class="space-y-6">
                <flux:card class="border border-zinc-200/80 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="space-y-4 p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <flux:heading size="sm">Filtros</flux:heading>
                                <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">Filtra por sucursal, profesional o recurso.</flux:text>
                            </div>

                            <flux:button size="sm" variant="ghost" icon="arrow-path" wire:click="clearFilters">
                                Reset
                            </flux:button>
                        </div>

                        <div class="space-y-3">
                            <div>
                                <flux:label>Sucursal</flux:label>
                                <flux:select wire:model.live="branchFilterId" class="mt-2">
                                    <option value="">Todas las sucursales</option>
                                    @foreach ($this->branches as $branch)
                                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                    @endforeach
                                </flux:select>
                            </div>

                            <div>
                                <flux:label>Profesional</flux:label>
                                <flux:select wire:model.live="professionalFilterId" class="mt-2">
                                    <option value="">Todos los profesionales</option>
                                    @foreach ($this->professionalsCatalog as $professional)
                                        <option value="{{ $professional->id }}">{{ $professional->fullName() }}</option>
                                    @endforeach
                                </flux:select>
                            </div>

                            <div>
                                <flux:label>Recurso</flux:label>
                                <flux:select wire:model.live="resourceFilterId" class="mt-2">
                                    <option value="">Todos los recursos</option>
                                    @foreach ($this->resourcesCatalog as $resource)
                                        <option value="{{ $resource->id }}">{{ $resource->name }} ({{ $resource->type }})</option>
                                    @endforeach
                                </flux:select>
                            </div>

                            <flux:switch
                                wire:model.live="onlyAvailable"
                                label="Solo disponibles"
                                description="Oculta las citas finalizadas y resalta la capacidad disponible."
                                align="left"
                            />
                        </div>
                    </div>
                </flux:card>

                <flux:card class="border border-zinc-200/80 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="space-y-4 p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <flux:heading size="sm">{{ \Carbon\CarbonImmutable::parse($this->selectedDate)->translatedFormat('F Y') }}</flux:heading>
                                <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">Navegador de calendario</flux:text>
                            </div>

                            <div class="flex gap-1">
                                <flux:button size="sm" variant="ghost" icon="chevron-left" wire:click="previous" />
                                <flux:button size="sm" variant="ghost" icon="chevron-right" wire:click="next" />
                            </div>
                        </div>

                        <div class="grid grid-cols-7 gap-1 text-center text-[10px] font-semibold uppercase tracking-wide text-zinc-400">
                            <span>L</span>
                            <span>M</span>
                            <span>X</span>
                            <span>J</span>
                            <span>V</span>
                            <span>S</span>
                            <span>D</span>
                        </div>

                        <div class="grid grid-cols-7 gap-1">
                            @foreach ($this->miniCalendar as $day)
                                <button
                                    type="button"
                                    wire:click="$set('selectedDate', '{{ $day['key'] }}')"
                                    @class([
                                        'rounded-xl border px-2 py-2 text-left transition',
                                        'border-sky-400 bg-sky-500/10 text-sky-200' => $day['is_selected'],
                                        'border-zinc-700 bg-zinc-900 text-zinc-500' => ! $day['is_in_month'] && ! $day['is_selected'],
                                        'border-zinc-700 bg-zinc-900 text-zinc-100 hover:border-sky-500' => $day['is_in_month'] && ! $day['is_selected'],
                                    ])
                                >
                                    <div class="flex items-center justify-between text-[11px]">
                                        <span>{{ $day['day'] }}</span>
                                        @if ($day['count'] > 0)
                                            <span class="rounded-full bg-sky-500/20 px-1.5 py-0.5 text-[10px] text-sky-200">{{ $day['count'] }}</span>
                                        @endif
                                    </div>
                                </button>
                            @endforeach
                        </div>
                    </div>
                </flux:card>

                <flux:card class="border border-zinc-200/80 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="space-y-4 p-4">
                        <div>
                            <flux:heading size="sm">Buscar horarios disponibles</flux:heading>
                            <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">Encuentra el siguiente espacio libre por día, sucursal y recurso.</flux:text>
                        </div>

                        <div class="grid gap-3">
                            <flux:input wire:model="slotSearchDate" label="Fecha" type="date" />
                            <flux:input wire:model="slotSearchDuration" label="Duración (minutos)" type="number" min="15" step="15" />
                            <flux:button variant="primary" icon="magnifying-glass" wire:click="searchAvailableSlots">
                                Buscar horarios disponibles
                            </flux:button>
                        </div>

                        @if (count($this->slotSearchResults) > 0)
                            <div class="space-y-2">
                                @foreach ($this->slotSearchResults as $slot)
                                    <button
                                        type="button"
                                        class="w-full rounded-2xl border border-zinc-200/70 px-3 py-3 text-left transition hover:border-sky-400 dark:border-zinc-700 dark:hover:border-sky-500"
                                        wire:click="openSlotResult('{{ $slot['starts_at'] }}', '{{ $slot['ends_at'] }}')"
                                    >
                                        <div class="flex items-center justify-between gap-3">
                                            <div>
                                                <div class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $slot['label'] }}</div>
                                                <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $slot['branch_name'] }}</div>
                                            </div>
                                            <flux:badge color="emerald">Open</flux:badge>
                                        </div>
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </flux:card>
            </aside>

            <main class="space-y-6">
                <flux:card class="border border-zinc-200/80 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="flex flex-col gap-4 border-b border-zinc-200/80 p-4 dark:border-zinc-700 lg:flex-row lg:items-center lg:justify-between">
                        <div class="flex flex-wrap items-center gap-2">
                            <flux:button variant="ghost" icon="chevron-left" wire:click="previous">
                                Anterior
                            </flux:button>
                            <flux:button variant="ghost" icon="calendar-days" wire:click="today">
                                Hoy
                            </flux:button>
                            <flux:button variant="ghost" icon="chevron-right" wire:click="next">
                                Siguiente
                            </flux:button>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            @foreach ([['day', 'Day'], ['week', 'Week'], ['month', 'Month'], ['list', 'List']] as [$mode, $label])
                                <flux:button
                                    variant="{{ $viewMode === $mode ? 'primary' : 'ghost' }}"
                                    wire:click="$set('viewMode', '{{ $mode }}')"
                                >
                                    {{ $label }}
                                </flux:button>
                            @endforeach
                        </div>
                    </div>

                    <div class="flex flex-col gap-3 border-b border-zinc-200/80 p-4 text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-400 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <flux:heading size="sm">{{ \Carbon\CarbonImmutable::parse($this->selectedDate)->translatedFormat('d F Y') }}</flux:heading>
                            <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">
                                {{ $this->viewMode === 'day' ? 'Single day pipeline' : ($this->viewMode === 'week' ? '7-day operational window' : ($this->viewMode === 'month' ? 'Monthly planning grid' : 'Operational list view')) }}
                            </flux:text>
                        </div>

                        <div class="flex items-center gap-2">
                            <flux:button variant="ghost" icon="arrow-path" wire:click="$refresh">
                                Actualizar
                            </flux:button>
                            <flux:button variant="primary" icon="plus" wire:click="openCreateModal">
                                Nueva cita
                            </flux:button>
                        </div>
                    </div>

                    @if ($this->viewMode === 'list')
                        <div class="overflow-x-auto">
                            <flux:table>
                                <flux:table.columns>
                                    <flux:table.column>Fecha</flux:table.column>
                                    <flux:table.column>Cliente</flux:table.column>
                                    <flux:table.column>Servicio</flux:table.column>
                                    <flux:table.column>Sucursal</flux:table.column>
                                    <flux:table.column>Recurso</flux:table.column>
                                    <flux:table.column>Estado</flux:table.column>
                                    <flux:table.column>Importe</flux:table.column>
                                    <flux:table.column class="text-right">Acciones</flux:table.column>
                                </flux:table.columns>

                                <flux:table.rows>
                                    @forelse ($this->appointments as $appointment)
                                        <flux:table.row :key="$appointment->id">
                                            <flux:table.cell>
                                                <button type="button" class="text-left" wire:click="openDrawer({{ $appointment->id }})">
                                                    <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $appointment->starts_at?->format('d M H:i') }}</div>
                                                    <div class="text-xs text-zinc-500">{{ $appointment->duration_minutes }} min</div>
                                                </button>
                                            </flux:table.cell>
                                            <flux:table.cell>{{ $appointment->client->fullName() }}</flux:table.cell>
                                            <flux:table.cell>{{ $appointment->service->name }}</flux:table.cell>
                                            <flux:table.cell>{{ $appointment->branch->name }}</flux:table.cell>
                                            <flux:table.cell>{{ $appointment->resource?->name ?? '—' }}</flux:table.cell>
                                            <flux:table.cell>
                                                <flux:select wire:change="changeStatusInline({{ $appointment->id }}, $event.target.value)">
                                                    @foreach ($this->appointmentStatuses as $status)
                                                        <option value="{{ $status->slug }}" @selected($appointment->status?->slug === $status->slug)>{{ $status->name }}</option>
                                                    @endforeach
                                                </flux:select>
                                            </flux:table.cell>
                                            <flux:table.cell>S/ {{ number_format((float) $appointment->price, 2) }}</flux:table.cell>
                                            <flux:table.cell>
                                                <div class="flex items-center justify-end gap-2">
                                                    <flux:button size="sm" variant="ghost" icon="eye" wire:click="openDrawer({{ $appointment->id }})">Ver</flux:button>
                                                    <flux:button size="sm" variant="ghost" icon="pencil-square" wire:click="openEditModal({{ $appointment->id }})">Editar</flux:button>
                                                </div>
                                            </flux:table.cell>
                                        </flux:table.row>
                                    @empty
                                        <flux:table.row>
                                            <flux:table.cell colspan="8">
                                                <div class="py-12 text-center">
                                                    <flux:heading size="lg">No se encontraron citas</flux:heading>
                                                    <flux:text class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">
                                                        Prueba cambiando los filtros o crea una nueva cita para empezar a llenar la agenda.
                                                    </flux:text>
                                                </div>
                                            </flux:table.cell>
                                        </flux:table.row>
                                    @endforelse
                                </flux:table.rows>
                            </flux:table>
                        </div>
                    @elseif ($this->viewMode === 'month')
                        <div class="grid grid-cols-7 gap-px overflow-hidden rounded-2xl border border-zinc-200/80 bg-zinc-200/80 dark:border-zinc-700 dark:bg-zinc-700">
                            @foreach (['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'] as $weekday)
                                <div class="bg-white px-3 py-2 text-xs font-semibold uppercase tracking-wide text-zinc-400 dark:bg-zinc-900">{{ $weekday }}</div>
                            @endforeach

                            @foreach ($this->monthGrid as $cell)
                                <button
                                    type="button"
                                    wire:click="$set('selectedDate', '{{ $cell['key'] }}')"
                                    @class([
                                        'min-h-40 bg-white p-3 text-left transition hover:bg-zinc-50 dark:bg-zinc-900 dark:hover:bg-zinc-800',
                                        'ring-2 ring-sky-400 ring-inset' => $cell['is_selected'],
                                        'opacity-50' => ! $cell['is_in_month'],
                                    ])
                                >
                                    <div class="flex items-center justify-between">
                                        <span @class(['text-sm font-semibold', 'text-sky-400' => $cell['is_today'], 'text-zinc-900 dark:text-zinc-100' => ! $cell['is_today']])>{{ $cell['day'] }}</span>
                                        @if (count($cell['blocks']) > 0)
                                            <flux:badge color="amber" size="sm">{{ count($cell['blocks']) }} bloqueos</flux:badge>
                                        @endif
                                    </div>

                                    <div class="mt-3 space-y-1">
                                        @foreach (collect($cell['appointments'])->take(3) as $appointment)
                                            <div
                                                class="rounded-lg border border-zinc-200 px-2 py-1 text-xs dark:border-zinc-700"
                                                style="border-left: 3px solid {{ match ($appointment->status?->color) {
                                                    'sky' => '#0ea5e9',
                                                    'emerald' => '#10b981',
                                                    'amber' => '#f59e0b',
                                                    'red' => '#ef4444',
                                                    'rose' => '#f43f5e',
                                                    'violet' => '#8b5cf6',
                                                    default => '#71717a',
                                                } }}"
                                            >
                                                <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $appointment->starts_at?->format('H:i') }} {{ $appointment->client->fullName() }}</div>
                                                <div class="text-zinc-500">{{ $appointment->service->name }}</div>
                                            </div>
                                        @endforeach

                                        @if (count($cell['appointments']) > 3)
                                            <div class="text-xs text-zinc-500">+{{ count($cell['appointments']) - 3 }} más</div>
                                        @endif
                                    </div>
                                </button>
                            @endforeach
                        </div>
                    @else
                        <div
                            class="grid gap-4 overflow-x-auto"
                            style="grid-template-columns: repeat({{ count($this->rangeDays) }}, minmax(18rem, 1fr));"
                        >
                            @foreach ($this->rangeDays as $day)
                                <div class="rounded-2xl border border-zinc-200/80 bg-zinc-50/70 dark:border-zinc-700 dark:bg-zinc-900">
                                    <div class="border-b border-zinc-200/80 p-4 dark:border-zinc-700">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <flux:heading size="sm">{{ $day['label'] }}</flux:heading>
                                                <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">
                                                    {{ count($day['appointments']) }} citas
                                                </flux:text>
                                            </div>

                                            @if ($day['is_today'])
                                                <flux:badge color="sky">Hoy</flux:badge>
                                            @endif
                                        </div>
                                    </div>

                                    <div
                                        class="min-h-96 space-y-3 p-4"
                                        @dragover.prevent
                                        @drop.prevent="$wire.moveAppointment(parseInt(event.dataTransfer.getData('appointment-id')), '{{ $day['date']->toDateTimeString() }}')"
                                    >
                                        @foreach ($day['blocks'] as $block)
                                            <div class="rounded-2xl border border-dashed border-amber-400/50 bg-amber-500/10 p-3 text-xs text-amber-200">
                                                <div class="font-semibold">{{ \Illuminate\Support\Str::title(str_replace('_', ' ', $block['label'])) }}</div>
                                                <div>{{ $block['reason'] ?? 'Schedule block' }}</div>
                                            </div>
                                        @endforeach

                                        @forelse ($day['appointments'] as $appointment)
                                            <article
                                                draggable="true"
                                                @dragstart="event.dataTransfer.setData('appointment-id', '{{ $appointment->id }}')"
                                                class="cursor-grab rounded-2xl border border-zinc-200 bg-white p-3 shadow-sm transition hover:border-sky-400 dark:border-zinc-700 dark:bg-zinc-950"
                                                style="border-left: 4px solid {{ match ($appointment->status?->color) {
                                                    'sky' => '#0ea5e9',
                                                    'emerald' => '#10b981',
                                                    'amber' => '#f59e0b',
                                                    'red' => '#ef4444',
                                                    'rose' => '#f43f5e',
                                                    'violet' => '#8b5cf6',
                                                    default => '#71717a',
                                                } }}"
                                            >
                                                <div class="flex items-start justify-between gap-3">
                                                    <div class="min-w-0">
                                                        <button type="button" class="text-left" wire:click="openDrawer({{ $appointment->id }})">
                                                            <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $appointment->starts_at?->format('H:i') }} - {{ $appointment->ends_at?->format('H:i') }}</div>
                                                            <div class="truncate text-sm text-zinc-500 dark:text-zinc-400">{{ $appointment->client->fullName() }}</div>
                                                        </button>
                                                        <div class="mt-2 text-xs text-zinc-500">{{ $appointment->service->name }}</div>
                                                    </div>

                                                    <flux:badge :color="$appointment->status?->color ?? 'zinc'">{{ $appointment->status?->name ?? 'Pending' }}</flux:badge>
                                                </div>

                                                <div class="mt-3 grid gap-2">
                                                    <flux:select wire:change="changeStatusInline({{ $appointment->id }}, $event.target.value)">
                                                        @foreach ($this->appointmentStatuses as $status)
                                                            <option value="{{ $status->slug }}" @selected($appointment->status?->slug === $status->slug)>{{ $status->name }}</option>
                                                        @endforeach
                                                    </flux:select>

                                                    <div class="flex flex-wrap gap-2">
                                                        <flux:button size="sm" variant="ghost" icon="eye" wire:click="openDrawer({{ $appointment->id }})">Abrir</flux:button>
                                                        <flux:button size="sm" variant="ghost" icon="pencil-square" wire:click="openEditModal({{ $appointment->id }})">Editar</flux:button>
                                                    </div>
                                                </div>
                                            </article>
                                        @empty
                                            <div class="rounded-2xl border border-dashed border-zinc-300/70 bg-white/70 p-6 text-center text-sm text-zinc-500 dark:border-zinc-700 dark:bg-zinc-950/60 dark:text-zinc-400">
                                                No appointments in this period.
                                            </div>
                                        @endforelse
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </flux:card>
            </main>

            @if ($this->selectedAppointment)
                <aside class="space-y-6 xl:sticky xl:top-6 xl:self-start">
                    <flux:card class="border border-zinc-200/80 bg-white shadow-xl dark:border-zinc-700 dark:bg-zinc-900">
                        <div class="space-y-4 p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <flux:badge :color="$this->selectedAppointment->status?->color ?? 'zinc'">{{ $this->selectedAppointment->status?->name ?? 'Pending' }}</flux:badge>
                                    <flux:heading size="lg" class="mt-2">{{ $this->selectedAppointment->title }}</flux:heading>
                                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ $this->selectedAppointment->reference_code }}</flux:text>
                                </div>

                                <flux:button variant="ghost" size="sm" icon="x-mark" wire:click="closeDrawer" />
                            </div>

                            <div class="grid gap-3 rounded-2xl border border-zinc-200/70 p-3 dark:border-zinc-700">
                                <div>
                                    <div class="text-xs uppercase tracking-wide text-zinc-400">Client</div>
                                    <div class="mt-1 font-medium text-zinc-900 dark:text-zinc-100">{{ $this->selectedAppointment->client->fullName() }}</div>
                                    <div class="text-sm text-zinc-500">{{ $this->selectedAppointment->client->phone ?? 'No phone' }}</div>
                                </div>
                                <div>
                                    <div class="text-xs uppercase tracking-wide text-zinc-400">Service</div>
                                    <div class="mt-1 font-medium text-zinc-900 dark:text-zinc-100">{{ $this->selectedAppointment->service->name }}</div>
                                    <div class="text-sm text-zinc-500">S/ {{ number_format((float) $this->selectedAppointment->price, 2) }} · {{ $this->selectedAppointment->duration_minutes }} min</div>
                                </div>
                                <div>
                                    <div class="text-xs uppercase tracking-wide text-zinc-400">Schedule</div>
                                    <div class="mt-1 font-medium text-zinc-900 dark:text-zinc-100">{{ $this->selectedAppointment->starts_at?->format('d M Y H:i') }}</div>
                                    <div class="text-sm text-zinc-500">{{ $this->selectedAppointment->ends_at?->format('H:i') }} · {{ $this->selectedAppointment->timezone }}</div>
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <div class="text-xs uppercase tracking-wide text-zinc-400">Branch</div>
                                        <div class="mt-1 text-sm text-zinc-900 dark:text-zinc-100">{{ $this->selectedAppointment->branch->name }}</div>
                                    </div>
                                    <div>
                                        <div class="text-xs uppercase tracking-wide text-zinc-400">Resource</div>
                                        <div class="mt-1 text-sm text-zinc-900 dark:text-zinc-100">{{ $this->selectedAppointment->resource?->name ?? 'No resource' }}</div>
                                    </div>
                                    <div>
                                        <div class="text-xs uppercase tracking-wide text-zinc-400">Professional</div>
                                        <div class="mt-1 text-sm text-zinc-900 dark:text-zinc-100">{{ $this->selectedAppointment->professional?->fullName() ?? 'Unassigned' }}</div>
                                    </div>
                                    <div>
                                        <div class="text-xs uppercase tracking-wide text-zinc-400">Amount</div>
                                        <div class="mt-1 text-sm text-zinc-900 dark:text-zinc-100">S/ {{ number_format((float) $this->selectedAppointment->price, 2) }}</div>
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <flux:heading size="sm">Quick actions</flux:heading>
                                    <flux:button size="sm" variant="ghost" icon="pencil-square" wire:click="openEditModal({{ $this->selectedAppointment->id }})">Editar</flux:button>
                                </div>

                                <div class="grid grid-cols-2 gap-2">
                                    <flux:button variant="primary" wire:click="rescheduleSelected" icon="arrow-path">
                                        Reschedule
                                    </flux:button>
                                    <flux:button variant="ghost" wire:click="completeAppointment" icon="check-circle">
                                        Complete
                                    </flux:button>
                                    <flux:button variant="ghost" wire:click="markNoShow" icon="x-circle">
                                        No Show
                                    </flux:button>
                                    <flux:button variant="danger" wire:click="cancelAppointment" icon="x-circle">
                                        Cancel
                                    </flux:button>
                                </div>

                                <flux:input wire:model="statusReason" label="Motivo de cancelación" type="text" />
                            </div>

                            <div class="space-y-3">
                                <flux:heading size="sm">Notes</flux:heading>
                                <flux:textarea wire:model="noteDraft" rows="3" placeholder="Add an internal note..." />
                                <flux:button variant="primary" icon="plus" wire:click="addNote">Agregar nota</flux:button>

                                <div class="space-y-2">
                                    @forelse ($this->selectedAppointment->notes as $note)
                                        <div class="rounded-2xl border border-zinc-200/70 p-3 dark:border-zinc-700">
                                            <div class="flex items-center justify-between gap-3">
                                                <div class="text-xs uppercase tracking-wide text-zinc-400">{{ $note->is_internal ? 'Internal' : 'Public' }}</div>
                                                <div class="text-xs text-zinc-500">{{ $note->created_at?->diffForHumans() }}</div>
                                            </div>
                                            <div class="mt-2 text-sm text-zinc-700 dark:text-zinc-200">{{ $note->note }}</div>
                                        </div>
                                    @empty
                                        <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">No notes yet.</flux:text>
                                    @endforelse
                                </div>
                            </div>

                            <div class="space-y-3">
                                <flux:heading size="sm">Historial de pagos</flux:heading>
                                <div class="space-y-2">
                                    @forelse ($this->selectedAppointment->payments as $payment)
                                        <div class="rounded-2xl border border-zinc-200/70 p-3 dark:border-zinc-700">
                                            <div class="flex items-center justify-between">
                                                <div>
                                                    <div class="font-medium text-zinc-900 dark:text-zinc-100">S/ {{ number_format((float) $payment->amount, 2) }}</div>
                                                    <div class="text-xs text-zinc-500">{{ $payment->method }} · {{ $payment->status }}</div>
                                                </div>
                                                <div class="text-xs text-zinc-500">{{ $payment->paid_at?->diffForHumans() }}</div>
                                            </div>
                                        </div>
                                    @empty
                                        <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">No payments recorded.</flux:text>
                                    @endforelse
                                </div>
                            </div>

                            <div class="space-y-3">
                                <flux:heading size="sm">Timeline activity</flux:heading>
                                <div class="space-y-2">
                                    @forelse ($this->timelineEntries as $entry)
                                        <div class="flex gap-3 rounded-2xl border border-zinc-200/70 p-3 dark:border-zinc-700">
                                            <div class="mt-1 size-2 rounded-full bg-sky-400"></div>
                                            <div class="min-w-0">
                                                <div class="flex items-center justify-between gap-3">
                                                    <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $entry['title'] }}</div>
                                                    <div class="text-xs text-zinc-500">{{ $entry['created_at']?->diffForHumans() }}</div>
                                                </div>
                                                <div class="text-sm text-zinc-500">{{ $entry['description'] ?? $entry['user'] }}</div>
                                            </div>
                                        </div>
                                    @empty
                                        <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">No activity yet.</flux:text>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </flux:card>
                </aside>
            @endif
        </div>
    </div>

    <flux:modal
        name="appointment-form"
        wire:close="closeModal"
        wire:cancel="closeModal"
        class="w-full max-w-7xl"
    >
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $form->appointmentId ? 'Editar cita' : 'Nueva cita' }}</flux:heading>
                <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                    Create, update or reschedule an appointment with branch, resource and professional constraints.
                </flux:text>
            </div>

            <form wire:submit="save" class="space-y-6">
                <div class="grid gap-4 rounded-2xl border border-zinc-200/80 p-4 dark:border-zinc-700 md:grid-cols-2 xl:grid-cols-3">
                    <flux:select wire:model="form.branch_id" label="Branch *">
                        <option value="">Select</option>
                        @foreach ($this->branches as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model="form.client_id" label="Client *">
                        <option value="">Select</option>
                        @foreach (\App\Models\Client::query()->orderBy('first_name')->get() as $client)
                            <option value="{{ $client->id }}">{{ $client->fullName() }}</option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model.live="form.service_id" label="Service *">
                        <option value="">Select</option>
                        @foreach (\App\Models\Service::query()->orderBy('name')->get() as $service)
                            <option value="{{ $service->id }}">{{ $service->name }}</option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model="form.professional_id" label="Professional">
                        <option value="">Select</option>
                        @foreach ($this->professionalsCatalog as $professional)
                            <option value="{{ $professional->id }}">{{ $professional->fullName() }}</option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model="form.resource_id" label="Resource">
                        <option value="">Select</option>
                        @foreach ($this->resourcesCatalog as $resource)
                            <option value="{{ $resource->id }}">{{ $resource->name }}</option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model="form.status_slug" label="Estado *">
                        @foreach ($this->appointmentStatuses as $status)
                            <option value="{{ $status->slug }}">{{ $status->name }}</option>
                        @endforeach
                    </flux:select>

                    <flux:input wire:model="form.title" label="Title *" type="text" class="md:col-span-2" />
                    <flux:input wire:model="form.starts_at" label="Starts at *" type="datetime-local" />
                    <flux:input wire:model="form.ends_at" label="Ends at *" type="datetime-local" />
                    <flux:input wire:model="form.duration_minutes" label="Duration (min) *" type="number" min="15" step="15" />
                    <flux:input wire:model="form.price" label="Price *" type="number" step="0.01" min="0" />
                    <flux:input wire:model="form.currency" label="Currency *" type="text" maxlength="3" />
                    <flux:input wire:model="form.timezone" label="Timezone *" type="text" />

                    <div class="md:col-span-2 xl:col-span-3">
                        <flux:textarea wire:model="form.notes" label="Notes" rows="4" />
                    </div>

                    <div class="md:col-span-2 xl:col-span-3">
                        <flux:input wire:model="form.cancellation_reason" label="Motivo de cancelación" type="text" />
                    </div>
                </div>

                <div class="flex flex-col-reverse gap-3 border-t border-zinc-200/80 pt-4 dark:border-zinc-700 sm:flex-row sm:items-center sm:justify-end">
                    <flux:modal.close>
                        <flux:button variant="ghost" type="button" wire:click="closeModal">Cancelar</flux:button>
                    </flux:modal.close>

                    <flux:button variant="primary" type="submit">
                        {{ $form->appointmentId ? 'Save changes' : 'Create appointment' }}
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>
</section>
