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

        <div class="grid gap-3 xl:grid-cols-[minmax(0,1fr)_240px_220px_160px] xl:items-center">
            <div class="w-full min-w-0">
                <flux:input
                    wire:model.live.debounce.300ms="search"
                    icon="magnifying-glass"
                    clearable
                    placeholder="Buscar por nombre o categoria"
                    class="rounded-2xl bg-white shadow-sm"
                />
            </div>

            <flux:select wire:model.live="categoryFilter" class="rounded-2xl bg-white shadow-sm">
                <option value="">Todas las categorias</option>
                @foreach ($this->categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="statusFilter" class="rounded-2xl bg-white shadow-sm">
                <option value="">Todos los estados</option>
                <option value="active">Activos</option>
                <option value="inactive">Inactivos</option>
            </flux:select>

            <flux:select wire:model.live="perPage" class="rounded-2xl bg-white shadow-sm">
                <option value="10">10 por pagina</option>
                <option value="25">25 por pagina</option>
                <option value="50">50 por pagina</option>
            </flux:select>
        </div>

        <div class="flex items-center justify-between text-sm text-zinc-500">
            <span>Mostrando {{ $services->count() }} de {{ $services->total() }} servicios</span>

            @if ($search !== '' || $categoryFilter !== '' || $statusFilter !== '')
                <button type="button" wire:click="clearFilters" class="font-medium text-zinc-700 underline underline-offset-4">
                    Limpiar filtros
                </button>
            @endif
        </div>

        @if ($services->isEmpty())
            <div class="rounded-[28px] border border-zinc-200/80 bg-white shadow-sm">
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
            </div>
        @else
            <div class="rounded-[28px] border border-zinc-200/80 bg-white shadow-sm">
                <div class="overflow-x-auto px-5 py-3">
                    <table class="w-max min-w-[1080px] divide-y divide-zinc-200 text-sm text-zinc-700">
                        <thead>
                            <tr class="text-left text-[11px] font-semibold uppercase tracking-[0.16em] text-zinc-500">
                                <th class="py-3 pe-4">Nombre</th>
                                <th class="px-4 py-3">Categoria</th>
                                <th class="px-4 py-3">Precio</th>
                                <th class="px-4 py-3">Duracion</th>
                                <th class="px-4 py-3">Profesionales asignados</th>
                                <th class="px-4 py-3">Reservas online</th>
                                <th class="px-4 py-3">Estado</th>
                                <th class="py-3 ps-4 text-right">Opciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200">
                            @foreach ($services as $service)
                                @php
                                    $professionalNames = $service->professionalProfiles->isNotEmpty()
                                        ? $service->professionalProfiles->pluck('public_name')
                                        : $service->professionals->pluck('name');
                                    $visibleNames = $professionalNames->take(2);
                                    $remainingCount = max(0, $professionalNames->count() - $visibleNames->count());
                                @endphp

                                <tr wire:key="service-row-{{ $service->id }}" class="align-middle">
                                    <td class="py-4 pe-4">
                                        <div class="flex items-center gap-3">
                                            <div class="flex size-8 shrink-0 items-center justify-center rounded-full bg-rose-50 text-rose-400 ring-1 ring-rose-100">
                                                <flux:icon.sparkles class="size-4" />
                                            </div>

                                            <div class="min-w-0">
                                                <div class="truncate font-medium text-zinc-900 dark:text-zinc-100">{{ $service->name }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 text-zinc-500 dark:text-zinc-400">{{ $service->category->name }}</td>
                                    <td class="px-4 py-4 whitespace-nowrap text-zinc-500 dark:text-zinc-400">S/ {{ number_format((float) $service->price, 2) }}</td>
                                    <td class="px-4 py-4 whitespace-nowrap text-zinc-500 dark:text-zinc-400">{{ $service->duration_minutes }} min</td>
                                    <td class="max-w-[18rem] px-4 py-4">
                                        @if ($professionalNames->isEmpty())
                                            <span class="text-zinc-400 dark:text-zinc-500">Sin profesionales</span>
                                        @else
                                            <div class="flex flex-wrap gap-2">
                                                @foreach ($visibleNames as $name)
                                                    <span class="inline-flex max-w-full items-center rounded-full bg-zinc-100 px-2.5 py-1 text-xs font-medium text-zinc-600 ring-1 ring-zinc-200 dark:bg-zinc-800 dark:text-zinc-200 dark:ring-zinc-700">
                                                        <span class="truncate">{{ $name }}</span>
                                                    </span>
                                                @endforeach

                                                @if ($remainingCount > 0)
                                                    <span class="inline-flex items-center rounded-full bg-sky-50 px-2.5 py-1 text-xs font-semibold text-sky-600 ring-1 ring-sky-200 dark:bg-sky-950/40 dark:text-sky-300 dark:ring-sky-900">
                                                        +{{ $remainingCount }} mas
                                                    </span>
                                                @endif
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4">
                                        <span class="inline-flex items-center rounded-lg px-2.5 py-1 text-xs font-medium {{ $service->is_bookable_online ? 'bg-sky-100 text-sky-700 dark:bg-sky-950/40 dark:text-sky-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300' }}">
                                            {{ $service->is_bookable_online ? 'Activas' : 'Inactivas' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-4">
                                        <span class="inline-flex items-center rounded-lg px-2.5 py-1 text-xs font-medium {{ $service->is_active ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300' : 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300' }}">
                                            {{ $service->is_active ? 'Activo' : 'Inactivo' }}
                                        </span>
                                    </td>
                                    <td class="py-4 ps-4">
                                        <div class="flex items-center justify-end gap-2 text-sm">
                                            <button type="button" wire:click="openEditModal({{ $service->id }})" class="inline-flex items-center gap-2 rounded-xl px-2.5 py-2 font-medium text-zinc-700 transition hover:bg-zinc-50 dark:text-zinc-200 dark:hover:bg-zinc-800">
                                                <flux:icon.pencil-square class="size-4" />
                                                <span>Editar</span>
                                            </button>
                                            <button
                                                type="button"
                                                wire:click="toggleStatus({{ $service->id }})"
                                                class="inline-flex items-center gap-2 rounded-xl px-2.5 py-2 font-medium text-zinc-700 transition hover:bg-zinc-50 dark:text-zinc-200 dark:hover:bg-zinc-800"
                                            >
                                                <flux:icon :icon="$service->is_active ? 'pause-circle' : 'play-circle'" class="size-4" />
                                                <span>{{ $service->is_active ? 'Desactivar' : 'Activar' }}</span>
                                            </button>
                                            <button type="button" wire:click="confirmDelete({{ $service->id }})" class="inline-flex items-center gap-2 rounded-xl bg-rose-500 px-3 py-2 font-medium text-white transition hover:bg-rose-600">
                                                <flux:icon.trash class="size-4" />
                                                <span>Eliminar</span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="flex items-center justify-between gap-4 text-sm text-zinc-500">
                <span>
                    Mostrando {{ $services->firstItem() ?? 0 }}-{{ $services->lastItem() ?? 0 }} de {{ $services->total() }} resultados
                </span>

                <flux:pagination :paginator="$services" />
            </div>
        @endif
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
                    Configura por ahora solo los datos basicos del servicio.
                </flux:text>
            </div>

            <form wire:submit="save" class="space-y-6">
                <div class="rounded-2xl border border-zinc-200/80 p-4 dark:border-zinc-700">
                    <div class="mb-4">
                        <flux:heading size="base">Datos basicos</flux:heading>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        <flux:input wire:model="form.name" label="Nombre *" type="text" required />
                        <flux:select wire:model.live="form.service_category_id" label="Categoria *">
                            <option value="">Seleccionar categoria</option>
                            @foreach ($this->categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </flux:select>
                        <div class="flex items-end">
                            <flux:button type="button" variant="ghost" icon="plus" class="w-full justify-center" wire:click="openCategoryModal">
                                Nueva categoria
                            </flux:button>
                        </div>
                        <flux:input wire:model="form.price" label="Precio *" type="number" step="0.01" min="0" required />
                        <flux:input wire:model="form.duration_minutes" label="Duracion en minutos *" type="number" min="1" required />

                        <flux:switch
                            wire:model.live="form.is_active"
                            label="Servicio activo"
                            description="Desactivalo temporalmente si no debe mostrarse ni reservarse."
                            align="left"
                        />
                    </div>
                </div>

                <div class="rounded-2xl border border-zinc-200/80 p-4 dark:border-zinc-700">
                    <div class="mb-4">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div>
                                <flux:heading size="base">Selecciona que profesionales realizaran el servicio</flux:heading>
                                <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                                    Asigna uno o varios profesionales para que puedan ofrecer este servicio.
                                </flux:text>
                            </div>

                            <button
                                type="button"
                                wire:click="$toggle('showProfessionalPicker')"
                                class="inline-flex items-center gap-2 rounded-xl border border-zinc-200 bg-white px-3 py-2 text-sm font-medium text-zinc-600 shadow-sm transition hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800"
                                aria-label="Mostrar u ocultar seleccion de profesionales"
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
                <flux:heading size="lg">Nueva categoria</flux:heading>
                <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                    Crea una categoria nueva y quedara seleccionada automaticamente en el servicio.
                </flux:text>
            </div>

            <div class="space-y-4">
                <flux:input wire:model="categoryName" label="Nombre de la categoria" type="text" required />
            </div>

            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <flux:modal.close>
                    <flux:button variant="ghost" type="button" wire:click="closeCategoryModal">
                        Cancelar
                    </flux:button>
                </flux:modal.close>

                <flux:button variant="primary" type="button" wire:click="saveCategory">
                    Guardar categoria
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
                        Esta accion eliminara {{ $servicePendingDeletion->name }} si no tiene reservas asociadas. Si las tiene, el sistema lo desactivara.
                    @else
                        Esta accion eliminara el servicio seleccionado.
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
