@php
    $professionalTabs = [
        'basic' => 'Datos básicos',
        'schedule' => 'Horario',
        'profile' => 'Perfil',
    ];
@endphp

<section >
    <div class="relative w-full overflow-hidden rounded-[24px]">
        <div class="space-y-5 px-1 py-2 sm:px-3 lg:px-0">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div class="min-w-0">
                    <h1 class="text-[2rem] font-semibold tracking-tight text-slate-900 dark:text-white">Profesionales <span class="text-[1.55rem] font-semibold text-slate-700 dark:text-zinc-300">(Estilistas)</span></h1>
                    <p class="mt-2 text-sm text-slate-600 dark:text-zinc-400">Gestiona a tu equipo de estilistas, sus horarios, servicios e información pública.</p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    @if ($sectionTab === 'professionals')
                        <flux:button variant="primary" icon="plus" wire:click="openCreateModal" class="h-11 rounded-xl bg-emerald-600 px-4 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700 dark:bg-emerald-600 dark:shadow-none">
                            Nuevo Profesional
                        </flux:button>
                    @else
                        <flux:button variant="primary" icon="plus" wire:click="openCreateGroupModal" class="h-11 rounded-xl bg-emerald-600 px-4 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700 dark:bg-emerald-600 dark:shadow-none">
                            Nuevo Grupo
                        </flux:button>
                    @endif
                </div>
            </div>

            <div class="overflow-hidden rounded-[24px] border border-zinc-200 bg-white shadow-sm dark:border-white/10 dark:bg-[#111820] dark:shadow-none">
                <div class="border-b border-zinc-200 px-4 pt-2 dark:border-white/10">
                    <div class="flex flex-wrap gap-2">
                        <button
                            type="button"
                            wire:click="switchSection('professionals')"
                            @class([
                                'inline-flex items-center gap-2 rounded-t-[18px] border-b-2 px-4 py-3 text-sm font-semibold transition',
                                'border-emerald-600 text-emerald-700 dark:border-emerald-400 dark:text-emerald-300' => $sectionTab === 'professionals',
                                'border-transparent text-zinc-500 hover:text-slate-800 dark:text-zinc-400 dark:hover:text-white' => $sectionTab !== 'professionals',
                            ])
                        >
                            <span>Profesionales</span>
                        </button>
                        <button
                            type="button"
                            wire:click="switchSection('groups')"
                            @class([
                                'inline-flex items-center gap-2 rounded-t-[18px] border-b-2 px-4 py-3 text-sm font-semibold transition',
                                'border-emerald-600 text-emerald-700 dark:border-emerald-400 dark:text-emerald-300' => $sectionTab === 'groups',
                                'border-transparent text-zinc-500 hover:text-slate-800 dark:text-zinc-400 dark:hover:text-white' => $sectionTab !== 'groups',
                            ])
                        >
                            <span>Grupos Personalizados</span>
                        </button>
                    </div>
                </div>

                <div class="border-b border-zinc-200 px-4 py-4 dark:border-white/10">
                    <div class="rounded-2xl border border-violet-200 bg-violet-50 px-4 py-4 text-sm text-violet-800 dark:border-violet-500/20 dark:bg-violet-500/10 dark:text-violet-200">
                        @if ($sectionTab === 'professionals')
                            <span class="font-semibold">Edita a tu primer profesional</span> y luego agrega más personas a tu equipo de trabajo. Puedes editar sus horarios, qué servicios realizan y su perfil público.
                        @else
                            <span class="font-semibold">En esta sección</span> podrás crear grupos de profesionales para luego previsualizarlos en la agenda.
                        @endif
                    </div>
                </div>

                <div class="grid gap-4 border-b border-zinc-200 px-4 py-4 dark:border-white/10 lg:grid-cols-[minmax(0,1.25fr)_minmax(12rem,0.8fr)_minmax(12rem,0.8fr)_minmax(10rem,0.7fr)]">
                    @if ($sectionTab === 'professionals')
                        <div class="flex flex-col gap-1.5">
                            <label class="text-xs font-semibold uppercase tracking-[0.12em] text-transparent select-none">Buscar</label>
                            <flux:input
                                wire:model.live.debounce.300ms="search"
                                icon="magnifying-glass"
                                clearable
                                placeholder="Buscar profesional por nombre..."
                                class="h-12 rounded-xl border-zinc-200 bg-white shadow-none dark:border-white/10 dark:bg-[#0d131a] dark:text-white"
                            />
                        </div>
                    @else
                        <div class="flex flex-col gap-1.5">
                            <label class="text-xs font-semibold uppercase tracking-[0.12em] text-transparent select-none">Buscar</label>
                            <flux:input
                                wire:model.live.debounce.300ms="groupSearch"
                                icon="magnifying-glass"
                                clearable
                                placeholder="Buscar por grupo o local..."
                                class="h-12 rounded-xl border-zinc-200 bg-white shadow-none dark:border-white/10 dark:bg-[#0d131a] dark:text-white"
                            />
                        </div>
                    @endif

                    <div class="flex flex-col gap-1.5">
                        <label class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-700 dark:text-zinc-300">Estado</label>
                        <flux:select wire:model.live="statusFilter" class="h-12 rounded-xl border-zinc-200 bg-white text-sm shadow-none dark:border-white/10 dark:bg-[#0d131a] dark:text-white">
                            <option value="">Estado: Todos</option>
                            <option value="active">Activos</option>
                            <option value="inactive">Inactivos</option>
                        </flux:select>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-700 dark:text-zinc-300">Local</label>
                        <flux:select wire:model.live="locationFilter" class="h-12 rounded-xl border-zinc-200 bg-white text-sm shadow-none dark:border-white/10 dark:bg-[#0d131a] dark:text-white">
                            <option value="">Todos los locales</option>
                            @foreach ($this->locationsCatalog as $location)
                                <option value="{{ $location->id }}">{{ $location->name }}</option>
                            @endforeach
                        </flux:select>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-700 dark:text-zinc-300">Por página</label>
                        <flux:select wire:model.live="perPage" class="h-12 rounded-xl border-zinc-200 bg-white text-sm shadow-none dark:border-white/10 dark:bg-[#0d131a] dark:text-white">
                            <option value="10">10 por página</option>
                            <option value="25">25 por página</option>
                            <option value="50">50 por página</option>
                        </flux:select>
                    </div>
                </div>

                @if ($sectionTab === 'professionals')
                    <div class="p-4 sm:p-5">
                        @if ($professionals->isEmpty())
                            <div class="flex flex-col items-center justify-center gap-3 px-6 py-20 text-center">
                                <div class="flex size-16 items-center justify-center rounded-3xl bg-zinc-100 text-zinc-500 dark:bg-white/[0.04] dark:text-zinc-300">
                                    <flux:icon.users class="size-8" />
                                </div>
                                <flux:heading size="lg" class="text-slate-900 dark:text-white">No hay profesionales para mostrar</flux:heading>
                                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                                    Crea tu primer profesional o ajusta los filtros para intentar de nuevo.
                                </flux:text>
                            </div>
                        @else
                            <div class="grid gap-4 xl:grid-cols-[minmax(20rem,0.92fr)_minmax(0,1.5fr)]">
                                <div class="overflow-hidden rounded-[22px] border border-zinc-200 bg-white dark:border-white/10 dark:bg-[#0f1720]">
                                    <div class="space-y-2 p-3">
                                        @foreach ($professionals as $professional)
                                            @php
                                                $isSelected = $currentProfessional?->id === $professional->id;
                                            @endphp
                                            <button
                                                type="button"
                                                wire:click="selectProfessional({{ $professional->id }})"
                                                class="flex w-full items-start gap-3 rounded-[18px] border px-4 py-4 text-left transition {{ $isSelected ? 'border-violet-300 bg-violet-50/70 dark:border-violet-500/30 dark:bg-violet-500/10' : 'border-zinc-200 bg-white hover:border-zinc-300 dark:border-white/10 dark:bg-white/[0.02] dark:hover:bg-white/[0.04]' }}"
                                            >
                                                @if ($professional->photoUrl())
                                                    <img src="{{ $professional->photoUrl() }}" alt="{{ $professional->displayName() }}" class="size-11 rounded-full object-cover">
                                                @else
                                                    <div class="flex size-11 shrink-0 items-center justify-center rounded-full bg-emerald-50 text-sm font-semibold text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">
                                                        {{ $professional->initials() }}
                                                    </div>
                                                @endif

                                                <div class="min-w-0 flex-1">
                                                    <div class="flex items-center gap-2">
                                                        <span class="truncate text-sm font-semibold text-slate-900 dark:text-white">{{ $professional->displayName() }}</span>
                                                        <span class="inline-flex items-center gap-1 text-xs {{ $professional->is_active ? 'text-emerald-600 dark:text-emerald-400' : 'text-zinc-500 dark:text-zinc-400' }}">
                                                            <span class="size-1.5 rounded-full {{ $professional->is_active ? 'bg-emerald-500' : 'bg-zinc-400' }}"></span>
                                                            {{ $professional->is_active ? 'Activo' : 'Inactivo' }}
                                                        </span>
                                                    </div>
                                                    <div class="mt-1 line-clamp-2 text-xs text-zinc-500 dark:text-zinc-400">
                                                        {{ $professional->services->pluck('name')->take(4)->join(', ') ?: 'Sin servicios asignados' }}
                                                    </div>
                                                </div>

                                                <flux:icon name="chevron-right" class="mt-1 size-4 shrink-0 {{ $isSelected ? 'text-violet-500 dark:text-violet-300' : 'text-zinc-400' }}" />
                                            </button>
                                        @endforeach
                                    </div>

                                    <div class="border-t border-zinc-200 px-4 py-4 dark:border-white/10">
                                        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                                            <div class="text-sm text-slate-600 dark:text-zinc-400">
                                                Mostrando {{ $professionals->firstItem() }} a {{ $professionals->lastItem() }} de {{ $professionals->total() }} profesionales
                                            </div>
                                            <div>
                                                {{ $professionals->links('vendor.pagination.livewire-table-clean') }}
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="rounded-[22px] border border-zinc-200 bg-white p-5 dark:border-white/10 dark:bg-[#0f1720]">
                                    @if ($currentProfessional)
                                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                            <div class="flex min-w-0 items-center gap-4">
                                                @if ($currentProfessional->photoUrl())
                                                    <img src="{{ $currentProfessional->photoUrl() }}" alt="{{ $currentProfessional->displayName() }}" class="size-16 rounded-full object-cover">
                                                @else
                                                    <div class="flex size-16 shrink-0 items-center justify-center rounded-full bg-emerald-50 text-2xl font-semibold text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">
                                                        {{ $currentProfessional->initials() }}
                                                    </div>
                                                @endif

                                                <div class="min-w-0">
                                                    <div class="flex flex-wrap items-center gap-2">
                                                        <h2 class="text-2xl font-semibold tracking-tight text-slate-900 dark:text-white">{{ $currentProfessional->displayName() }}</h2>
                                                        <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $currentProfessional->is_active ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300' : 'bg-zinc-100 text-zinc-600 dark:bg-white/[0.05] dark:text-zinc-400' }}">
                                                            {{ $currentProfessional->is_active ? 'Activo' : 'Inactivo' }}
                                                        </span>
                                                    </div>

                                                    <div class="mt-2 space-y-1 text-sm text-zinc-500 dark:text-zinc-400">
                                                        <div>Correo: {{ $currentProfessional->email ?: 'Sin correo' }}</div>
                                                        <div>Acceso al sistema: {{ $currentProfessional->has_system_access ? 'Sí' : 'No' }}</div>
                                                        <div>Reservas online: {{ $currentProfessional->accepts_online_bookings ? 'Activas' : 'Inactivas' }}</div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="flex flex-wrap gap-2">
                                                <flux:button size="sm" variant="ghost" icon="pencil-square" wire:click="openEditModal({{ $currentProfessional->id }})" class="rounded-xl border border-violet-200 bg-white text-violet-700 hover:bg-violet-50 dark:border-violet-500/20 dark:bg-white/[0.03] dark:text-violet-200">
                                                    Editar
                                                </flux:button>
                                                <flux:button size="sm" variant="ghost" icon="calendar-days" wire:click="openSchedulePreview({{ $currentProfessional->id }})" class="rounded-xl border border-zinc-200 bg-white text-slate-700 hover:bg-zinc-50 dark:border-white/10 dark:bg-white/[0.03] dark:text-white">
                                                    Ver y editar horario
                                                </flux:button>
                                            </div>
                                        </div>

                                        <div class="mt-8">
                                            <div class="text-sm font-semibold text-slate-900 dark:text-white">Servicios que realiza</div>
                                            <div class="mt-3 flex flex-wrap gap-2">
                                                @forelse ($currentProfessional->services->take(8) as $service)
                                                    <span class="inline-flex items-center rounded-full border border-zinc-200 bg-zinc-50 px-2.5 py-1 text-xs font-medium text-zinc-600 dark:border-white/10 dark:bg-white/[0.04] dark:text-zinc-200">
                                                        {{ $service->name }}
                                                    </span>
                                                @empty
                                                    <span class="text-sm text-zinc-500 dark:text-zinc-400">Sin servicios asignados.</span>
                                                @endforelse

                                                @if ($currentProfessional->services->count() > 8)
                                                    <span class="inline-flex items-center rounded-full border border-sky-200 bg-sky-50 px-2.5 py-1 text-xs font-semibold text-sky-600 dark:border-sky-500/20 dark:bg-sky-500/10 dark:text-sky-300">
                                                        +{{ $currentProfessional->services->count() - 8 }} más
                                                    </span>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="mt-8 grid gap-4 lg:grid-cols-3">
                                            <div class="rounded-[20px] border border-zinc-200 bg-white p-4 dark:border-white/10 dark:bg-white/[0.03]">
                                                <div class="text-2xl font-semibold text-slate-900 dark:text-white">{{ $currentProfessional->services->count() }}</div>
                                                <div class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Servicios asignados</div>
                                            </div>
                                            <div class="rounded-[20px] border border-zinc-200 bg-white p-4 dark:border-white/10 dark:bg-white/[0.03]">
                                                <div class="text-2xl font-semibold text-slate-900 dark:text-white">{{ $currentProfessional->locations->count() }}</div>
                                                <div class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Locales vinculados</div>
                                            </div>
                                            <div class="rounded-[20px] border border-zinc-200 bg-white p-4 dark:border-white/10 dark:bg-white/[0.03]">
                                                <div class="text-2xl font-semibold text-slate-900 dark:text-white">{{ $currentProfessional->groups->count() }}</div>
                                                <div class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Grupos asignados</div>
                                            </div>
                                        </div>

                                        <div class="mt-8">
                                            <div class="mb-3 text-sm font-semibold text-slate-900 dark:text-white">Horario semanal</div>
                                            <div class="grid gap-3 md:grid-cols-3 xl:grid-cols-7">
                                                @foreach ($currentProfessional->schedules as $schedule)
                                                    <div class="rounded-[18px] border border-zinc-200 bg-white p-3 dark:border-white/10 dark:bg-white/[0.03]">
                                                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-700 dark:text-zinc-200">
                                                            {{ \App\Livewire\Forms\ProfessionalForm::dayLabels()[$schedule->day_of_week] }}
                                                        </div>
                                                        <div class="mt-2 text-sm font-medium text-slate-900 dark:text-white">
                                                            {{ $schedule->is_working ? (($schedule->starts_at ?? '--:--').' - '.($schedule->ends_at ?? '--:--')) : 'Descanso' }}
                                                        </div>
                                                        <div class="mt-2">
                                                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $schedule->is_working ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300' : 'bg-zinc-100 text-zinc-600 dark:bg-white/[0.05] dark:text-zinc-400' }}">
                                                                {{ $schedule->is_working ? 'Laboral' : 'Descanso' }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>

                                        <div class="mt-8 grid gap-4 lg:grid-cols-2">
                                            <div class="rounded-[20px] border border-zinc-200 bg-white p-4 dark:border-white/10 dark:bg-white/[0.03]">
                                                <div class="text-sm font-semibold text-slate-900 dark:text-white">Información básica</div>
                                                <div class="mt-4 space-y-3 text-sm text-zinc-600 dark:text-zinc-300">
                                                    <div>Correo: {{ $currentProfessional->email ?: 'Sin correo' }}</div>
                                                    <div>Estado: {{ $currentProfessional->is_active ? 'Activo' : 'Inactivo' }}</div>
                                                    <div>Locales: {{ $currentProfessional->locations->pluck('name')->join(', ') ?: 'Sin locales' }}</div>
                                                </div>
                                            </div>

                                            <div class="rounded-[20px] border border-zinc-200 bg-white p-4 dark:border-white/10 dark:bg-white/[0.03]">
                                                <div class="text-sm font-semibold text-slate-900 dark:text-white">Perfil público</div>
                                                <div class="mt-4 text-sm text-zinc-600 dark:text-zinc-300">
                                                    {{ $currentProfessional->bio ?: 'Aún no tiene biografía pública registrada.' }}
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                @else
                <div class="space-y-4 px-4 py-6 sm:px-6">
                    @forelse ($groups as $group)
                        <div class="rounded-2xl border border-zinc-200/80 px-4 py-4 dark:border-zinc-700">
                            <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                                <div>
                                    <div class="text-base font-semibold text-zinc-900 dark:text-zinc-50">{{ $group->name }}</div>
                                    <div class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                                        {{ $group->location?->name }} · {{ $group->professionals->count() }} profesionales
                                    </div>
                                </div>

                                <div class="flex flex-wrap items-center gap-2">
                                    <flux:badge :color="$group->is_active ? 'emerald' : 'zinc'">
                                        {{ $group->is_active ? 'Activo' : 'Inactivo' }}
                                    </flux:badge>
                                    <flux:button size="sm" variant="ghost" icon="pencil-square" wire:click="openEditGroupModal({{ $group->id }})">
                                        Editar
                                    </flux:button>
                                    <flux:button size="sm" variant="danger" icon="trash" wire:click="confirmGroupDelete({{ $group->id }})">
                                        Eliminar
                                    </flux:button>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="flex flex-col items-center justify-center gap-3 px-6 py-20 text-center">
                            <div class="flex size-20 items-center justify-center rounded-3xl border border-dashed border-zinc-300 text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                                <flux:icon.magnifying-glass class="size-9" />
                            </div>
                            <flux:heading size="lg">No hay resultados para mostrar</flux:heading>
                        </div>
                    @endforelse

                    @if ($groups->isNotEmpty())
                        <flux:pagination :paginator="$groups" />
                    @endif
                </div>
                @endif
            </div>
        </div>
    </div>

    <flux:modal name="upsert-professional" wire:close="closeModal" wire:cancel="closeModal" class="w-full max-w-6xl">
        <div class="space-y-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <flux:heading size="lg">{{ $isEditing ? 'Editar Profesional' : 'Nuevo Profesional' }}</flux:heading>
                </div>
            </div>

            <div class="border-b border-violet-300/80">
                <div class="flex flex-wrap gap-2">
                    @foreach ($professionalTabs as $tabKey => $label)
                        <button
                            type="button"
                            wire:key="professional-tab-{{ $tabKey }}"
                            wire:click="setProfessionalModalTab('{{ $tabKey }}')"
                            class="{{ $professionalModalTab === $tabKey ? 'border-violet-500 bg-white text-violet-600' : 'border-transparent bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300' }} rounded-t-2xl border border-b-0 px-4 py-3 text-sm font-semibold transition"
                        >
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </div>

            <form wire:submit="save" class="space-y-6" wire:key="professional-form-{{ $form->professionalId ?? 'new' }}-{{ $professionalModalTab }}">
                @if ($professionalModalTab === 'basic')
                    <div class="space-y-5 rounded-2xl border border-zinc-200/80 p-4 dark:border-zinc-700">
                        <div class="grid gap-4">
                            <flux:input wire:model="form.public_name" label="Nombre Público" type="text" placeholder="Nombre Público" />

                            <div class="grid gap-4 rounded-2xl border border-zinc-200/80 p-4 dark:border-zinc-700 md:grid-cols-2">
                                <div class="space-y-2">
                                    <flux:switch
                                        wire:model.live="form.is_active"
                                        label="Profesional activo"
                                        description="Permite que el profesional aparezca operativo en el sistema."
                                        align="left"
                                    />
                                    <flux:badge :color="$form->is_active ? 'emerald' : 'zinc'">
                                        {{ $form->is_active ? 'Profesional activo' : 'Profesional inactivo' }}
                                    </flux:badge>
                                </div>

                                <div class="space-y-2">
                                    <flux:switch
                                        wire:model.live="form.accepts_online_bookings"
                                        label="Acepta reservas online"
                                        description="Permite seleccionarlo desde el Perfil web. Requiere acceso al sistema."
                                        align="left"
                                    />
                                    <flux:badge :color="$form->accepts_online_bookings ? 'emerald' : 'amber'">
                                        {{ $form->accepts_online_bookings ? 'Reservas online activadas' : 'Reservas online desactivadas' }}
                                    </flux:badge>
                                    @error('form.accepts_online_bookings')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
                                </div>
                            </div>

                            <div class="space-y-3 rounded-2xl border border-zinc-200/80 p-4 dark:border-zinc-700">
                                <flux:switch
                                    wire:model.live="form.has_system_access"
                                    label="Acceso al sistema"
                                    description="Crea o mantiene un usuario para que el profesional pueda gestionar su agenda."
                                    align="left"
                                />

                                @if ($form->has_system_access)
                                    <flux:input wire:model="form.email" label="Email" type="email" placeholder="Ingresa el email del profesional" />
                                @endif
                            </div>
                        </div>
                    </div>


                        <div class="mt-4 space-y-4 rounded-2xl border border-zinc-200/70 p-4 dark:border-zinc-700">
                            <flux:button type="button" variant="ghost" icon="check" wire:click="selectAllServices">
                                Seleccionar Todo
                            </flux:button>

                            @foreach ($this->serviceCategories as $category)
                                <div class="rounded-2xl border border-zinc-200/70 dark:border-zinc-700">
                                    <div class="flex items-center justify-between border-b border-zinc-200/70 px-4 py-3 dark:border-zinc-700">
                                        <div class="text-sm font-semibold">{{ $category->name }}</div>
                                        <div class="text-sm text-zinc-500">{{ $category->services->count() }}</div>
                                    </div>
                                    <div class="grid gap-3 px-4 py-4 md:grid-cols-2">
                                        @foreach ($category->services as $service)
                                            <flux:checkbox
                                                wire:key="professional-service-{{ $category->id }}-{{ $service->id }}"
                                                wire:model.live="form.service_ids"
                                                value="{{ $service->id }}"
                                                :label="$service->name"
                                            />
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                @elseif ($professionalModalTab === 'schedule')
                    <div class="rounded-2xl border border-zinc-200/80 p-4 dark:border-zinc-700">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                                <thead>
                                    <tr class="text-left text-sm font-semibold text-zinc-600 dark:text-zinc-300">
                                        <th class="px-4 py-3">Día</th>
                                        <th class="px-4 py-3">Estado</th>
                                        <th class="px-4 py-3">Inicio de la jornada</th>
                                        <th class="px-4 py-3">Fin de la jornada</th>
                                        <th class="px-4 py-3">Descanso</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                                    @foreach ($form->schedules as $index => $schedule)
                                        @php($isWorking = (bool) data_get($form->schedules, "{$index}.is_working"))
                                        <tr wire:key="professional-schedule-{{ $schedule['day_of_week'] }}">
                                            <td class="px-4 py-4 font-medium">{{ $schedule['label'] }}</td>
                                            <td class="px-4 py-4">
                                                <flux:switch wire:model.live="form.schedules.{{ $index }}.is_working" align="left" />
                                            </td>
                                            <td class="px-4 py-4">
                                                @if ($isWorking)
                                                    <flux:input wire:model="form.schedules.{{ $index }}.starts_at" type="time" />
                                                @else
                                                    <span class="text-sm text-zinc-500">Local cerrado</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-4">
                                                @if ($isWorking)
                                                    <flux:input wire:model="form.schedules.{{ $index }}.ends_at" type="time" />
                                                @else
                                                    <span class="text-sm text-zinc-500">Local cerrado</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-4">
                                                @if ($isWorking)
                                                    <div class="space-y-3">
                                                        <div class="flex flex-wrap items-center gap-2">
                                                            <flux:button type="button" size="sm" variant="ghost" icon="plus" wire:click="addBreak({{ $index }})">
                                                                Descanso
                                                            </flux:button>

                                                            @if ($index === 0)
                                                                <button type="button" wire:click="copyScheduleToAll({{ $index }})" class="text-sm font-medium text-zinc-800 underline underline-offset-4 dark:text-zinc-200">
                                                                    Copiar en todos
                                                                </button>
                                                            @endif
                                                        </div>

                                                        @forelse ($schedule['breaks'] as $breakIndex => $break)
                                                            <div class="flex flex-wrap items-center gap-2" wire:key="professional-schedule-break-{{ $schedule['day_of_week'] }}-{{ $breakIndex }}">
                                                                <flux:input wire:model="form.schedules.{{ $index }}.breaks.{{ $breakIndex }}.starts_at" type="time" />
                                                                <span class="text-sm text-zinc-500">a</span>
                                                                <flux:input wire:model="form.schedules.{{ $index }}.breaks.{{ $breakIndex }}.ends_at" type="time" />
                                                                <flux:button type="button" size="sm" variant="ghost" icon="trash" wire:click="removeBreak({{ $index }}, {{ $breakIndex }})">
                                                                    Quitar
                                                                </flux:button>
                                                            </div>
                                                        @empty
                                                            <div class="text-sm text-zinc-500">Sin descansos configurados.</div>
                                                        @endforelse
                                                    </div>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @else
                    <div class="space-y-5 rounded-2xl border border-zinc-200/80 p-4 dark:border-zinc-700">
                        <div class="space-y-4">
                            <flux:textarea
                                wire:model="form.bio"
                                label="Biografía"
                                rows="5"
                                placeholder="Incluye una breve biografía del profesional"
                            />

                            <div class="text-sm text-zinc-500 dark:text-zinc-400">
                                Será visible en el sitio y marketplace. Máximo 600 caracteres.
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <div class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">Foto del profesional</div>
                                <div class="text-sm text-zinc-500 dark:text-zinc-400">
                                    Te recomendamos una imagen mínima de 100x100 px y un peso máximo de 3 MB.
                                </div>
                            </div>

                            <flux:input wire:model="form.photo" type="file" accept="image/*" />

                            @if ($form->photo)
                                <img src="{{ $form->photo->temporaryUrl() }}" alt="Vista previa profesional" class="h-40 w-40 rounded-3xl object-cover">
                            @elseif ($form->existingPhotoPath)
                                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($form->existingPhotoPath) }}" alt="Foto actual profesional" class="h-40 w-40 rounded-3xl object-cover">
                            @endif
                        </div>
                    </div>
                @endif

                <div class="flex flex-col-reverse gap-3 border-t border-zinc-200/80 pt-4 dark:border-zinc-700 sm:flex-row sm:items-center sm:justify-between">
                    <flux:modal.close>
                        <flux:button type="button" variant="ghost" wire:click="closeModal">
                            Cerrar
                        </flux:button>
                    </flux:modal.close>

                    <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="save">
                        <span wire:loading.remove wire:target="save">Guardar</span>
                        <span wire:loading wire:target="save">Guardando...</span>
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    <flux:modal name="professional-schedule-preview" wire:close="closeSchedulePreview" wire:cancel="closeSchedulePreview" class="w-full max-w-6xl">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">
                    Horario de {{ $schedulePreviewProfessional?->displayName() }}
                </flux:heading>
            </div>

            @if ($schedulePreviewProfessional)
                <div class="rounded-2xl border border-zinc-200/80 p-4 dark:border-zinc-700">
                    <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                        <thead>
                            <tr class="text-left text-sm font-semibold text-zinc-600 dark:text-zinc-300">
                                <th class="px-4 py-3">Día</th>
                                <th class="px-4 py-3">Estado</th>
                                <th class="px-4 py-3">Inicio</th>
                                <th class="px-4 py-3">Fin</th>
                                <th class="px-4 py-3">Descansos</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                            @foreach ($schedulePreviewProfessional->schedules as $schedule)
                                <tr wire:key="schedule-preview-{{ $schedule->day_of_week }}">
                                    <td class="px-4 py-4">{{ \App\Livewire\Forms\ProfessionalForm::dayLabels()[$schedule->day_of_week] }}</td>
                                    <td class="px-4 py-4">{{ $schedule->is_working ? 'Activo' : 'Cerrado' }}</td>
                                    <td class="px-4 py-4">{{ $schedule->starts_at ?? 'Local cerrado' }}</td>
                                    <td class="px-4 py-4">{{ $schedule->ends_at ?? 'Local cerrado' }}</td>
                                    <td class="px-4 py-4">
                                        {{ $schedule->breaks->map(fn ($break) => $break->starts_at.' - '.$break->ends_at)->join(', ') ?: 'Sin descansos' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <div class="flex justify-start">
                <flux:modal.close>
                    <flux:button type="button" variant="ghost" wire:click="closeSchedulePreview">
                        Cerrar
                    </flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>

    <flux:modal name="upsert-group" wire:close="closeGroupModal" wire:cancel="closeGroupModal" class="w-full max-w-4xl">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $isGroupEditing ? 'Editar grupo' : 'Crea un nuevo grupo' }}</flux:heading>
            </div>

            <form wire:submit="saveGroup" class="space-y-6">
                <div class="rounded-2xl border border-zinc-200/80 p-4 dark:border-zinc-700">
                    <div class="grid gap-4">
                        <flux:input wire:model="groupForm.name" label="Nombre del grupo *" type="text" placeholder="Escribe aquí el nombre del grupo" />

                        <flux:select wire:model.live="groupForm.location_id" label="Selecciona un local *">
                            <option value="">Selecciona una sucursal</option>
                        @foreach ($this->locationsCatalog as $location)
                            <option value="{{ $location->id }}">{{ $location->name }}</option>
                        @endforeach
                    </flux:select>

                    @if ($isGroupEditing && $groupForm->location_id)
                            <div class="space-y-3 rounded-2xl border border-zinc-200/70 p-4 dark:border-zinc-700">
                                <div class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">Profesionales del grupo</div>
                                <div class="grid gap-3 md:grid-cols-2">
                                        @forelse ($this->eligibleProfessionalsForGroup as $professional)
                                            <flux:checkbox
                                                wire:key="group-member-{{ $professional->id }}"
                                                wire:model.live="groupForm.member_ids"
                                                value="{{ $professional->id }}"
                                                :label="$professional->displayName()"
                                            />
                                    @empty
                                        <div class="text-sm text-zinc-500">No hay profesionales disponibles para este local.</div>
                                    @endforelse
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="flex flex-col-reverse gap-3 border-t border-zinc-200/80 pt-4 dark:border-zinc-700 sm:flex-row sm:items-center sm:justify-between">
                    <flux:modal.close>
                        <flux:button type="button" variant="ghost" wire:click="closeGroupModal">
                            Cancelar
                        </flux:button>
                    </flux:modal.close>

                    <flux:button type="submit" variant="primary">
                        Guardar
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    <flux:modal name="delete-professional" wire:close="closeDeleteModal" wire:cancel="closeDeleteModal" class="w-full max-w-lg">
        <div class="space-y-5">
            <div>
                <flux:heading size="lg">Eliminar profesional</flux:heading>
                <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                    @if ($professionalPendingDeletion)
                        Esta acción desactivará a {{ $professionalPendingDeletion->displayName() }} y su acceso al sistema.
                    @endif
                </flux:text>
            </div>

            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <flux:modal.close>
                    <flux:button variant="ghost" type="button" wire:click="closeDeleteModal">
                        Cancelar
                    </flux:button>
                </flux:modal.close>

                <flux:button variant="danger" type="button" wire:click="delete">
                    Eliminar
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <flux:modal name="delete-group" wire:close="closeGroupDeleteModal" wire:cancel="closeGroupDeleteModal" class="w-full max-w-lg">
        <div class="space-y-5">
            <div>
                <flux:heading size="lg">Eliminar grupo</flux:heading>
                <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                    @if ($groupPendingDeletion)
                        Esta acción eliminará el grupo {{ $groupPendingDeletion->name }}.
                    @endif
                </flux:text>
            </div>

            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <flux:modal.close>
                    <flux:button variant="ghost" type="button" wire:click="closeGroupDeleteModal">
                        Cancelar
                    </flux:button>
                </flux:modal.close>

                <flux:button variant="danger" type="button" wire:click="deleteGroup">
                    Eliminar
                </flux:button>
            </div>
        </div>
    </flux:modal>
</section>
