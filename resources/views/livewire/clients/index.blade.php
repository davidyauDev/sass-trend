<section class="w-full px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex w-full flex-col gap-6">
        <div class="grid gap-4 xl:grid-cols-[minmax(0,1.6fr)_minmax(320px,1fr)] xl:items-end">
            <div class="min-w-0">
                <flux:heading size="xl" level="1">Clientes</flux:heading>
                <flux:subheading size="lg" class="mt-2">
                    Gestiona tu base de clientes, crea nuevos registros y trabaja con audiencias desde un mismo listado.
                </flux:subheading>
            </div>

            <div class="flex flex-wrap items-center justify-start gap-2 xl:justify-end">
                <flux:button variant="ghost" icon="arrow-up-tray" wire:click="notifyPendingFeature('Cargar clientes')">
                    Cargar clientes
                </flux:button>

                <flux:button variant="ghost" icon="user-group" wire:click="notifyPendingFeature('Crear una audiencia con este listado')">
                    Crear una audiencia con este listado
                </flux:button>

                <flux:dropdown position="bottom" align="end">
                    <flux:button variant="ghost" icon="ellipsis-horizontal">
                        Acciones
                    </flux:button>

                    <flux:menu>
                        <flux:menu.item icon="arrow-path" wire:click="clearFilters">
                            Limpiar filtros
                        </flux:menu.item>

                        <flux:menu.item icon="arrow-down-tray" wire:click="notifyPendingFeature('Exportar clientes')">
                            Exportar clientes
                        </flux:menu.item>
                    </flux:menu>
                </flux:dropdown>

                <flux:button variant="primary" icon="plus" wire:click="openCreateModal">
                    Nuevo cliente
                </flux:button>
            </div>
        </div>

        <flux:card class="overflow-hidden border border-zinc-200/80 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="grid gap-4 border-b border-zinc-200/80 px-5 py-4 dark:border-zinc-700 xl:grid-cols-[minmax(0,1fr)_auto] xl:items-center">
                <div class="w-full min-w-0">
                    <flux:input
                        wire:model.live.debounce.300ms="search"
                        icon="magnifying-glass"
                        clearable
                        placeholder="Buscar por nombre, apellido, DNI, correo o teléfono"
                    />
                </div>

                <div class="flex items-center gap-3 xl:justify-end">
                    <div class="min-w-28">
                        <flux:select wire:model.live="perPage">
                            <option value="10">10 por página</option>
                            <option value="25">25 por página</option>
                            <option value="50">50 por página</option>
                        </flux:select>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-between border-b border-zinc-200/80 px-5 py-3 text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                <span>Mostrando {{ $clients->count() }} de {{ $clients->total() }} clientes</span>

                @if ($search !== '')
                    <span class="hidden sm:inline">Filtro activo: "{{ $search }}"</span>
                @endif
            </div>

            @if ($clients->isEmpty())
                <div class="flex flex-col items-center justify-center gap-3 px-6 py-16 text-center">
                    <div class="flex size-14 items-center justify-center rounded-2xl bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-300">
                        <flux:icon.users class="size-7" />
                    </div>

                    <div class="space-y-1">
                        <flux:heading size="lg">No hay clientes para mostrar</flux:heading>
                        <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                            Crea tu primer cliente o ajusta los filtros para intentar de nuevo.
                        </flux:text>
                    </div>

                    <div class="flex flex-wrap items-center justify-center gap-2">
                        <flux:button variant="primary" icon="plus" wire:click="openCreateModal">
                            Nuevo cliente
                        </flux:button>

                        <flux:button variant="ghost" icon="arrow-path" wire:click="clearFilters">
                            Limpiar filtros
                        </flux:button>
                    </div>
                </div>
            @else
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column class="w-[18%]">
                            <flux:table.sortable
                                wire:click="sort('first_name')"
                                :sorted="$sortBy === 'first_name'"
                                :direction="$sortDirection"
                            >
                                Nombre
                            </flux:table.sortable>
                        </flux:table.column>

                        <flux:table.column class="w-[18%]">
                            <flux:table.sortable
                                wire:click="sort('last_name')"
                                :sorted="$sortBy === 'last_name'"
                                :direction="$sortDirection"
                            >
                                Apellido
                            </flux:table.sortable>
                        </flux:table.column>

                        <flux:table.column class="w-[22%]">
                            <flux:table.sortable
                                wire:click="sort('email')"
                                :sorted="$sortBy === 'email'"
                                :direction="$sortDirection"
                            >
                                Correo
                            </flux:table.sortable>
                        </flux:table.column>

                        <flux:table.column class="w-[16%]">
                            <flux:table.sortable
                                wire:click="sort('phone')"
                                :sorted="$sortBy === 'phone'"
                                :direction="$sortDirection"
                            >
                                Teléfono
                            </flux:table.sortable>
                        </flux:table.column>

                        <flux:table.column class="w-[14%]">
                            <flux:table.sortable
                                wire:click="sort('dni')"
                                :sorted="$sortBy === 'dni'"
                                :direction="$sortDirection"
                            >
                                DNI
                            </flux:table.sortable>
                        </flux:table.column>

                        <flux:table.column class="w-[12%] min-w-40 text-right">
                            Opciones
                        </flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach ($clients as $client)
                            <flux:table.row :key="$client->id">
                                <flux:table.cell class="font-medium text-zinc-900 dark:text-zinc-100">
                                    {{ $client->first_name }}
                                </flux:table.cell>

                                <flux:table.cell>
                                    {{ $client->last_name }}
                                </flux:table.cell>

                                <flux:table.cell>
                                    {{ $client->email ?: 'Sin correo' }}
                                </flux:table.cell>

                                <flux:table.cell>
                                    {{ $client->phone ?: 'Sin teléfono' }}
                                </flux:table.cell>

                                <flux:table.cell>
                                    {{ $client->dni ?: 'Sin DNI' }}
                                </flux:table.cell>

                                <flux:table.cell>
                                    <div class="flex items-center justify-end gap-2">
                                        <flux:button size="sm" variant="ghost" icon="eye" wire:click="showClient({{ $client->id }})">
                                            Ver
                                        </flux:button>

                                        <flux:button size="sm" variant="danger" icon="trash" wire:click="confirmDelete({{ $client->id }})">
                                            Eliminar
                                        </flux:button>
                                    </div>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>

                <div class="border-t border-zinc-200/80 px-5 py-4 dark:border-zinc-700">
                    <flux:pagination :paginator="$clients" />
                </div>
            @endif
        </flux:card>
    </div>

    <flux:modal
        name="create-client"
        wire:close="closeCreateModal"
        wire:cancel="closeCreateModal"
        class="w-full max-w-7xl"
    >
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Nuevo cliente</flux:heading>
                <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                    Completa la información principal y de contacto para registrar un nuevo cliente.
                </flux:text>
            </div>

            <form wire:submit="save" class="space-y-6">
                <div class="rounded-2xl border border-zinc-200/80 p-4 dark:border-zinc-700">
                    <div class="mb-4">
                        <flux:heading size="base">Información personal</flux:heading>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <flux:input wire:model="form.first_name" label="Nombre *" type="text" required />
                        <flux:input wire:model="form.last_name" label="Apellido *" type="text" required />
                        <flux:input wire:model="form.birth_date" label="Fecha de nacimiento" type="date" />
                        <flux:input wire:model="form.age" label="Edad" type="number" min="0" max="150" />
                        <flux:input wire:model="form.dni" label="DNI" type="text" />

                        <div>
                            <label class="mb-2 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Género</label>
                            <flux:select wire:model="form.gender">
                                <option value="">Seleccionar</option>
                                <option value="Femenino">Femenino</option>
                                <option value="Masculino">Masculino</option>
                                <option value="No binario">No binario</option>
                                <option value="Prefiero no decirlo">Prefiero no decirlo</option>
                            </flux:select>
                        </div>

                        <flux:input wire:model="form.client_number" label="Número de cliente" type="text" class="md:col-span-2" />
                    </div>
                </div>

                <div class="rounded-2xl border border-zinc-200/80 p-4 dark:border-zinc-700">
                    <div class="mb-4">
                        <flux:heading size="base">Información de contacto</flux:heading>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <flux:input wire:model="form.email" label="Correo electrónico" type="email" />
                        <flux:input wire:model="form.phone" label="Teléfono" type="text" />
                        <flux:input wire:model="form.address" label="Dirección" type="text" class="md:col-span-2" />
                        <flux:input wire:model="form.district" label="Distrito" type="text" />
                        <flux:input wire:model="form.city" label="Ciudad" type="text" />
                    </div>
                </div>

                <div class="flex flex-col-reverse gap-3 border-t border-zinc-200/80 pt-4 dark:border-zinc-700 sm:flex-row sm:items-center sm:justify-end">
                    <flux:modal.close>
                        <flux:button variant="ghost" type="button" wire:click="closeCreateModal">
                            Cancelar
                        </flux:button>
                    </flux:modal.close>

                    <flux:button variant="primary" type="submit">
                        Guardar
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    <flux:modal
        name="show-client"
        wire:close="closeShowClientModal"
        wire:cancel="closeShowClientModal"
        class="w-full max-w-3xl"
    >
        @if ($selectedClient)
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">{{ $selectedClient->fullName() }}</flux:heading>
                    <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                        Resumen de la información registrada para este cliente.
                    </flux:text>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="rounded-2xl border border-zinc-200/80 p-4 dark:border-zinc-700">
                        <flux:text class="text-xs uppercase tracking-wide text-zinc-400">Correo</flux:text>
                        <flux:heading size="base" class="mt-2">{{ $selectedClient->email ?: 'Sin correo' }}</flux:heading>
                    </div>

                    <div class="rounded-2xl border border-zinc-200/80 p-4 dark:border-zinc-700">
                        <flux:text class="text-xs uppercase tracking-wide text-zinc-400">Teléfono</flux:text>
                        <flux:heading size="base" class="mt-2">{{ $selectedClient->phone ?: 'Sin teléfono' }}</flux:heading>
                    </div>

                    <div class="rounded-2xl border border-zinc-200/80 p-4 dark:border-zinc-700">
                        <flux:text class="text-xs uppercase tracking-wide text-zinc-400">DNI</flux:text>
                        <flux:heading size="base" class="mt-2">{{ $selectedClient->dni ?: 'Sin DNI' }}</flux:heading>
                    </div>

                    <div class="rounded-2xl border border-zinc-200/80 p-4 dark:border-zinc-700">
                        <flux:text class="text-xs uppercase tracking-wide text-zinc-400">Número de cliente</flux:text>
                        <flux:heading size="base" class="mt-2">{{ $selectedClient->client_number ?: 'Sin número' }}</flux:heading>
                    </div>

                    <div class="rounded-2xl border border-zinc-200/80 p-4 dark:border-zinc-700">
                        <flux:text class="text-xs uppercase tracking-wide text-zinc-400">Fecha de nacimiento</flux:text>
                        <flux:heading size="base" class="mt-2">{{ $selectedClient->birth_date?->format('Y-m-d') ?: 'Sin fecha' }}</flux:heading>
                    </div>

                    <div class="rounded-2xl border border-zinc-200/80 p-4 dark:border-zinc-700">
                        <flux:text class="text-xs uppercase tracking-wide text-zinc-400">Edad</flux:text>
                        <flux:heading size="base" class="mt-2">{{ $selectedClient->age !== null ? $selectedClient->age : 'Sin edad' }}</flux:heading>
                    </div>

                    <div class="rounded-2xl border border-zinc-200/80 p-4 dark:border-zinc-700 md:col-span-2">
                        <flux:text class="text-xs uppercase tracking-wide text-zinc-400">Dirección</flux:text>
                        <flux:heading size="base" class="mt-2">
                            {{ collect([$selectedClient->address, $selectedClient->district, $selectedClient->city])->filter()->join(', ') ?: 'Sin dirección' }}
                        </flux:heading>
                    </div>
                </div>

                <div class="flex justify-end border-t border-zinc-200/80 pt-4 dark:border-zinc-700">
                    <flux:modal.close>
                        <flux:button variant="primary" type="button">
                            Cerrar
                        </flux:button>
                    </flux:modal.close>
                </div>
            </div>
        @endif
    </flux:modal>

    <flux:modal
        name="delete-client"
        wire:close="closeDeleteModal"
        wire:cancel="closeDeleteModal"
        class="w-full max-w-lg"
    >
        <div class="space-y-5">
            <div>
                <flux:heading size="lg">Eliminar cliente</flux:heading>
                <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                    @if ($clientPendingDeletion)
                        Esta acción eliminará a {{ $clientPendingDeletion->fullName() }} de forma permanente.
                    @else
                        Esta acción eliminará el cliente seleccionado de forma permanente.
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
                    Eliminar cliente
                </flux:button>
            </div>
        </div>
    </flux:modal>
</section>
