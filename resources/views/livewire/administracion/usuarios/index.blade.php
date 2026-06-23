<section class="w-full px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex w-full flex-col gap-6">
        <div class="grid gap-4 xl:grid-cols-[minmax(0,1.6fr)_minmax(280px,1fr)] xl:items-end">
            <div class="min-w-0">
                <flux:badge color="sky" size="sm" inset="left">Administración</flux:badge>
                <flux:heading size="xl" level="1" class="mt-3">Usuarios</flux:heading>
                <flux:subheading size="lg" class="mt-2">
                    Gestiona usuarios internos, roles, permisos específicos y locales asignados desde un único módulo.
                </flux:subheading>
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
                    Nuevo usuario
                </flux:button>
            </div>
        </div>

        <flux:card class="overflow-hidden border border-zinc-200/80 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="grid gap-4 border-b border-zinc-200/80 px-5 py-4 dark:border-zinc-700 xl:grid-cols-[minmax(0,1fr)_220px_220px_140px] xl:items-center">
                <div class="w-full min-w-0">
                    <flux:input
                        wire:model.live.debounce.300ms="search"
                        icon="magnifying-glass"
                        clearable
                        placeholder="Buscar por nombre, apellido o correo"
                    />
                </div>

                <flux:select wire:model.live="roleFilter">
                    <option value="">Todos los roles</option>
                    @foreach ($this->roles as $role)
                        <option value="{{ $role->id }}">{{ $role->name }}</option>
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
                <span>Mostrando {{ $users->count() }} de {{ $users->total() }} usuarios</span>

                @if ($search !== '' || $roleFilter !== '' || $statusFilter !== '')
                    <span class="hidden sm:inline">Filtros activos</span>
                @endif
            </div>

            @if ($users->isEmpty())
                <div class="flex flex-col items-center justify-center gap-3 px-6 py-16 text-center">
                    <div class="flex size-14 items-center justify-center rounded-2xl bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-300">
                        <flux:icon.users class="size-7" />
                    </div>

                    <div class="space-y-1">
                        <flux:heading size="lg">No hay usuarios para mostrar</flux:heading>
                        <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                            Crea el primer usuario interno o ajusta los filtros para intentar de nuevo.
                        </flux:text>
                    </div>

                    <div class="flex flex-wrap items-center justify-center gap-2">
                        <flux:button variant="primary" icon="plus" wire:click="openCreateModal">
                            Nuevo usuario
                        </flux:button>

                        <flux:button variant="ghost" icon="arrow-path" wire:click="clearFilters">
                            Limpiar filtros
                        </flux:button>
                    </div>
                </div>
            @else
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column class="w-[8%]">Foto</flux:table.column>
                        <flux:table.column class="w-[14%]">Nombre</flux:table.column>
                        <flux:table.column class="w-[14%]">Apellido</flux:table.column>
                        <flux:table.column class="w-[18%]">Correo</flux:table.column>
                        <flux:table.column class="w-[18%]">Rol</flux:table.column>
                        <flux:table.column class="w-[20%]">Locales asignados</flux:table.column>
                        <flux:table.column class="w-[8%]">Estado</flux:table.column>
                        <flux:table.column class="w-[8%] min-w-52 text-right">Opciones</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach ($users as $user)
                            <flux:table.row :key="$user->id">
                                <flux:table.cell>
                                    @if ($user->photoUrl())
                                        <img
                                            src="{{ $user->photoUrl() }}"
                                            alt="{{ $user->fullName() }}"
                                            class="size-11 rounded-2xl object-cover"
                                        >
                                    @else
                                        <div class="flex size-11 items-center justify-center rounded-2xl bg-zinc-100 text-xs font-semibold text-zinc-500 dark:bg-zinc-800 dark:text-zinc-300">
                                            {{ $user->initials() }}
                                        </div>
                                    @endif
                                </flux:table.cell>

                                <flux:table.cell class="font-medium text-zinc-900 dark:text-zinc-100">
                                    {{ $user->displayFirstName() }}
                                </flux:table.cell>

                                <flux:table.cell>
                                    {{ $user->displayLastName() }}
                                </flux:table.cell>

                                <flux:table.cell>
                                    {{ $user->email }}
                                </flux:table.cell>

                                <flux:table.cell>
                                    {{ $user->role?->name ?: 'Sin rol' }}
                                </flux:table.cell>

                                <flux:table.cell>
                                    {{ $user->locations->pluck('name')->join(', ') ?: 'Sin locales asignados' }}
                                </flux:table.cell>

                                <flux:table.cell>
                                    @if ($user->is_active)
                                        <flux:badge color="emerald">Activo</flux:badge>
                                    @else
                                        <flux:badge>Inactivo</flux:badge>
                                    @endif
                                </flux:table.cell>

                                <flux:table.cell>
                                    <div class="flex items-center justify-end gap-2">
                                        <flux:button size="sm" variant="ghost" icon="pencil-square" wire:click="openEditModal({{ $user->id }})">
                                            Editar
                                        </flux:button>

                                        <flux:button
                                            size="sm"
                                            variant="ghost"
                                            icon="{{ $user->is_active ? 'pause-circle' : 'play-circle' }}"
                                            wire:click="toggleStatus({{ $user->id }})"
                                        >
                                            {{ $user->is_active ? 'Desactivar' : 'Activar' }}
                                        </flux:button>

                                        <flux:button size="sm" variant="danger" icon="trash" wire:click="confirmDelete({{ $user->id }})">
                                            Eliminar
                                        </flux:button>
                                    </div>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>

                <div class="border-t border-zinc-200/80 px-5 py-4 dark:border-zinc-700">
                    <flux:pagination :paginator="$users" />
                </div>
            @endif
        </flux:card>
    </div>

    <flux:modal
        name="upsert-user"
        wire:close="closeModal"
        wire:cancel="closeModal"
        class="w-full max-w-7xl"
    >
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $isEditing ? 'Editar usuario' : 'Nuevo usuario' }}</flux:heading>
                <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                    Define datos básicos, rol, locales asignados y permisos específicos del usuario interno.
                </flux:text>
            </div>

            <form wire:submit="save" class="space-y-6">
                <div class="rounded-2xl border border-zinc-200/80 p-4 dark:border-zinc-700">
                    <div class="mb-4">
                        <flux:heading size="base">Información básica</flux:heading>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        <flux:input wire:model="form.first_name" label="Nombre *" type="text" required />
                        <flux:input wire:model="form.last_name" label="Apellido *" type="text" required />
                        <flux:input wire:model="form.email" label="Correo electrónico *" type="email" required />
                        <flux:input wire:model="form.phone" label="Teléfono" type="text" />
                        <flux:input wire:model="form.photo" label="Foto pública" type="file" accept="image/*" />
                        <flux:input
                            wire:model="form.password"
                            :label="$isEditing ? 'Nueva contraseña' : 'Contraseña *'"
                            type="password"
                            viewable
                            :required="! $isEditing"
                        />
                        <flux:input
                            wire:model="form.password_confirmation"
                            :label="$isEditing ? 'Confirmar nueva contraseña' : 'Confirmar contraseña *'"
                            type="password"
                            viewable
                            :required="! $isEditing"
                        />

                        <flux:switch
                            wire:model.live="form.is_active"
                            label="Usuario activo"
                            description="Puedes desactivarlo temporalmente sin eliminar el acceso configurado."
                            align="left"
                        />

                        <div class="md:col-span-2 xl:col-span-3">
                            @if ($form->photo)
                                <div class="space-y-3 rounded-2xl border border-dashed border-zinc-300 p-4 dark:border-zinc-600">
                                    <flux:text class="text-sm font-medium">Vista previa de la nueva foto</flux:text>
                                    <img src="{{ $form->photo->temporaryUrl() }}" alt="Vista previa del profesional" class="h-40 w-40 rounded-3xl object-cover">
                                </div>
                            @elseif ($form->existingPhotoPath)
                                <div class="space-y-3 rounded-2xl border border-dashed border-zinc-300 p-4 dark:border-zinc-600">
                                    <flux:text class="text-sm font-medium">Foto actual</flux:text>
                                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($form->existingPhotoPath) }}" alt="Foto actual del profesional" class="h-40 w-40 rounded-3xl object-cover">
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-zinc-200/80 p-4 dark:border-zinc-700">
                    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <flux:heading size="base">Rol y alcance</flux:heading>
                            <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                                El rol define el comportamiento base. Luego puedes ajustar permisos específicos.
                            </flux:text>
                        </div>

                        <flux:button type="button" variant="ghost" icon="sparkles" wire:click="applyRolePermissionsTemplate">
                            Aplicar permisos sugeridos del rol
                        </flux:button>
                    </div>

                    <div class="grid gap-4 lg:grid-cols-2">
                        <flux:select wire:model.live="form.role_id" label="Rol *">
                            <option value="">Seleccionar rol</option>
                            @foreach ($this->roles as $role)
                                <option value="{{ $role->id }}">{{ $role->name }}</option>
                            @endforeach
                        </flux:select>

                        <div>
                            <flux:label>Locales asignados</flux:label>
                            <div class="mt-3 grid gap-3 rounded-2xl border border-zinc-200/70 p-4 dark:border-zinc-700 sm:grid-cols-2">
                                @forelse ($this->locationsCatalog as $location)
                                    <flux:checkbox
                                        wire:model.live="form.location_ids"
                                        value="{{ $location->id }}"
                                        label="{{ $location->name }}"
                                        description="{{ $location->address }}"
                                    />
                                @empty
                                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                                        No hay locales creados todavía.
                                    </flux:text>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-zinc-200/80 p-4 dark:border-zinc-700">
                    <div class="mb-4">
                        <flux:heading size="base">Permisos específicos</flux:heading>
                        <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                            Ajusta el detalle fino del acceso por módulo. Administrador General conserva acceso total.
                        </flux:text>
                    </div>

                    <div class="grid gap-4 xl:grid-cols-2">
                            @foreach ($this->permissionGroups as $group => $permissions)
                            <div class="rounded-2xl border border-zinc-200/70 p-4 dark:border-zinc-700">
                                <flux:heading size="sm">{{ $group }}</flux:heading>

                                <div class="mt-4 grid gap-3">
                                    @foreach ($permissions as $permission)
                                        <flux:checkbox
                                            wire:model.live="form.permission_ids"
                                            value="{{ $permission->id }}"
                                            :label="$permission->name"
                                            :description="$permission->slug"
                                        />
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
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
        name="delete-user"
        wire:close="closeDeleteModal"
        wire:cancel="closeDeleteModal"
        class="w-full max-w-lg"
    >
        <div class="space-y-5">
            <div>
                <flux:heading size="lg">Eliminar usuario</flux:heading>
                <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                    @if ($userPendingDeletion)
                        Esta acción eliminará a {{ $userPendingDeletion->fullName() }} y sus asignaciones relacionadas.
                    @else
                        Esta acción eliminará el usuario seleccionado.
                    @endif
                </flux:text>
            </div>

            @error('deletion')
                <flux:callout color="red" icon="exclamation-triangle">
                    <flux:callout.text>{{ $message }}</flux:callout.text>
                </flux:callout>
            @enderror

            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <flux:modal.close>
                    <flux:button variant="ghost" type="button" wire:click="closeDeleteModal">
                        Cancelar
                    </flux:button>
                </flux:modal.close>

                <flux:button variant="danger" type="button" wire:click="delete">
                    Eliminar usuario
                </flux:button>
            </div>
        </div>
    </flux:modal>
</section>
