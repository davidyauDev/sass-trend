<section class="w-full px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex w-full flex-col gap-6">
        <div class="grid gap-4 xl:grid-cols-[minmax(0,1.6fr)_minmax(280px,1fr)] xl:items-end">
            <div class="min-w-0">
                <flux:heading size="xl" level="1" class="mt-3">Servicios</flux:heading>

            </div>

            <div class="flex flex-wrap items-center justify-start gap-2 xl:justify-end">
                <flux:dropdown position="bottom" align="end">
                    <flux:button variant="ghost" icon="ellipsis-horizontal">
                        Acciones
                    </flux:button>

                    <flux:menu>
                        <flux:menu.item icon="arrow-path" wire:click="clearFilters">
                            Limpiar filtros
                        </flux:menu.item>
                    </flux:menu>
                </flux:dropdown>

                <flux:button variant="primary" icon="plus" wire:click="openCreateModal">
                    Nuevo servicio
                </flux:button>
            </div>
        </div>

        <flux:card class="overflow-hidden border border-zinc-200/80 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="grid gap-4 border-b border-zinc-200/80 px-5 py-4 dark:border-zinc-700 xl:grid-cols-[minmax(0,1fr)_240px_220px_140px] xl:items-center">
                <div class="w-full min-w-0">
                    <flux:input
                        wire:model.live.debounce.300ms="search"
                        icon="magnifying-glass"
                        clearable
                        placeholder="Buscar por nombre o categoría"
                    />
                </div>

                <flux:select wire:model.live="categoryFilter">
                    <option value="">Todas las categorías</option>
                    @foreach ($this->categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </flux:select>

                <flux:select wire:model.live="statusFilter">
                    <option value="">Todos los estados</option>
                    <option value="active">Activos</option>
                    <option value="inactive">Inactivos</option>
                </flux:select>

                <flux:select wire:model.live="perPage">
                    <option value="10">10 por página</option>
                    <option value="25">25 por página</option>
                    <option value="50">50 por página</option>
                </flux:select>
            </div>

            <div class="flex items-center justify-between border-b border-zinc-200/80 px-5 py-3 text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                <span>Mostrando {{ $services->count() }} de {{ $services->total() }} servicios</span>

                @if ($search !== '' || $categoryFilter !== '' || $statusFilter !== '')
                    <span class="hidden sm:inline">Filtros activos</span>
                @endif
            </div>

            @if ($services->isEmpty())
                <div class="flex flex-col items-center justify-center gap-3 px-6 py-16 text-center">
                    <div class="flex size-14 items-center justify-center rounded-2xl bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-300">
                        <flux:icon.sparkles class="size-7" />
                    </div>

                    <div class="space-y-1">
                        <flux:heading size="lg">No hay servicios para mostrar</flux:heading>
                        <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                            Crea tu primer servicio o ajusta los filtros para intentar de nuevo.
                        </flux:text>
                    </div>

                    <div class="flex flex-wrap items-center justify-center gap-2">
                        <flux:button variant="primary" icon="plus" wire:click="openCreateModal">
                            Nuevo servicio
                        </flux:button>

                        <flux:button variant="ghost" icon="arrow-path" wire:click="clearFilters">
                            Limpiar filtros
                        </flux:button>
                    </div>
                </div>
            @else
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column class="w-[18%]">Nombre</flux:table.column>
                        <flux:table.column class="w-[14%]">Categoría</flux:table.column>
                        <flux:table.column class="w-[10%]">Precio</flux:table.column>
                        <flux:table.column class="w-[10%]">Duración</flux:table.column>
                        <flux:table.column class="w-[22%]">Profesionales asignados</flux:table.column>
                        <flux:table.column class="w-[10%]">Reservas online</flux:table.column>
                        <flux:table.column class="w-[8%]">Estado</flux:table.column>
                        <flux:table.column class="w-[8%] min-w-52 text-right">Opciones</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach ($services as $service)
                            <flux:table.row :key="$service->id">
                                <flux:table.cell class="font-medium text-zinc-900 dark:text-zinc-100">
                                    {{ $service->name }}
                                </flux:table.cell>
                                <flux:table.cell>{{ $service->category->name }}</flux:table.cell>
                                <flux:table.cell>S/ {{ number_format((float) $service->price, 2) }}</flux:table.cell>
                                <flux:table.cell>{{ $service->duration_minutes }} min</flux:table.cell>
                                <flux:table.cell class="max-w-[28rem]">
                                    @php
                                        $professionalNames = $service->professionalProfiles->isNotEmpty()
                                            ? $service->professionalProfiles->pluck('public_name')
                                            : $service->professionals->pluck('name');
                                        $visibleNames = $professionalNames->take(3);
                                        $remainingCount = max(0, $professionalNames->count() - $visibleNames->count());
                                    @endphp

                                    @if ($professionalNames->isEmpty())
                                        Sin profesionales
                                    @else
                                        <div class="flex flex-wrap gap-2">
                                            @foreach ($visibleNames as $name)
                                                <span class="inline-flex max-w-full items-center rounded-full bg-zinc-100 px-2.5 py-1 text-xs font-medium text-zinc-700 ring-1 ring-zinc-200 dark:bg-zinc-800 dark:text-zinc-200 dark:ring-zinc-700">
                                                    <span class="truncate">{{ $name }}</span>
                                                </span>
                                            @endforeach

                                            @if ($remainingCount > 0)
                                                <span class="inline-flex items-center rounded-full bg-cyan-50 px-2.5 py-1 text-xs font-semibold text-cyan-700 ring-1 ring-cyan-200 dark:bg-cyan-950/40 dark:text-cyan-300 dark:ring-cyan-900">
                                                    +{{ $remainingCount }} más
                                                </span>
                                            @endif
                                        </div>
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell>
                                    <flux:badge :color="$service->is_bookable_online ? 'sky' : 'amber'">
                                        {{ $service->is_bookable_online ? 'Activas' : 'Inactivas' }}
                                    </flux:badge>
                                </flux:table.cell>
                                <flux:table.cell>
                                    @if ($service->is_active)
                                        <flux:badge color="emerald">Activo</flux:badge>
                                    @else
                                        <flux:badge>Inactivo</flux:badge>
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell>
                                    <div class="flex items-center justify-end gap-2">
                                        <flux:button size="sm" variant="ghost" icon="pencil-square" wire:click="openEditModal({{ $service->id }})">
                                            Editar
                                        </flux:button>
                                        <flux:button
                                            size="sm"
                                            variant="ghost"
                                            icon="{{ $service->is_active ? 'pause-circle' : 'play-circle' }}"
                                            wire:click="toggleStatus({{ $service->id }})"
                                        >
                                            {{ $service->is_active ? 'Desactivar' : 'Activar' }}
                                        </flux:button>
                                        <flux:button size="sm" variant="danger" icon="trash" wire:click="confirmDelete({{ $service->id }})">
                                            Eliminar
                                        </flux:button>
                                    </div>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>

                <div class="border-t border-zinc-200/80 px-5 py-4 dark:border-zinc-700">
                    <flux:pagination :paginator="$services" />
                </div>
            @endif
        </flux:card>
    </div>

    <flux:modal
        name="upsert-service"
        wire:close="closeModal"
        wire:cancel="closeModal"
        class="w-full max-w-7xl"
    >
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $isEditing ? 'Editar servicio' : 'Nuevo servicio' }}</flux:heading>
                <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                    Configura por ahora solo los datos básicos del servicio.
                </flux:text>
            </div>

            <form wire:submit="save" class="space-y-6">
                <div class="rounded-2xl border border-zinc-200/80 p-4 dark:border-zinc-700">
                    <div class="mb-4">
                        <flux:heading size="base">Datos básicos</flux:heading>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        <flux:input wire:model="form.name" label="Nombre *" type="text" required />
                        <flux:select wire:model.live="form.service_category_id" label="Categoría *">
                            <option value="">Seleccionar categoría</option>
                            @foreach ($this->categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </flux:select>
                        <div class="flex items-end">
                            <flux:button type="button" variant="ghost" icon="plus" class="w-full justify-center" wire:click="openCategoryModal">
                                Nueva categoría
                            </flux:button>
                        </div>
                        <flux:input wire:model="form.price" label="Precio *" type="number" step="0.01" min="0" required />
                        <flux:input wire:model="form.duration_minutes" label="Duración en minutos *" type="number" min="1" required />

                        <flux:switch
                            wire:model.live="form.is_active"
                            label="Servicio activo"
                            description="Desactívalo temporalmente si no debe mostrarse ni reservarse."
                            align="left"
                        />
                    </div>
                </div>

                <div class="rounded-2xl border border-zinc-200/80 p-4 dark:border-zinc-700">
                    <div class="mb-4">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div>
                                <flux:heading size="base">Selecciona qué profesionales realizarán el servicio</flux:heading>
                                <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                                    Asigna uno o varios profesionales para que puedan ofrecer este servicio.
                                </flux:text>
                            </div>

                            <button
                                type="button"
                                wire:click="$toggle('showProfessionalPicker')"
                                class="inline-flex items-center gap-2 rounded-xl border border-zinc-200 bg-white px-3 py-2 text-sm font-medium text-zinc-600 shadow-sm transition hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800"
                                aria-label="Mostrar u ocultar selección de profesionales"
                            >
                                <span class="text-zinc-400">({{ count($form->professional_ids) }})</span>
                                <flux:icon.chevron-up class="size-4 transition-transform duration-200 {{ $showProfessionalPicker ? '' : 'rotate-180' }}" />
                            </button>
                        </div>
                    </div>

                    @if ($showProfessionalPicker)
                        <div class="rounded-2xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-950/30">
                            <div class="space-y-4">
                                <label class="flex items-center gap-3 text-sm font-semibold text-zinc-700 dark:text-zinc-200">
                                    <input
                                        type="checkbox"
                                        class="size-4 rounded border-zinc-300 text-cyan-600 focus:ring-cyan-500 dark:border-zinc-600 dark:bg-zinc-900"
                                        wire:click="selectAllProfessionals"
                                        @checked($this->professionalsCatalog->isNotEmpty() && count($form->professional_ids) === $this->professionalsCatalog->count())
                                    >
                                    <span>Seleccionar todo</span>
                                </label>

                                @if ($form->professional_ids !== [])
                                    <div class="flex flex-wrap gap-2">
                                        @foreach ($this->professionalsCatalog->whereIn('id', $form->professional_ids) as $selectedProfessional)
                                            <span class="inline-flex items-center rounded-full bg-cyan-50 px-3 py-1 text-xs font-semibold text-cyan-700 ring-1 ring-cyan-200 dark:bg-cyan-950/40 dark:text-cyan-300 dark:ring-cyan-900">
                                                {{ $selectedProfessional->public_name }}
                                            </span>
                                        @endforeach
                                    </div>
                                @endif

                                @if ($this->professionalsCatalog->isEmpty())
                                    <div class="rounded-xl border border-dashed border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-400">
                                        No hay profesionales activos disponibles.
                                    </div>
                                @else
                                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                        @foreach ($this->professionalsCatalog as $professional)
                                            <label wire:key="professional-option-{{ $professional->id }}" class="flex items-center gap-3 rounded-xl border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-700 shadow-sm transition hover:border-cyan-300 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200">
                                                <input
                                                    type="checkbox"
                                                    class="size-4 rounded border-zinc-300 text-cyan-600 focus:ring-cyan-500 dark:border-zinc-600 dark:bg-zinc-900"
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

                <div class="flex flex-col-reverse gap-3 border-t border-zinc-200/80 pt-4 dark:border-zinc-700 sm:flex-row sm:items-center sm:justify-end">
                    <flux:modal.close>
                        <flux:button variant="ghost" type="button" wire:click="closeModal">
                            Cancelar
                        </flux:button>
                    </flux:modal.close>

                    <flux:button variant="primary" type="submit">
                        {{ $isEditing ? 'Guardar cambios' : 'Guardar' }}
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    <flux:modal
        name="create-service-category"
        wire:close="closeCategoryModal"
        wire:cancel="closeCategoryModal"
        class="w-full max-w-lg"
    >
        <div class="space-y-5">
            <div>
                <flux:heading size="lg">Nueva categoría</flux:heading>
                <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                    Crea una categoría nueva y quedará seleccionada automáticamente en el servicio.
                </flux:text>
            </div>

            <div class="space-y-4">
                <flux:input wire:model="categoryName" label="Nombre de la categoría" type="text" required />
            </div>

            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <flux:modal.close>
                    <flux:button variant="ghost" type="button" wire:click="closeCategoryModal">
                        Cancelar
                    </flux:button>
                </flux:modal.close>

                <flux:button variant="primary" type="button" wire:click="saveCategory">
                    Guardar categoría
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <flux:modal
        name="delete-service"
        wire:close="closeDeleteModal"
        wire:cancel="closeDeleteModal"
        class="w-full max-w-lg"
    >
        <div class="space-y-5">
            <div>
                <flux:heading size="lg">Eliminar servicio</flux:heading>
                <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                    @if ($servicePendingDeletion)
                        Esta acción eliminará {{ $servicePendingDeletion->name }} si no tiene reservas asociadas. Si las tiene, el sistema lo desactivará.
                    @else
                        Esta acción eliminará el servicio seleccionado.
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
                    Eliminar servicio
                </flux:button>
            </div>
        </div>
    </flux:modal>
</section>
