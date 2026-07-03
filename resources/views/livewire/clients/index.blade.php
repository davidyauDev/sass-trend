<section class="w-full px-4 py-4 sm:px-6 lg:px-8">
    <div class="flex w-full flex-col gap-5">
        <div class="grid gap-3 xl:grid-cols-[auto_minmax(0,1fr)] xl:items-start">
            <div class="min-w-0 pt-0 xl:pt-2">
                <flux:heading size="xl" level="1" class="mt-0 leading-none">Clientes</flux:heading>
            </div>

            <div class="flex w-full flex-col gap-2 xl:w-auto xl:flex-row xl:items-end xl:justify-end">
                <div class="grid w-full gap-2 sm:grid-cols-2 xl:w-auto xl:grid-cols-[minmax(16rem,22rem)_auto] xl:items-end">
                    <flux:input
                        wire:model.live.debounce.300ms="search"
                        icon="magnifying-glass"
                        clearable
                        placeholder="Buscar por nombre, apellido, DNI, correo o teléfono"
                        class="w-full rounded-2xl border-zinc-200 bg-zinc-50 shadow-sm"
                    />

                    <flux:button variant="ghost" icon="x-mark" wire:click="clearFilters">
                        Limpiar
                    </flux:button>
                </div>

                <div class="flex w-full items-end gap-2 xl:w-auto">
                    <flux:button variant="primary" icon="plus" wire:click="openCreateModal">
                        Nuevo cliente
                    </flux:button>
                </div>
            </div>
        </div>

        <flux:card class="overflow-hidden border border-zinc-200/80 bg-white shadow-sm">
            @if ($clients->isEmpty())
                <div class="flex flex-col items-center justify-center gap-3 px-6 py-20 text-center">
                    <div class="flex size-16 items-center justify-center rounded-2xl bg-violet-50 text-violet-600">
                        <flux:icon name="users" class="size-8" />
                    </div>

                    <div class="space-y-1">
                        <flux:heading size="lg">{{ $search !== '' ? 'No se encontraron clientes' : 'No hay clientes aún' }}</flux:heading>
                        <flux:text class="text-sm text-zinc-500">
                            {{ $search !== ''
                                ? 'Prueba quitando el filtro o cambia la búsqueda para ver resultados.'
                                : 'Crea tu primer cliente para comenzar a registrar información de contacto y seguimiento.' }}
                        </flux:text>
                    </div>

                    <div class="flex flex-wrap items-center justify-center gap-2">
                        @if ($search !== '')
                            <flux:button variant="ghost" icon="x-mark" wire:click="clearFilters">
                                Limpiar filtros
                            </flux:button>
                        @endif

                        <flux:button variant="primary" icon="plus" wire:click="openCreateModal">
                            Nuevo cliente
                        </flux:button>
                    </div>
                </div>
            @else
                <div class="space-y-2 px-0 pb-4 pt-0 md:hidden">
                    @foreach ($clients as $client)
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
                                        {{ $client->fullName() }}
                                    </div>

                                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-zinc-500">
                                        <span class="truncate">
                                            {{ $client->client_number ?: ($client->dni ?: 'Sin documento') }}
                                        </span>

                                        <span class="hidden h-1 w-1 rounded-full bg-zinc-300 sm:inline-block"></span>

                                        <span class="truncate">
                                            {{ $client->phone ?: 'Sin teléfono' }}
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
                                        <div class="text-[11px] uppercase tracking-wide text-zinc-500">DNI</div>
                                        <div class="mt-1 font-medium text-zinc-900">{{ $client->dni ?: 'Sin DNI' }}</div>
                                    </div>

                                    <div class="rounded-2xl bg-zinc-50 px-3 py-2">
                                        <div class="text-[11px] uppercase tracking-wide text-zinc-500">Género</div>
                                        <div class="mt-1 font-medium text-zinc-900">{{ $client->gender ?: 'Sin género' }}</div>
                                    </div>

                                    <div class="rounded-2xl bg-zinc-50 px-3 py-2">
                                        <div class="text-[11px] uppercase tracking-wide text-zinc-500">Correo</div>
                                        <div class="mt-1 font-medium text-zinc-900">{{ $client->email ?: 'Sin correo' }}</div>
                                    </div>

                                    <div class="rounded-2xl bg-zinc-50 px-3 py-2">
                                        <div class="text-[11px] uppercase tracking-wide text-zinc-500">Teléfono</div>
                                        <div class="mt-1 font-medium text-zinc-900">{{ $client->phone ?: 'Sin teléfono' }}</div>
                                    </div>

                                    <div class="rounded-2xl bg-zinc-50 px-3 py-2">
                                        <div class="text-[11px] uppercase tracking-wide text-zinc-500">Edad</div>
                                        <div class="mt-1 font-medium text-zinc-900">{{ $client->age ?? 'Sin edad' }}</div>
                                    </div>

                                    <div class="rounded-2xl bg-zinc-50 px-3 py-2">
                                        <div class="text-[11px] uppercase tracking-wide text-zinc-500">Ciudad</div>
                                        <div class="mt-1 font-medium text-zinc-900">{{ $client->city ?: 'Sin ciudad' }}</div>
                                    </div>

                                    <div class="rounded-2xl bg-zinc-50 px-3 py-2 col-span-2">
                                        <div class="text-[11px] uppercase tracking-wide text-zinc-500">Dirección</div>
                                        <div class="mt-1 font-medium text-zinc-900">
                                            {{ collect([$client->address, $client->district, $client->city])->filter()->join(', ') ?: 'Sin dirección' }}
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-4 grid grid-cols-2 gap-2">
                                    <button
                                        type="button"
                                        class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-violet-200 bg-violet-50 px-3 py-2 text-sm font-medium text-violet-700 transition hover:border-violet-300 hover:bg-violet-100"
                                        wire:click="showClient({{ $client->id }})"
                                    >
                                        <flux:icon name="eye" class="size-4" />
                                        <span>Ver</span>
                                    </button>

                                    <button
                                        type="button"
                                        class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm font-medium text-rose-700 transition hover:border-rose-300 hover:bg-rose-100"
                                        wire:click="confirmDelete({{ $client->id }})"
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

                            <flux:table.column class="w-[7rem] min-w-[7rem] text-center">
                                Opciones
                            </flux:table.column>
                        </flux:table.columns>

                        <flux:table.rows>
                            @foreach ($clients as $client)
                                <flux:table.row :key="$client->id">
                                    <flux:table.cell class="font-medium text-zinc-900">
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

                                    <flux:table.cell class="text-center align-middle">
                                        <div class="inline-flex items-center justify-center gap-2 whitespace-nowrap">
                                            <flux:button
                                                size="sm"
                                                variant="ghost"
                                                icon="eye"
                                                aria-label="Ver cliente"
                                                wire:click="showClient({{ $client->id }})"
                                            ></flux:button>

                                            <flux:button
                                                size="sm"
                                                variant="danger"
                                                icon="trash"
                                                aria-label="Eliminar cliente"
                                                wire:click="confirmDelete({{ $client->id }})"
                                            ></flux:button>
                                        </div>
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>

                    </div>
                </div>

                <div class="border-t border-zinc-200/80 px-2 py-3 md:px-4">
                    {{ $clients->links() }}
                </div>
            @endif
        </flux:card>
    </div>

    @if ($createClientOpen)
        <div
            class="fixed inset-0 z-[60] flex items-start justify-center bg-zinc-950/50 px-3 py-4 backdrop-blur-[2px] sm:items-center sm:px-4 sm:py-6"
            wire:click.self="closeCreateModal"
        >
            <div class="relative flex h-full w-full max-w-5xl max-h-[100vh] flex-col overflow-hidden rounded-none bg-white shadow-[0_30px_100px_rgba(0,0,0,0.25)] ring-1 ring-violet-200 sm:max-h-[92vh] sm:rounded-[30px]">
                <div class="flex items-start justify-between gap-4 border-b border-violet-100 px-4 py-4 sm:px-6 sm:py-5">
                    <div>
                        <flux:heading size="lg">Nuevo cliente</flux:heading>
                        <flux:text class="mt-1 text-sm text-zinc-500">
                            Completa la información principal y de contacto para registrar un nuevo cliente.
                        </flux:text>
                    </div>

                    <button
                        type="button"
                        class="rounded-full p-2 text-zinc-500 transition hover:bg-zinc-100 hover:text-zinc-900"
                        wire:click="closeCreateModal"
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
                                    <flux:heading size="base">Información personal</flux:heading>
                                </div>

                                <div class="grid gap-4 lg:grid-cols-2">
                                    <div class="space-y-1.5">
                                        <flux:input wire:model="form.first_name" label="Nombre *" type="text" required class="rounded-2xl" />
                                        @error('form.first_name')
                                            <p class="text-sm text-rose-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div class="space-y-1.5">
                                        <flux:input wire:model="form.last_name" label="Apellido *" type="text" required class="rounded-2xl" />
                                        @error('form.last_name')
                                            <p class="text-sm text-rose-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div class="space-y-1.5">
                                        <flux:input wire:model="form.birth_date" label="Fecha de nacimiento" type="date" class="rounded-2xl" />
                                        @error('form.birth_date')
                                            <p class="text-sm text-rose-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div class="space-y-1.5">
                                        <flux:input wire:model="form.age" label="Edad" type="number" min="0" max="150" class="rounded-2xl" />
                                        @error('form.age')
                                            <p class="text-sm text-rose-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div class="space-y-1.5">
                                        <flux:input wire:model="form.dni" label="DNI" type="text" class="rounded-2xl" />
                                        @error('form.dni')
                                            <p class="text-sm text-rose-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div class="space-y-1.5">
                                        <label class="text-sm font-medium text-zinc-700">Género</label>
                                        <flux:select wire:model="form.gender" class="rounded-2xl">
                                            <option value="">Seleccionar</option>
                                            <option value="Femenino">Femenino</option>
                                            <option value="Masculino">Masculino</option>
                                            <option value="No binario">No binario</option>
                                            <option value="Prefiero no decirlo">Prefiero no decirlo</option>
                                        </flux:select>
                                        @error('form.gender')
                                            <p class="text-sm text-rose-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div class="space-y-1.5 lg:col-span-2">
                                        <flux:input wire:model="form.client_number" label="Número de cliente" type="text" class="rounded-2xl" />
                                        @error('form.client_number')
                                            <p class="text-sm text-rose-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="rounded-[24px] border border-zinc-200/80 p-5">
                                <div class="mb-5">
                                    <flux:heading size="base">Información de contacto</flux:heading>
                                </div>

                                <div class="grid gap-4 lg:grid-cols-2">
                                    <div class="space-y-1.5">
                                        <flux:input wire:model="form.email" label="Correo electrónico" type="email" class="rounded-2xl" />
                                        @error('form.email')
                                            <p class="text-sm text-rose-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div class="space-y-1.5">
                                        <flux:input wire:model="form.phone" label="Teléfono" type="text" class="rounded-2xl" />
                                        @error('form.phone')
                                            <p class="text-sm text-rose-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div class="space-y-1.5 lg:col-span-2">
                                        <flux:input wire:model="form.address" label="Dirección" type="text" class="rounded-2xl" />
                                        @error('form.address')
                                            <p class="text-sm text-rose-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div class="space-y-1.5">
                                        <flux:input wire:model="form.district" label="Distrito" type="text" class="rounded-2xl" />
                                        @error('form.district')
                                            <p class="text-sm text-rose-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div class="space-y-1.5">
                                        <flux:input wire:model="form.city" label="Ciudad" type="text" class="rounded-2xl" />
                                        @error('form.city')
                                            <p class="text-sm text-rose-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col-reverse gap-3 border-t border-zinc-200 bg-zinc-50 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                        <button
                            type="button"
                            class="inline-flex h-10 items-center justify-center rounded-xl bg-zinc-100 px-4 text-sm font-medium text-zinc-700 shadow-sm transition hover:bg-zinc-200"
                            wire:click="closeCreateModal"
                        >
                            Cancelar
                        </button>

                        <button
                            type="submit"
                            class="inline-flex h-10 items-center justify-center rounded-xl bg-violet-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-violet-700"
                        >
                            Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @if ($showClientOpen && $selectedClient)
        <div
            class="fixed inset-0 z-[60] flex items-start justify-center bg-zinc-950/50 px-3 py-4 backdrop-blur-[2px] sm:items-center sm:px-4 sm:py-6"
            wire:click.self="closeShowClientModal"
        >
            <div class="relative flex h-full w-full max-w-4xl max-h-[100vh] flex-col overflow-hidden rounded-none bg-white shadow-[0_30px_100px_rgba(0,0,0,0.25)] ring-1 ring-violet-200 sm:max-h-[92vh] sm:rounded-[30px]">
                <div class="flex items-start justify-between gap-4 border-b border-violet-100 px-4 py-4 sm:px-6 sm:py-5">
                    <div>
                        <flux:heading size="lg">{{ $selectedClient->fullName() }}</flux:heading>
                        <flux:text class="mt-1 text-sm text-zinc-500">
                            Resumen de la información registrada para este cliente.
                        </flux:text>
                    </div>

                    <button
                        type="button"
                        class="rounded-full p-2 text-zinc-500 transition hover:bg-zinc-100 hover:text-zinc-900"
                        wire:click="closeShowClientModal"
                        aria-label="Cerrar modal"
                    >
                        <flux:icon name="x-mark" class="size-6" />
                    </button>
                </div>

                <div class="min-h-0 flex-1 overflow-y-auto px-4 py-4 sm:px-6 sm:py-5">
                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="rounded-[24px] border border-zinc-200/80 p-4">
                            <flux:text class="text-xs uppercase tracking-wide text-zinc-400">Correo</flux:text>
                            <flux:heading size="base" class="mt-2">{{ $selectedClient->email ?: 'Sin correo' }}</flux:heading>
                        </div>

                        <div class="rounded-[24px] border border-zinc-200/80 p-4">
                            <flux:text class="text-xs uppercase tracking-wide text-zinc-400">Teléfono</flux:text>
                            <flux:heading size="base" class="mt-2">{{ $selectedClient->phone ?: 'Sin teléfono' }}</flux:heading>
                        </div>

                        <div class="rounded-[24px] border border-zinc-200/80 p-4">
                            <flux:text class="text-xs uppercase tracking-wide text-zinc-400">DNI</flux:text>
                            <flux:heading size="base" class="mt-2">{{ $selectedClient->dni ?: 'Sin DNI' }}</flux:heading>
                        </div>

                        <div class="rounded-[24px] border border-zinc-200/80 p-4">
                            <flux:text class="text-xs uppercase tracking-wide text-zinc-400">Número de cliente</flux:text>
                            <flux:heading size="base" class="mt-2">{{ $selectedClient->client_number ?: 'Sin número' }}</flux:heading>
                        </div>

                        <div class="rounded-[24px] border border-zinc-200/80 p-4">
                            <flux:text class="text-xs uppercase tracking-wide text-zinc-400">Fecha de nacimiento</flux:text>
                            <flux:heading size="base" class="mt-2">{{ $selectedClient->birth_date?->format('Y-m-d') ?: 'Sin fecha' }}</flux:heading>
                        </div>

                        <div class="rounded-[24px] border border-zinc-200/80 p-4">
                            <flux:text class="text-xs uppercase tracking-wide text-zinc-400">Edad</flux:text>
                            <flux:heading size="base" class="mt-2">{{ $selectedClient->age !== null ? $selectedClient->age : 'Sin edad' }}</flux:heading>
                        </div>

                        <div class="rounded-[24px] border border-zinc-200/80 p-4 md:col-span-2">
                            <flux:text class="text-xs uppercase tracking-wide text-zinc-400">Dirección</flux:text>
                            <flux:heading size="base" class="mt-2">
                                {{ collect([$selectedClient->address, $selectedClient->district, $selectedClient->city])->filter()->join(', ') ?: 'Sin dirección' }}
                            </flux:heading>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end border-t border-zinc-200 bg-zinc-50 px-4 py-4 sm:px-6">
                    <button
                        type="button"
                        class="inline-flex h-10 items-center justify-center rounded-xl bg-violet-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-violet-700"
                        wire:click="closeShowClientModal"
                    >
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    @endif

    @if ($deleteClientOpen)
        <div
            class="fixed inset-0 z-[60] flex items-start justify-center bg-zinc-950/50 px-3 py-4 backdrop-blur-[2px] sm:items-center sm:px-4 sm:py-6"
            wire:click.self="closeDeleteModal"
        >
            <div class="relative flex h-full w-full max-w-lg max-h-[100vh] flex-col overflow-hidden rounded-none bg-white shadow-[0_30px_100px_rgba(0,0,0,0.25)] ring-1 ring-violet-200 sm:max-h-[92vh] sm:rounded-[30px]">
                <div class="flex items-start justify-between gap-4 border-b border-violet-100 px-4 py-4 sm:px-6 sm:py-5">
                    <div>
                        <flux:heading size="lg">Eliminar cliente</flux:heading>
                        <flux:text class="mt-1 text-sm text-zinc-500">
                            @if ($clientPendingDeletion)
                                Esta acción eliminará a {{ $clientPendingDeletion->fullName() }} de forma permanente.
                            @else
                                Esta acción eliminará el cliente seleccionado de forma permanente.
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

                <div class="flex flex-1 items-end px-4 py-4 sm:px-6">
                    <div class="w-full space-y-4">
                        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                            Esta acción es permanente y no se puede deshacer.
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
                                Eliminar cliente
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</section>
