<section class="w-full px-4 py-4 sm:px-6 lg:px-8">
    @php
        $hasActiveFilters = $search !== '' || $categoryFilter !== '' || $statusFilter !== '';
    @endphp

    <div class="flex w-full flex-col gap-5">
        <div class="grid gap-3 lg:grid-cols-[auto_minmax(0,1fr)] lg:items-start">
            <div class="min-w-0 pt-0 lg:pt-2">
                <flux:heading size="xl" level="1" class="mt-0 leading-none">Servicios</flux:heading>
            </div>

            <div class="flex w-full flex-col gap-2 lg:w-auto lg:flex-row lg:items-end lg:justify-end">
                <div class="flex w-full flex-wrap items-end gap-2 lg:w-auto lg:justify-end">
                    <flux:button variant="outline" icon="plus" wire:click="openCategoryModal">
                        Nueva categoría
                    </flux:button>

                    <flux:button variant="primary" icon="plus" wire:click="openCreateModal">
                        Nuevo servicio
                    </flux:button>
                </div>
            </div>
        </div>

        <flux:card class="overflow-hidden border border-zinc-200/80 bg-white shadow-sm">
            <div class="grid gap-2 border-b border-zinc-200/80 px-4 py-4 sm:grid-cols-2 lg:grid-cols-[minmax(18rem,22rem)_minmax(12rem,14rem)_minmax(12rem,14rem)_minmax(8rem,10rem)_auto] lg:items-end lg:px-5">
                <flux:input
                    wire:model.live.debounce.300ms="search"
                    icon="magnifying-glass"
                    clearable
                    placeholder="Buscar por nombre o categoría"
                    class="w-full rounded-2xl border-zinc-200 bg-zinc-50 shadow-sm"
                />

                <div class="space-y-1">
                    <label class="text-sm font-medium text-zinc-700">Categoría</label>
                    <flux:select wire:model.live="categoryFilter" class="w-full rounded-2xl border-zinc-200 bg-zinc-50 shadow-sm">
                        <option value="">Todas las categorías</option>
                        @foreach ($this->categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </flux:select>
                </div>

                <div class="space-y-1">
                    <label class="text-sm font-medium text-zinc-700">Estado</label>
                    <flux:select wire:model.live="statusFilter" class="w-full rounded-2xl border-zinc-200 bg-zinc-50 shadow-sm">
                        <option value="">Todos los estados</option>
                        <option value="active">Activos</option>
                        <option value="inactive">Inactivos</option>
                    </flux:select>
                </div>

                <div class="space-y-1">
                    <label class="text-sm font-medium text-zinc-700">Por página</label>
                    <flux:select wire:model.live="perPage" class="w-full rounded-2xl border-zinc-200 bg-zinc-50 shadow-sm">
                        <option value="10">10 por página</option>
                        <option value="25">25 por página</option>
                        <option value="50">50 por página</option>
                    </flux:select>
                </div>

                <flux:button variant="ghost" icon="x-mark" wire:click="clearFilters" class="justify-self-start lg:justify-self-end">
                    Limpiar
                </flux:button>
            </div>

            @if ($services->isEmpty())
                <div class="flex flex-col items-center justify-center gap-3 px-6 py-20 text-center">
                    <div class="flex size-16 items-center justify-center rounded-2xl bg-violet-50 text-violet-600">
                        <flux:icon.sparkles class="size-8" />
                    </div>

                    <div class="space-y-1">
                        <flux:heading size="lg">{{ $hasActiveFilters ? 'No se encontraron servicios' : 'No hay servicios aún' }}</flux:heading>
                        <flux:text class="text-sm text-zinc-500">
                            {{ $hasActiveFilters
                                ? 'Prueba quitando los filtros o cambia la búsqueda para ver resultados.'
                                : 'Crea tu primer servicio para comenzar a registrar reservas, precios y asignaciones.' }}
                        </flux:text>
                    </div>

                    <div class="flex flex-wrap items-center justify-center gap-2">
                        @if ($hasActiveFilters)
                            <flux:button variant="ghost" icon="x-mark" wire:click="clearFilters">
                                Limpiar filtros
                            </flux:button>
                        @endif

                        <flux:button variant="primary" icon="plus" wire:click="openCreateModal">
                            Nuevo servicio
                        </flux:button>
                    </div>
                </div>
            @else
                <div class="space-y-2 px-0 pb-4 pt-0 md:hidden">
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
                            class="rounded-none border-x-0 border-y border-zinc-200/80 bg-white shadow-sm first:border-t-0"
                        >
                            <button
                                type="button"
                                class="flex w-full items-start gap-3 px-3 py-3 text-left sm:px-4"
                                @click="expanded = !expanded"
                            >
                                <div class="min-w-0 flex-1 space-y-1">
                                    <div class="truncate text-lg font-semibold leading-tight text-zinc-900">
                                        {{ $service->name }}
                                    </div>

                                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-zinc-500">
                                        <span class="truncate">
                                            {{ $service->category->name }}
                                        </span>

                                        <span class="hidden h-1 w-1 rounded-full bg-zinc-300 sm:inline-block"></span>

                                        <span class="truncate">
                                            S/ {{ number_format((float) $service->price, 2) }}
                                        </span>

                                        <span class="hidden h-1 w-1 rounded-full bg-zinc-300 sm:inline-block"></span>

                                        <span class="truncate">
                                            {{ $service->duration_minutes }} min
                                        </span>
                                    </div>
                                </div>

                                <span
                                    class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl border border-zinc-200 bg-white text-zinc-500 transition"
                                    :class="expanded ? 'border-violet-300 text-violet-700' : ''"
                                >
                                    <flux:icon name="chevron-down" class="size-4 transition-transform" :class="expanded ? 'rotate-180' : ''" />
                                </span>
                            </button>

                            <div x-show="expanded" x-cloak x-transition class="border-t border-zinc-100 px-3 py-4 sm:px-4">
                                <div class="grid grid-cols-2 gap-3 text-sm">
                                    <div class="rounded-2xl bg-zinc-50 px-3 py-2">
                                        <div class="text-[11px] uppercase tracking-wide text-zinc-500">Profesionales</div>
                                        <div class="mt-1 font-medium text-zinc-900">
                                            {{ $professionalNames->isEmpty() ? 'Sin profesionales' : $professionalNames->count().' asignados' }}
                                        </div>
                                    </div>

                                    <div class="rounded-2xl bg-zinc-50 px-3 py-2">
                                        <div class="text-[11px] uppercase tracking-wide text-zinc-500">Reservas online</div>
                                        <div class="mt-1 font-medium {{ $service->is_bookable_online ? 'text-emerald-600' : 'text-amber-600' }}">
                                            {{ $service->is_bookable_online ? 'Activas' : 'Inactivas' }}
                                        </div>
                                    </div>

                                    <div class="rounded-2xl bg-zinc-50 px-3 py-2">
                                        <div class="text-[11px] uppercase tracking-wide text-zinc-500">Estado</div>
                                        <div class="mt-1 font-medium {{ $service->is_active ? 'text-emerald-600' : 'text-rose-600' }}">
                                            {{ $service->is_active ? 'Activo' : 'Inactivo' }}
                                        </div>
                                    </div>

                                    <div class="rounded-2xl bg-zinc-50 px-3 py-2">
                                        <div class="text-[11px] uppercase tracking-wide text-zinc-500">Duración</div>
                                        <div class="mt-1 font-medium text-zinc-900">{{ $service->duration_minutes }} min</div>
                                    </div>

                                    @if ($professionalNames->isNotEmpty())
                                        <div class="col-span-2 rounded-2xl bg-zinc-50 px-3 py-2">
                                            <div class="text-[11px] uppercase tracking-wide text-zinc-500">Asignados</div>
                                            <div class="mt-2 flex flex-wrap gap-2">
                                                @foreach ($visibleNames as $name)
                                                    <span class="inline-flex max-w-full items-center rounded-full bg-zinc-100 px-2.5 py-1 text-xs font-medium text-zinc-600 ring-1 ring-zinc-200">
                                                        <span class="truncate">{{ $name }}</span>
                                                    </span>
                                                @endforeach

                                                @if ($remainingCount > 0)
                                                    <span class="inline-flex items-center rounded-full bg-sky-50 px-2.5 py-1 text-xs font-semibold text-sky-600 ring-1 ring-sky-200">
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
                                        class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-violet-200 bg-violet-50 px-3 py-2 text-sm font-medium text-violet-700 transition hover:border-violet-300 hover:bg-violet-100"
                                        wire:click="openEditModal({{ $service->id }})"
                                    >
                                        <flux:icon name="pencil-square" class="size-4" />
                                        <span>Editar</span>
                                    </button>

                                    <button
                                        type="button"
                                        class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-zinc-200 bg-white px-3 py-2 text-sm font-medium text-zinc-700 transition hover:border-zinc-300 hover:bg-zinc-50"
                                        wire:click="toggleStatus({{ $service->id }})"
                                    >
                                        <flux:icon name="{{ $service->is_active ? 'pause-circle' : 'play-circle' }}" class="size-4" />
                                        <span>{{ $service->is_active ? 'Desactivar' : 'Activar' }}</span>
                                    </button>

                                    <button
                                        type="button"
                                        class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm font-medium text-rose-700 transition hover:border-rose-300 hover:bg-rose-100"
                                        wire:click="confirmDelete({{ $service->id }})"
                                    >
                                        <flux:icon name="trash" class="size-4" />
                                        <span>Eliminar</span>
                                    </button>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>

                <div class="hidden md:block">
                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column class="w-[22%]">
                                Nombre
                            </flux:table.column>

                            <flux:table.column class="w-[16%]">
                                Categoría
                            </flux:table.column>

                            <flux:table.column class="w-[12%]">
                                Precio
                            </flux:table.column>

                            <flux:table.column class="w-[12%]">
                                Duración
                            </flux:table.column>

                            <flux:table.column class="w-[22%]">
                                Profesionales
                            </flux:table.column>

                            <flux:table.column class="w-[10%]">
                                Online
                            </flux:table.column>

                            <flux:table.column class="w-[8%]">
                                Estado
                            </flux:table.column>

                            <flux:table.column class="w-[8rem] min-w-[8rem] text-center">
                                Opciones
                            </flux:table.column>
                        </flux:table.columns>

                        <flux:table.rows>
                            @foreach ($services as $service)
                                @php
                                    $professionalNames = $service->professionalProfiles->isNotEmpty()
                                        ? $service->professionalProfiles->pluck('public_name')
                                        : $service->professionals->pluck('name');
                                    $visibleNames = $professionalNames->take(2);
                                    $remainingCount = max(0, $professionalNames->count() - $visibleNames->count());
                                @endphp

                                <flux:table.row :key="$service->id">
                                    <flux:table.cell class="font-medium text-zinc-900">
                                        {{ $service->name }}
                                    </flux:table.cell>

                                    <flux:table.cell>
                                        {{ $service->category->name }}
                                    </flux:table.cell>

                                    <flux:table.cell>
                                        S/ {{ number_format((float) $service->price, 2) }}
                                    </flux:table.cell>

                                    <flux:table.cell>
                                        {{ $service->duration_minutes }} min
                                    </flux:table.cell>

                                    <flux:table.cell>
                                        @if ($professionalNames->isEmpty())
                                            <span class="text-zinc-400">Sin profesionales</span>
                                        @else
                                            <div class="flex flex-wrap gap-2">
                                                @foreach ($visibleNames as $name)
                                                    <span class="inline-flex max-w-full items-center rounded-full bg-zinc-100 px-2.5 py-1 text-xs font-medium text-zinc-600 ring-1 ring-zinc-200">
                                                        <span class="truncate">{{ $name }}</span>
                                                    </span>
                                                @endforeach

                                                @if ($remainingCount > 0)
                                                    <span class="inline-flex items-center rounded-full bg-sky-50 px-2.5 py-1 text-xs font-semibold text-sky-600 ring-1 ring-sky-200">
                                                        +{{ $remainingCount }} más
                                                    </span>
                                                @endif
                                            </div>
                                        @endif
                                    </flux:table.cell>

                                    <flux:table.cell>
                                        <span class="inline-flex items-center rounded-lg px-2.5 py-1 text-xs font-medium {{ $service->is_bookable_online ? 'bg-sky-100 text-sky-700' : 'bg-amber-100 text-amber-700' }}">
                                            {{ $service->is_bookable_online ? 'Activas' : 'Inactivas' }}
                                        </span>
                                    </flux:table.cell>

                                    <flux:table.cell>
                                        <span class="inline-flex items-center rounded-lg px-2.5 py-1 text-xs font-medium {{ $service->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-zinc-100 text-zinc-600' }}">
                                            {{ $service->is_active ? 'Activo' : 'Inactivo' }}
                                        </span>
                                    </flux:table.cell>

                                    <flux:table.cell>
                                        <div class="flex items-center justify-center gap-2">
                                            <flux:button
                                                size="sm"
                                                variant="ghost"
                                                icon="pencil-square"
                                                aria-label="Editar servicio"
                                                wire:click="openEditModal({{ $service->id }})"
                                            ></flux:button>

                                            <flux:button
                                                size="sm"
                                                variant="ghost"
                                                :icon="$service->is_active ? 'pause-circle' : 'play-circle'"
                                                aria-label="{{ $service->is_active ? 'Desactivar servicio' : 'Activar servicio' }}"
                                                wire:click="toggleStatus({{ $service->id }})"
                                            ></flux:button>

                                            <flux:button
                                                size="sm"
                                                variant="danger"
                                                icon="trash"
                                                aria-label="Eliminar servicio"
                                                wire:click="confirmDelete({{ $service->id }})"
                                            ></flux:button>
                                        </div>
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                </div>

                <div class="border-t border-zinc-200/80 px-2 py-3 md:px-4">
                    {{ $services->links() }}
                </div>
            @endif
        </flux:card>
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
