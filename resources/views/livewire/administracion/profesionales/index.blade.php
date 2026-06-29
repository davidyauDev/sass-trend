@php
    $professionalTabs = [
        'basic' => 'Datos básicos',
        'schedule' => 'Horario',
        'profile' => 'Perfil',
    ];
@endphp

<section class="w-full px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex w-full flex-col gap-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="min-w-0">
                <flux:heading size="xl" level="1" class="mt-3">Profesionales</flux:heading>
            </div>

            <div class="flex flex-wrap items-center gap-2">


                @if ($sectionTab === 'professionals')
                    <flux:button variant="primary" icon="plus" wire:click="openCreateModal">
                        Nuevo Profesional
                    </flux:button>
                @else
                    <flux:button variant="primary" icon="plus" wire:click="openCreateGroupModal">
                        Nuevo Grupo
                    </flux:button>
                @endif
            </div>
        </div>

        <div class="overflow-hidden rounded-[1.75rem] border border-zinc-200/80 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="border-b border-zinc-200/80 px-4 pt-4 dark:border-zinc-700 sm:px-6">
                <div class="flex flex-wrap gap-3">
                    <button
                        type="button"
                        wire:click="switchSection('professionals')"
                        class="{{ $sectionTab === 'professionals' ? 'border-violet-500 bg-white text-violet-600' : 'border-transparent bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300' }} rounded-t-2xl border border-b-0 px-5 py-3 text-sm font-semibold transition"
                    >
                        Profesionales
                    </button>
                    <button
                        type="button"
                        wire:click="switchSection('groups')"
                        class="{{ $sectionTab === 'groups' ? 'border-violet-500 bg-white text-violet-600' : 'border-transparent bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300' }} rounded-t-2xl border border-b-0 px-5 py-3 text-sm font-semibold transition"
                    >
                        Grupos Personalizados
                    </button>
                </div>
            </div>

            <div class="border-b border-zinc-200/80 px-4 py-4 dark:border-zinc-700 sm:px-6">
                <div class="rounded-2xl border border-sky-200 bg-sky-50 px-4 py-4 text-sm text-sky-800 dark:border-sky-900/50 dark:bg-sky-950/30 dark:text-sky-100">
                    @if ($sectionTab === 'professionals')
                        <span class="font-semibold">Edita a tu primer profesional</span> y luego agrega más personas a tu equipo de trabajo. Puedes editar sus horarios, qué servicios realizan y su perfil público.
                    @else
                        <span class="font-semibold">En esta sección</span> podrás crear grupos de profesionales para luego previsualizarlos en la agenda.
                    @endif
                </div>
            </div>

            @if ($showFilters)
                <div class="grid gap-4 border-b border-zinc-200/80 px-4 py-4 dark:border-zinc-700 sm:px-6 xl:grid-cols-[minmax(0,1fr)_240px_220px_140px]">
                    @if ($sectionTab === 'professionals')
                        <flux:input
                            wire:model.live.debounce.300ms="search"
                            icon="magnifying-glass"
                            clearable
                            placeholder="Buscar por nombre, servicio o grupo"
                        />
                    @else
                        <flux:input
                            wire:model.live.debounce.300ms="groupSearch"
                            icon="magnifying-glass"
                            clearable
                            placeholder="Buscar por grupo o local"
                        />
                    @endif

                    <flux:select wire:model.live="locationFilter">
                        <option value="">Todos los locales</option>
                        @foreach ($this->locationsCatalog as $location)
                            <option value="{{ $location->id }}">{{ $location->name }}</option>
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
            @endif

            @if ($sectionTab === 'professionals')
                <div class="space-y-4 px-4 py-6 sm:px-6">
                    @forelse ($professionals as $professional)
                        <div class="rounded-2xl border border-zinc-200/80 px-4 py-4 dark:border-zinc-700">
                            <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                                <div class="flex min-w-0 items-center gap-4">
                                    @if ($professional->photoUrl())
                                        <img src="{{ $professional->photoUrl() }}" alt="{{ $professional->displayName() }}" class="size-12 rounded-full object-cover">
                                    @else
                                        <div class="flex size-12 items-center justify-center rounded-full bg-zinc-300 text-sm font-semibold text-white">
                                            {{ $professional->initials() }}
                                        </div>
                                    @endif

                                    <div class="min-w-0">
                                        <div class="truncate text-base font-semibold text-zinc-900 dark:text-zinc-50">{{ $professional->displayName() }}</div>
                                        <div class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                                            {{ $professional->services->pluck('name')->join(', ') ?: 'Sin servicios asignados' }}
                                        </div>
                                    </div>
                                </div>

                                <div class="flex flex-wrap items-center gap-3 xl:justify-end">
                                    <button type="button" wire:click="openSchedulePreview({{ $professional->id }})" class="text-sm font-medium text-zinc-800 underline underline-offset-4 dark:text-zinc-200">
                                        Ver horario
                                    </button>

                                    <div class="text-sm {{ $professional->is_active ? 'text-emerald-600' : 'text-zinc-500' }}">
                                        <span class="mr-2 inline-block size-2 rounded-full {{ $professional->is_active ? 'bg-emerald-500' : 'bg-zinc-400' }}"></span>
                                        {{ $professional->is_active ? 'Activo' : 'Inactivo' }}
                                    </div>

                                    <flux:dropdown position="bottom" align="end">
                                        <flux:button variant="ghost" icon="ellipsis-horizontal">
                                            Opciones
                                        </flux:button>

                                        <flux:menu>
                                            <flux:menu.item icon="{{ $professional->is_active ? 'pause-circle' : 'play-circle' }}" wire:click="toggleStatus({{ $professional->id }})">
                                                {{ $professional->is_active ? 'Desactivar' : 'Activar' }}
                                            </flux:menu.item>
                                            <flux:menu.item icon="trash" variant="danger" wire:click="confirmDelete({{ $professional->id }})">
                                                Eliminar
                                            </flux:menu.item>
                                        </flux:menu>
                                    </flux:dropdown>

                                    <flux:button size="sm" variant="ghost" icon="pencil-square" wire:click="openEditModal({{ $professional->id }})">
                                        Editar
                                    </flux:button>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="flex flex-col items-center justify-center gap-3 px-6 py-20 text-center">
                            <div class="flex size-16 items-center justify-center rounded-3xl bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-300">
                                <flux:icon.users class="size-8" />
                            </div>
                            <flux:heading size="lg">No hay profesionales para mostrar</flux:heading>
                            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                                Crea tu primer profesional o ajusta los filtros para intentar de nuevo.
                            </flux:text>
                        </div>
                    @endforelse

                    @if ($professionals->isNotEmpty())
                        <flux:pagination :paginator="$professionals" />
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

                            {{-- Los permisos comienzan desactivados por defecto para nuevos profesionales. --}}
                            {{-- <flux:switch
                                wire:model.live="form.accepts_online_bookings"
                                label="Este profesional acepta reservas en línea"
                                align="left"
                            /> --}}

                            {{-- <div class="space-y-3">
                                <flux:switch
                                    wire:model.live="form.has_system_access"
                                    label="Crear un usuario a este profesional"
                                    description="Ingresa el email para que el profesional pueda ver su propia agenda."
                                    align="left"
                                />

                                @if ($form->has_system_access)
                                    <flux:input wire:model="form.email" label="Email" type="email" placeholder="Ingresa el email del profesional" />
                                @endif
                            </div> --}}
                        </div>
                    </div>

                    <div class="rounded-2xl border border-zinc-200/80 p-4 dark:border-zinc-700">
                        <flux:heading size="base">Selecciona los servicios que realiza el profesional</flux:heading>

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

                    <flux:button type="submit" variant="primary">
                        Guardar
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
