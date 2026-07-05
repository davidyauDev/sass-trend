<section >
    @php
        $hasActiveFilters = $search !== '' || $categoryFilter !== '' || $statusFilter !== '';
    @endphp

    <div class="relative w-full overflow-hidden rounded-[24px]">
        <div class="space-y-5 px-1 py-2 sm:px-3 lg:px-0">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div class="min-w-0">
                    <h1 class="text-[2rem] font-semibold tracking-tight text-slate-900 dark:text-white">Servicios</h1>
                    <p class="mt-2 text-sm text-slate-600 dark:text-zinc-400">Gestiona todas las categorías y servicios de tu negocio.</p>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-end">
                    <flux:button
                        variant="outline"
                        icon="plus"
                        wire:click="openCategoryModal"
                        class="h-11 rounded-xl border-zinc-200 bg-white px-4 text-sm font-semibold text-slate-700 shadow-sm dark:border-white/10 dark:bg-white/[0.02] dark:text-white dark:shadow-none"
                    >
                        Nueva categoría
                    </flux:button>

                    <flux:button
                        variant="primary"
                        icon="plus"
                        wire:click="openCreateModal"
                        class="h-11 rounded-xl bg-emerald-600 px-4 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700 dark:bg-emerald-600 dark:shadow-none"
                    >
                        Nuevo servicio
                    </flux:button>
                </div>
            </div>

            <div class="rounded-[24px] border border-zinc-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-[#111820] dark:shadow-none">
                <div class="grid items-end gap-4 lg:grid-cols-[minmax(18rem,1.65fr)_minmax(12rem,0.95fr)_minmax(12rem,0.95fr)_minmax(10rem,0.85fr)]">
                    <div class="flex flex-col gap-1.5">
                        <label class="text-xs font-semibold uppercase tracking-[0.12em] text-transparent select-none">Buscar</label>
                        <flux:input
                            wire:model.live.debounce.300ms="search"
                            icon="magnifying-glass"
                            clearable
                            placeholder="Buscar por nombre o categoría"
                            class="h-12 rounded-xl border-zinc-200 bg-white shadow-none dark:border-white/10 dark:bg-[#0d131a] dark:text-white"
                        />
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-700 dark:text-zinc-300">Categoría</label>
                        <flux:select
                            wire:model.live="categoryFilter"
                            class="h-12 rounded-xl border-zinc-200 bg-white text-sm shadow-none dark:border-white/10 dark:bg-[#0d131a] dark:text-white"
                        >
                            <option value="">Todas las categorías</option>
                            @foreach ($this->categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </flux:select>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-700 dark:text-zinc-300">Estado</label>
                        <flux:select
                            wire:model.live="statusFilter"
                            class="h-12 rounded-xl border-zinc-200 bg-white text-sm shadow-none dark:border-white/10 dark:bg-[#0d131a] dark:text-white"
                        >
                            <option value="">Todos los estados</option>
                            <option value="active">Activos</option>
                            <option value="inactive">Inactivos</option>
                        </flux:select>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-700 dark:text-zinc-300">Por página</label>
                        <flux:select
                            wire:model.live="perPage"
                            class="h-12 rounded-xl border-zinc-200 bg-white text-sm shadow-none dark:border-white/10 dark:bg-[#0d131a] dark:text-white"
                        >
                            <option value="10">10 por página</option>
                            <option value="25">25 por página</option>
                            <option value="50">50 por página</option>
                        </flux:select>
                    </div>
                </div>

                @if ($hasActiveFilters)
                    <div class="mt-4 flex justify-end">
                        <button
                            type="button"
                            wire:click="clearFilters"
                            class="inline-flex items-center gap-2 rounded-xl border border-zinc-200 bg-zinc-50 px-3 py-2 text-sm font-medium text-zinc-600 transition hover:border-zinc-300 hover:bg-zinc-100 dark:border-white/10 dark:bg-white/[0.02] dark:text-zinc-300 dark:hover:bg-white/[0.05]"
                        >
                            <flux:icon name="x-mark" class="size-4" />
                            <span>Limpiar filtros</span>
                        </button>
                    </div>
                @endif
            </div>

            @if ($services->isEmpty())
                <div class="rounded-[24px] border border-zinc-200 bg-white px-6 py-20 text-center shadow-sm dark:border-white/10 dark:bg-[#111820] dark:shadow-none">
                    <div class="mx-auto flex size-16 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-300">
                        <flux:icon.sparkles class="size-8" />
                    </div>

                    <div class="mt-4 space-y-1">
                        <flux:heading size="lg" class="text-slate-900 dark:text-white">
                            {{ $hasActiveFilters ? 'No se encontraron servicios' : 'No hay servicios aún' }}
                        </flux:heading>
                        <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                            {{ $hasActiveFilters
                                ? 'Prueba quitando los filtros o cambia la búsqueda para ver resultados.'
                                : 'Crea tu primer servicio para comenzar a registrar reservas, precios y asignaciones.' }}
                        </flux:text>
                    </div>

                    <div class="mt-5 flex flex-wrap items-center justify-center gap-3">
                        @if ($hasActiveFilters)
                            <flux:button
                                variant="outline"
                                icon="x-mark"
                                wire:click="clearFilters"
                                class="h-11 rounded-xl border-zinc-200 bg-white px-4 text-sm font-semibold text-slate-700 shadow-sm dark:border-white/10 dark:bg-white/[0.02] dark:text-white dark:shadow-none"
                            >
                                Limpiar filtros
                            </flux:button>
                        @endif

                        <flux:button
                            variant="primary"
                            icon="plus"
                            wire:click="openCreateModal"
                            class="h-11 rounded-xl bg-emerald-600 px-4 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700 dark:bg-emerald-600 dark:shadow-none"
                        >
                            Nuevo servicio
                        </flux:button>
                    </div>
                </div>
            @else
                <div class="overflow-hidden rounded-[24px] border border-zinc-200 bg-white shadow-sm dark:border-white/10 dark:bg-[#111820] dark:shadow-none">
                    <div class="space-y-3 px-3 py-3 md:hidden">
                        @foreach ($services as $service)
                            @php
                                $professionalNames = $service->professionalProfiles->isNotEmpty()
                                    ? $service->professionalProfiles->pluck('public_name')
                                    : $service->professionals->pluck('name');
                                $visibleNames = $professionalNames->take(2);
                                $remainingCount = max(0, $professionalNames->count() - $visibleNames->count());
                            @endphp

                            <article
                                x-data="{ expanded: false }"
                                class="overflow-hidden rounded-[24px] border border-zinc-200 bg-white shadow-sm dark:border-white/10 dark:bg-[#0f1720] dark:shadow-none"
                            >
                                <button
                                    type="button"
                                    class="flex w-full items-start gap-3 px-4 py-4 text-left"
                                    @click="expanded = !expanded"
                                >
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="min-w-0">
                                                <div class="truncate text-[1.05rem] font-semibold leading-tight text-slate-900 dark:text-white">
                                                    {{ $service->name }}
                                                </div>
                                                <div class="mt-1 truncate text-sm text-zinc-500 dark:text-zinc-400">
                                                    {{ $service->category->name }}
                                                </div>
                                            </div>

                                            <div class="shrink-0 text-right">
                                                <div class="text-lg font-semibold leading-none text-emerald-600 dark:text-emerald-400">
                                                    S/ {{ number_format((float) $service->price, 2) }}
                                                </div>
                                                <div class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                                                    {{ $service->duration_minutes }} min
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <span class="inline-flex size-10 shrink-0 items-center justify-center rounded-2xl border border-zinc-200 bg-white text-zinc-500 transition dark:border-white/10 dark:bg-white/[0.03] dark:text-zinc-300">
                                        <flux:icon name="chevron-down" class="size-4 transition-transform" :class="expanded ? 'rotate-180' : ''" />
                                    </span>
                                </button>

                                <div x-show="expanded" x-cloak x-transition.opacity.duration.200ms class="border-t border-zinc-100 px-4 py-4 dark:border-white/10">
                                    <div class="grid grid-cols-2 gap-3">
                                        <div class="rounded-[20px] bg-zinc-50 px-4 py-3 dark:bg-white/[0.03]">
                                            <div class="text-[11px] uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Profesionales</div>
                                            <div class="mt-1 font-medium text-slate-900 dark:text-white">
                                                {{ $professionalNames->isEmpty() ? 'Sin asignar' : $professionalNames->count().' asignados' }}
                                            </div>
                                        </div>

                                        <div class="rounded-[20px] bg-zinc-50 px-4 py-3 dark:bg-white/[0.03]">
                                            <div class="text-[11px] uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Online</div>
                                            <div class="mt-1 font-medium {{ $service->is_bookable_online ? 'text-sky-600 dark:text-sky-400' : 'text-zinc-500 dark:text-zinc-400' }}">
                                                {{ $service->is_bookable_online ? 'Activas' : 'Inactivas' }}
                                            </div>
                                        </div>

                                        <div class="rounded-[20px] bg-zinc-50 px-4 py-3 dark:bg-white/[0.03]">
                                            <div class="text-[11px] uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Estado</div>
                                            <div class="mt-1 font-medium {{ $service->is_active ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                                                {{ $service->is_active ? 'Activo' : 'Inactivo' }}
                                            </div>
                                        </div>

                                        <div class="rounded-[20px] bg-zinc-50 px-4 py-3 dark:bg-white/[0.03]">
                                            <div class="text-[11px] uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Duración</div>
                                            <div class="mt-1 font-medium text-slate-900 dark:text-white">{{ $service->duration_minutes }} min</div>
                                        </div>

                                        @if ($professionalNames->isNotEmpty())
                                            <div class="col-span-2 rounded-[20px] bg-zinc-50 px-4 py-3 dark:bg-white/[0.03]">
                                                <div class="text-[11px] uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Asignados</div>
                                                <div class="mt-2 flex flex-wrap gap-2">
                                                    @foreach ($visibleNames as $name)
                                                        <span class="inline-flex max-w-full items-center rounded-full border border-zinc-200 bg-white px-2.5 py-1 text-xs font-medium text-zinc-600 dark:border-white/10 dark:bg-white/[0.04] dark:text-zinc-200">
                                                            <span class="truncate">{{ $name }}</span>
                                                        </span>
                                                    @endforeach

                                                    @if ($remainingCount > 0)
                                                        <span class="inline-flex items-center rounded-full border border-sky-200 bg-sky-50 px-2.5 py-1 text-xs font-semibold text-sky-600 dark:border-sky-500/20 dark:bg-sky-500/10 dark:text-sky-300">
                                                            +{{ $remainingCount }} más
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                        @endif
                                    </div>

                                    <div class="mt-4 grid grid-cols-3 gap-2">
                                        <button
                                            type="button"
                                            class="inline-flex items-center justify-center rounded-2xl border border-zinc-200 bg-white px-3 py-2.5 text-zinc-600 transition hover:border-zinc-300 hover:bg-zinc-50 dark:border-white/10 dark:bg-white/[0.03] dark:text-zinc-200 dark:hover:bg-white/[0.06]"
                                            wire:click="openEditModal({{ $service->id }})"
                                            aria-label="Editar servicio"
                                        >
                                            <flux:icon name="pencil-square" class="size-4" />
                                        </button>

                                        <button
                                            type="button"
                                            class="inline-flex items-center justify-center rounded-2xl border border-zinc-200 bg-white px-3 py-2.5 text-zinc-600 transition hover:border-zinc-300 hover:bg-zinc-50 dark:border-white/10 dark:bg-white/[0.03] dark:text-zinc-200 dark:hover:bg-white/[0.06]"
                                            wire:click="toggleStatus({{ $service->id }})"
                                            aria-label="{{ $service->is_active ? 'Desactivar servicio' : 'Activar servicio' }}"
                                        >
                                            <flux:icon name="{{ $service->is_active ? 'pause-circle' : 'play-circle' }}" class="size-4" />
                                        </button>

                                        <button
                                            type="button"
                                            class="inline-flex items-center justify-center rounded-2xl bg-rose-500 px-3 py-2.5 text-white transition hover:bg-rose-600 dark:bg-rose-500 dark:hover:bg-rose-600"
                                            wire:click="confirmDelete({{ $service->id }})"
                                            aria-label="Eliminar servicio"
                                        >
                                            <flux:icon name="trash" class="size-4" />
                                        </button>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>

                    <div class="hidden md:block">
                        <div class="overflow-x-auto">
                            <table class="min-w-full border-separate border-spacing-0">
                                <thead>
                                    <tr>
                                        <th class="border-b border-zinc-200 px-5 py-4 text-left text-xs font-semibold uppercase tracking-[0.08em] text-slate-700 dark:border-white/10 dark:text-zinc-300">Nombre</th>
                                        <th class="border-b border-zinc-200 px-5 py-4 text-left text-xs font-semibold uppercase tracking-[0.08em] text-slate-700 dark:border-white/10 dark:text-zinc-300">Categoría</th>
                                        <th class="border-b border-zinc-200 px-5 py-4 text-left text-xs font-semibold uppercase tracking-[0.08em] text-slate-700 dark:border-white/10 dark:text-zinc-300">Precio</th>
                                        <th class="border-b border-zinc-200 px-5 py-4 text-left text-xs font-semibold uppercase tracking-[0.08em] text-slate-700 dark:border-white/10 dark:text-zinc-300">Duración</th>
                                        <th class="border-b border-zinc-200 px-5 py-4 text-left text-xs font-semibold uppercase tracking-[0.08em] text-slate-700 dark:border-white/10 dark:text-zinc-300">Profesionales</th>
                                        <th class="border-b border-zinc-200 px-5 py-4 text-left text-xs font-semibold uppercase tracking-[0.08em] text-slate-700 dark:border-white/10 dark:text-zinc-300">Online</th>
                                        <th class="border-b border-zinc-200 px-5 py-4 text-left text-xs font-semibold uppercase tracking-[0.08em] text-slate-700 dark:border-white/10 dark:text-zinc-300">Estado</th>
                                        <th class="border-b border-zinc-200 px-5 py-4 text-right text-xs font-semibold uppercase tracking-[0.08em] text-slate-700 dark:border-white/10 dark:text-zinc-300">Opciones</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    @foreach ($services as $service)
                                        @php
                                            $professionalNames = $service->professionalProfiles->isNotEmpty()
                                                ? $service->professionalProfiles->pluck('public_name')
                                                : $service->professionals->pluck('name');
                                            $visibleNames = $professionalNames->take(2);
                                            $remainingCount = max(0, $professionalNames->count() - $visibleNames->count());
                                        @endphp

                                        <tr wire:key="service-row-{{ $service->id }}">
                                            <td class="border-b border-zinc-100 px-5 py-5 align-top dark:border-white/5">
                                                <div class="font-semibold text-slate-900 dark:text-white">{{ $service->name }}</div>
                                            </td>

                                            <td class="border-b border-zinc-100 px-5 py-5 align-top text-sm text-slate-600 dark:border-white/5 dark:text-zinc-300">
                                                {{ $service->category->name }}
                                            </td>

                                            <td class="border-b border-zinc-100 px-5 py-5 align-top text-sm text-slate-600 dark:border-white/5 dark:text-zinc-300">
                                                S/ {{ number_format((float) $service->price, 2) }}
                                            </td>

                                            <td class="border-b border-zinc-100 px-5 py-5 align-top text-sm text-slate-600 dark:border-white/5 dark:text-zinc-300">
                                                {{ $service->duration_minutes }} min
                                            </td>

                                            <td class="border-b border-zinc-100 px-5 py-5 align-top dark:border-white/5">
                                                @if ($professionalNames->isEmpty())
                                                    <span class="text-sm text-zinc-400 dark:text-zinc-500">Sin profesionales</span>
                                                @else
                                                    <div class="flex flex-wrap gap-2">
                                                        @foreach ($visibleNames as $name)
                                                            <span class="inline-flex max-w-full items-center rounded-full border border-zinc-200 bg-zinc-50 px-2.5 py-1 text-xs font-medium text-zinc-600 dark:border-white/10 dark:bg-white/[0.04] dark:text-zinc-200">
                                                                <span class="truncate">{{ $name }}</span>
                                                            </span>
                                                        @endforeach

                                                        @if ($remainingCount > 0)
                                                            <span class="inline-flex items-center rounded-full border border-sky-200 bg-sky-50 px-2.5 py-1 text-xs font-semibold text-sky-600 dark:border-sky-500/20 dark:bg-sky-500/10 dark:text-sky-300">
                                                                +{{ $remainingCount }} más
                                                            </span>
                                                        @endif
                                                    </div>
                                                @endif
                                            </td>

                                            <td class="border-b border-zinc-100 px-5 py-5 align-top dark:border-white/5">
                                                <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $service->is_bookable_online ? 'bg-sky-100 text-sky-700 dark:bg-sky-500/10 dark:text-sky-300' : 'bg-zinc-100 text-zinc-600 dark:bg-white/[0.05] dark:text-zinc-400' }}">
                                                    {{ $service->is_bookable_online ? 'Activas' : 'Inactivas' }}
                                                </span>
                                            </td>

                                            <td class="border-b border-zinc-100 px-5 py-5 align-top dark:border-white/5">
                                                <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $service->is_active ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300' : 'bg-zinc-100 text-zinc-600 dark:bg-white/[0.05] dark:text-zinc-400' }}">
                                                    {{ $service->is_active ? 'Activo' : 'Inactivo' }}
                                                </span>
                                            </td>

                                            <td class="border-b border-zinc-100 px-5 py-5 align-top dark:border-white/5">
                                                <div class="flex items-center justify-end gap-2">
                                                    <button
                                                        type="button"
                                                        class="inline-flex size-9 items-center justify-center rounded-xl border border-zinc-200 bg-white text-zinc-600 transition hover:border-zinc-300 hover:bg-zinc-50 dark:border-white/10 dark:bg-white/[0.03] dark:text-zinc-200 dark:hover:bg-white/[0.06]"
                                                        wire:click="openEditModal({{ $service->id }})"
                                                        aria-label="Editar servicio"
                                                    >
                                                        <flux:icon name="pencil-square" class="size-4" />
                                                    </button>

                                                    <button
                                                        type="button"
                                                        class="inline-flex size-9 items-center justify-center rounded-xl border border-zinc-200 bg-white text-zinc-600 transition hover:border-zinc-300 hover:bg-zinc-50 dark:border-white/10 dark:bg-white/[0.03] dark:text-zinc-200 dark:hover:bg-white/[0.06]"
                                                        wire:click="toggleStatus({{ $service->id }})"
                                                        aria-label="{{ $service->is_active ? 'Desactivar servicio' : 'Activar servicio' }}"
                                                    >
                                                        <flux:icon name="{{ $service->is_active ? 'pause-circle' : 'play-circle' }}" class="size-4" />
                                                    </button>

                                                    <button
                                                        type="button"
                                                        class="inline-flex size-9 items-center justify-center rounded-xl bg-rose-500 text-white transition hover:bg-rose-600 dark:bg-rose-500 dark:hover:bg-rose-600"
                                                        wire:click="confirmDelete({{ $service->id }})"
                                                        aria-label="Eliminar servicio"
                                                    >
                                                        <flux:icon name="trash" class="size-4" />
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="flex flex-col gap-4 border-t border-zinc-200 px-4 py-4 md:flex-row md:items-center md:justify-between dark:border-white/10">
                        <div class="text-sm text-slate-600 dark:text-zinc-400">
                            Mostrando {{ $services->firstItem() }} a {{ $services->lastItem() }} de {{ $services->total() }} servicios
                        </div>

                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                            <div class="min-w-[10rem]">
                                <flux:select
                                    wire:model.live="perPage"
                                    class="h-11 rounded-xl border-zinc-200 bg-white text-sm shadow-none dark:border-white/10 dark:bg-[#0d131a] dark:text-white"
                                >
                                    <option value="10">10 por página</option>
                                    <option value="25">25 por página</option>
                                    <option value="50">50 por página</option>
                                </flux:select>
                            </div>

                            <div class="services-pagination">
                                {{ $services->links('vendor.pagination.livewire-table-clean') }}
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    @if ($showUpsertModal)
        <div
            class="fixed inset-0 z-[60] flex items-start justify-center bg-zinc-950/50 px-3 py-4 backdrop-blur-[2px] sm:items-center sm:px-4 sm:py-6"
            wire:click.self="closeModal"
        >
            <div class="relative flex h-full w-full max-w-6xl max-h-[100vh] flex-col overflow-hidden rounded-none bg-white shadow-[0_30px_100px_rgba(0,0,0,0.25)] ring-1 ring-violet-200 sm:max-h-[92vh] sm:rounded-[30px]">
                <div class="flex items-start justify-between gap-4 border-b border-violet-100 px-4 py-4 sm:px-6 sm:py-5">
                    <div>
                        <flux:heading size="lg">{{ $isEditing ? 'Editar servicio' : 'Nuevo servicio' }}</flux:heading>
                        <flux:text class="mt-1 text-sm text-zinc-500">
                            Configura los datos principales del servicio y asigna profesionales.
                        </flux:text>
                    </div>

                    <button
                        type="button"
                        class="rounded-full p-2 text-zinc-500 transition hover:bg-zinc-100 hover:text-zinc-900"
                        wire:click="closeModal"
                        aria-label="Cerrar modal"
                    >
                        <flux:icon name="x-mark" class="size-6" />
                    </button>
                </div>

                <form wire:submit="save" class="flex min-h-0 flex-1 flex-col">
                    <div class="min-h-0 flex-1 overflow-y-auto px-4 py-4 sm:px-6 sm:py-5">
                        <div class="space-y-4">
                            <div class="rounded-[24px] border border-zinc-200/80 p-5">
                                <div class="mb-5">
                                    <flux:heading size="base">Datos básicos</flux:heading>
                                </div>

                                <div class="grid gap-4 lg:grid-cols-2">
                                    <div class="space-y-1.5">
                                        <flux:input wire:model="form.name" label="Nombre *" type="text" required class="rounded-2xl" />
                                        @error('form.name')
                                            <p class="text-sm text-rose-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div class="space-y-1.5">
                                        <flux:select wire:model.live="form.service_category_id" label="Categoría *" class="rounded-2xl">
                                            <option value="">Seleccionar categoría</option>
                                            @foreach ($this->categories as $category)
                                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                                            @endforeach
                                        </flux:select>
                                        @error('form.service_category_id')
                                            <p class="text-sm text-rose-600">{{ $message }}</p>
                                        @enderror
                                        <button
                                            type="button"
                                            class="text-sm font-medium text-zinc-600 underline decoration-zinc-400 underline-offset-4 hover:text-violet-700"
                                            wire:click="openCategoryModal"
                                        >
                                            + Nueva categoría
                                        </button>
                                    </div>

                                    <div class="space-y-1.5">
                                        <flux:input wire:model="form.price" label="Precio *" type="number" step="0.01" min="0" required class="rounded-2xl" />
                                        @error('form.price')
                                            <p class="text-sm text-rose-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div class="space-y-1.5">
                                        <flux:input wire:model="form.duration_minutes" label="Duración en minutos *" type="number" min="1" required class="rounded-2xl" />
                                        @error('form.duration_minutes')
                                            <p class="text-sm text-rose-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div class="rounded-2xl border border-violet-100 bg-violet-50/70 px-4 py-4">
                                        <flux:switch
                                            wire:model.live="form.is_active"
                                            label="Servicio activo"
                                            description="Desactívalo temporalmente si no debe mostrarse."
                                            align="left"
                                        />
                                    </div>

                                    <div class="rounded-2xl border border-violet-100 bg-violet-50/70 px-4 py-4">
                                        <flux:switch
                                            wire:model.live="form.is_bookable_online"
                                            label="Reservable online"
                                            description="Habilita o deshabilita la reserva por la web."
                                            align="left"
                                        />
                                    </div>
                                </div>
                            </div>

                            <div class="rounded-[24px] border border-zinc-200/80 p-5">
                                <div class="mb-5 flex flex-wrap items-start justify-between gap-4">
                                    <div>
                                        <flux:heading size="base">Profesionales asignados</flux:heading>
                                        <flux:text class="mt-1 text-sm text-zinc-500">
                                            Asigna uno o varios profesionales para que puedan ofrecer este servicio.
                                        </flux:text>
                                    </div>

                                    <button
                                        type="button"
                                        wire:click="$toggle('showProfessionalPicker')"
                                        class="inline-flex items-center gap-2 rounded-xl border border-zinc-200 bg-white px-3 py-2 text-sm font-medium text-zinc-600 shadow-sm transition hover:bg-zinc-50"
                                        aria-label="Mostrar u ocultar selección de profesionales"
                                    >
                                        <span class="text-zinc-400">({{ count($form->professional_ids) }})</span>
                                        <flux:icon.chevron-up class="size-4 transition-transform duration-200 {{ $showProfessionalPicker ? '' : 'rotate-180' }}" />
                                    </button>
                                </div>

                                @if ($showProfessionalPicker)
                                    <div class="rounded-2xl border border-zinc-200 bg-zinc-50 p-4">
                                        <div class="space-y-4">
                                            <label class="flex items-center gap-3 text-sm font-semibold text-zinc-700">
                                                <input
                                                    type="checkbox"
                                                    class="size-4 rounded border-zinc-300 text-cyan-600 focus:ring-cyan-500"
                                                    wire:click="selectAllProfessionals"
                                                    @checked($this->professionalsCatalog->isNotEmpty() && count($form->professional_ids) === $this->professionalsCatalog->count())
                                                >
                                                <span>Seleccionar todo</span>
                                            </label>

                                            @if ($form->professional_ids !== [])
                                                <div class="flex flex-wrap gap-2">
                                                    @foreach ($this->professionalsCatalog->whereIn('id', $form->professional_ids) as $selectedProfessional)
                                                        <span class="inline-flex items-center rounded-full bg-cyan-50 px-3 py-1 text-xs font-semibold text-cyan-700 ring-1 ring-cyan-200">
                                                            {{ $selectedProfessional->public_name }}
                                                        </span>
                                                    @endforeach
                                                </div>
                                            @endif

                                            @if ($this->professionalsCatalog->isEmpty())
                                                <div class="rounded-xl border border-dashed border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-500">
                                                    No hay profesionales activos disponibles.
                                                </div>
                                            @else
                                                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                                    @foreach ($this->professionalsCatalog as $professional)
                                                        <label wire:key="professional-option-{{ $professional->id }}" class="flex items-center gap-3 rounded-xl border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-700 shadow-sm transition hover:border-cyan-300">
                                                            <input
                                                                type="checkbox"
                                                                class="size-4 rounded border-zinc-300 text-cyan-600 focus:ring-cyan-500"
                                                                value="{{ $professional->id }}"
                                                                wire:model.live="form.professional_ids"
                                                            >
                                                            <span class="truncate">{{ $professional->public_name }}</span>
                                                        </label>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col-reverse gap-3 border-t border-zinc-200 bg-zinc-50 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                        <button
                            type="button"
                            class="inline-flex h-10 items-center justify-center rounded-xl bg-zinc-100 px-4 text-sm font-medium text-zinc-700 shadow-sm transition hover:bg-zinc-200"
                            wire:click="closeModal"
                        >
                            Cancelar
                        </button>

                        <button
                            type="submit"
                            class="inline-flex h-10 items-center justify-center rounded-xl bg-violet-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-violet-700"
                        >
                            {{ $isEditing ? 'Guardar cambios' : 'Guardar' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @if ($showCategoryModal)
        <div
            class="fixed inset-0 z-[60] flex items-start justify-center bg-zinc-950/50 px-3 py-4 backdrop-blur-[2px] sm:items-center sm:px-4 sm:py-6"
            wire:click.self="closeCategoryModal"
        >
            <div class="relative w-full max-w-lg overflow-hidden rounded-[24px] bg-white shadow-[0_30px_100px_rgba(0,0,0,0.25)] ring-1 ring-violet-200">
                <div class="flex items-start justify-between gap-4 border-b border-violet-100 px-4 py-4 sm:px-6">
                    <div>
                        <flux:heading size="lg">Nueva categoría</flux:heading>
                        <flux:text class="mt-1 text-sm text-zinc-500">
                            Crea una categoría nueva y quedará seleccionada automáticamente.
                        </flux:text>
                    </div>

                    <button
                        type="button"
                        class="rounded-full p-2 text-zinc-500 transition hover:bg-zinc-100 hover:text-zinc-900"
                        wire:click="closeCategoryModal"
                        aria-label="Cerrar modal"
                    >
                        <flux:icon name="x-mark" class="size-6" />
                    </button>
                </div>

                <div class="space-y-5 px-4 py-4 sm:px-6">
                    <div class="space-y-1.5">
                        <flux:input wire:model="categoryName" label="Nombre de la categoría" type="text" required class="rounded-2xl" />
                        @error('categoryName')
                            <p class="text-sm text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex flex-col-reverse gap-3 border-t border-zinc-200 pt-4 sm:flex-row sm:items-center sm:justify-between">
                        <button
                            type="button"
                            class="inline-flex h-10 items-center justify-center rounded-xl bg-zinc-100 px-4 text-sm font-medium text-zinc-700 shadow-sm transition hover:bg-zinc-200"
                            wire:click="closeCategoryModal"
                        >
                            Cancelar
                        </button>

                        <button
                            type="button"
                            class="inline-flex h-10 items-center justify-center rounded-xl bg-violet-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-violet-700"
                            wire:click="saveCategory"
                        >
                            Guardar categoría
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if ($showDeleteModal)
        <div
            class="fixed inset-0 z-[60] flex items-start justify-center bg-zinc-950/50 px-3 py-4 backdrop-blur-[2px] sm:items-center sm:px-4 sm:py-6"
            wire:click.self="closeDeleteModal"
        >
            <div class="relative w-full max-w-lg overflow-hidden rounded-[24px] bg-white shadow-[0_30px_100px_rgba(0,0,0,0.25)] ring-1 ring-violet-200">
                <div class="flex items-start justify-between gap-4 border-b border-violet-100 px-4 py-4 sm:px-6">
                    <div>
                        <flux:heading size="lg">Eliminar servicio</flux:heading>
                        <flux:text class="mt-1 text-sm text-zinc-500">
                            @if ($servicePendingDeletion)
                                Esta acción eliminará {{ $servicePendingDeletion->name }} si no tiene reservas asociadas. Si las tiene, se desactivará.
                            @else
                                Esta acción eliminará el servicio seleccionado.
                            @endif
                        </flux:text>
                    </div>

                    <button
                        type="button"
                        class="rounded-full p-2 text-zinc-500 transition hover:bg-zinc-100 hover:text-zinc-900"
                        wire:click="closeDeleteModal"
                        aria-label="Cerrar modal"
                    >
                        <flux:icon name="x-mark" class="size-6" />
                    </button>
                </div>

                <div class="space-y-5 px-4 py-4 sm:px-6">
                    <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                        Esta acción es permanente y no se puede deshacer si no tiene reservas asociadas.
                    </div>

                    <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                        <button
                            type="button"
                            class="inline-flex h-10 items-center justify-center rounded-xl bg-zinc-100 px-4 text-sm font-medium text-zinc-700 shadow-sm transition hover:bg-zinc-200"
                            wire:click="closeDeleteModal"
                        >
                            Cancelar
                        </button>

                        <button
                            type="button"
                            class="inline-flex h-10 items-center justify-center rounded-xl bg-rose-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-rose-700"
                            wire:click="delete"
                        >
                            Eliminar servicio
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</section>
