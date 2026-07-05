<section >
    <div class="relative w-full overflow-hidden rounded-[24px]">
        <div class="space-y-5 px-1 py-2 sm:px-3 lg:px-0">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div class="min-w-0">
                    <h1 class="text-[2rem] font-semibold tracking-tight text-slate-900 dark:text-white">Clientes</h1>
                    <p class="mt-2 text-sm text-slate-600 dark:text-zinc-400">Gestiona todos los clientes de tu negocio.</p>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-end">
                    @if ($search !== '')
                        <flux:button
                            variant="outline"
                            icon="x-mark"
                            wire:click="clearFilters"
                            class="h-11 rounded-xl border-zinc-200 bg-white px-4 text-sm font-semibold text-slate-700 shadow-sm dark:border-white/10 dark:bg-white/[0.02] dark:text-white dark:shadow-none"
                        >
                            Limpiar
                        </flux:button>
                    @endif

                    <flux:button
                        variant="primary"
                        icon="plus"
                        wire:click="openCreateModal"
                        class="h-11 rounded-xl bg-emerald-600 px-4 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700 dark:bg-emerald-600 dark:shadow-none"
                    >
                        Nuevo cliente
                    </flux:button>
                </div>
            </div>

            <div class="rounded-[24px] border border-zinc-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-[#111820] dark:shadow-none">
                <div class="grid items-end gap-4 lg:grid-cols-[minmax(20rem,1fr)_auto]">
                    <div class="flex flex-col gap-1.5">
                        <label class="text-xs font-semibold uppercase tracking-[0.12em] text-transparent select-none">Buscar</label>
                        <flux:input
                            wire:model.live.debounce.300ms="search"
                            icon="magnifying-glass"
                            clearable
                            placeholder="Buscar por nombre, apellido, DNI, correo o teléfono..."
                            class="h-12 rounded-xl border-zinc-200 bg-white shadow-none dark:border-white/10 dark:bg-[#0d131a] dark:text-white"
                        />
                    </div>

                    @if ($search !== '')
                        <div class="flex justify-end">
                            <button
                                type="button"
                                wire:click="clearFilters"
                                class="inline-flex h-12 items-center gap-2 rounded-xl border border-zinc-200 bg-zinc-50 px-4 text-sm font-medium text-zinc-600 transition hover:border-zinc-300 hover:bg-zinc-100 dark:border-white/10 dark:bg-white/[0.02] dark:text-zinc-300 dark:hover:bg-white/[0.05]"
                            >
                                <flux:icon name="x-mark" class="size-4" />
                                <span>Limpiar</span>
                            </button>
                        </div>
                    @endif
                </div>
            </div>

            <div class="overflow-hidden rounded-[24px] border border-zinc-200 bg-white shadow-sm dark:border-white/10 dark:bg-[#111820] dark:shadow-none">
            @if ($clients->isEmpty())
                <div class="flex flex-col items-center justify-center gap-3 px-6 py-20 text-center">
                    <div class="flex size-16 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-300">
                        <flux:icon name="users" class="size-8" />
                    </div>

                    <div class="space-y-1">
                        <flux:heading size="lg" class="text-slate-900 dark:text-white">{{ $search !== '' ? 'No se encontraron clientes' : 'No hay clientes aún' }}</flux:heading>
                        <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                            {{ $search !== ''
                                ? 'Prueba quitando el filtro o cambia la búsqueda para ver resultados.'
                                : 'Crea tu primer cliente para comenzar a registrar información de contacto y seguimiento.' }}
                        </flux:text>
                    </div>

                    <div class="flex flex-wrap items-center justify-center gap-2">
                        @if ($search !== '')
                            <flux:button variant="outline" icon="x-mark" wire:click="clearFilters" class="h-11 rounded-xl border-zinc-200 bg-white px-4 text-sm font-semibold text-slate-700 shadow-sm dark:border-white/10 dark:bg-white/[0.02] dark:text-white dark:shadow-none">
                                Limpiar filtros
                            </flux:button>
                        @endif

                        <flux:button variant="primary" icon="plus" wire:click="openCreateModal" class="h-11 rounded-xl bg-emerald-600 px-4 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700 dark:bg-emerald-600 dark:shadow-none">
                            Nuevo cliente
                        </flux:button>
                    </div>
                </div>
            @else
                <div class="space-y-3 px-3 py-3 md:hidden">
                    @foreach ($clients as $client)
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
                                                {{ $client->fullName() }}
                                            </div>
                                            <div class="mt-1 truncate text-sm text-zinc-500 dark:text-zinc-400">
                                                {{ $client->email ?: 'Sin correo' }}
                                            </div>
                                        </div>

                                        <div class="shrink-0 text-right">
                                            <div class="text-sm font-semibold leading-none text-slate-900 dark:text-white">
                                                {{ $client->dni ?: 'Sin DNI' }}
                                            </div>
                                            <div class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                                                {{ $client->phone ?: 'Sin teléfono' }}
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <span class="inline-flex size-10 shrink-0 items-center justify-center rounded-2xl border border-zinc-200 bg-white text-zinc-500 transition dark:border-white/10 dark:bg-white/[0.03] dark:text-zinc-300">
                                    <flux:icon name="chevron-down" class="size-4 transition-transform" :class="expanded ? 'rotate-180' : ''" />
                                </span>
                            </button>

                            <div x-show="expanded" x-cloak x-transition.opacity.duration.200ms class="border-t border-zinc-100 px-4 py-4 dark:border-white/10">
                                <div class="grid grid-cols-2 gap-3 text-sm">
                                    <div class="rounded-[20px] bg-zinc-50 px-4 py-3 dark:bg-white/[0.03]">
                                        <div class="text-[11px] uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Nombre</div>
                                        <div class="mt-1 font-medium text-slate-900 dark:text-white">{{ $client->first_name ?: 'Sin nombre' }}</div>
                                    </div>

                                    <div class="rounded-[20px] bg-zinc-50 px-4 py-3 dark:bg-white/[0.03]">
                                        <div class="text-[11px] uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Apellido</div>
                                        <div class="mt-1 font-medium text-slate-900 dark:text-white">{{ $client->last_name ?: 'Sin apellido' }}</div>
                                    </div>

                                    <div class="rounded-[20px] bg-zinc-50 px-4 py-3 dark:bg-white/[0.03]">
                                        <div class="text-[11px] uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Correo</div>
                                        <div class="mt-1 font-medium text-slate-900 dark:text-white">{{ $client->email ?: 'Sin correo' }}</div>
                                    </div>

                                    <div class="rounded-[20px] bg-zinc-50 px-4 py-3 dark:bg-white/[0.03]">
                                        <div class="text-[11px] uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Teléfono</div>
                                        <div class="mt-1 font-medium text-slate-900 dark:text-white">{{ $client->phone ?: 'Sin teléfono' }}</div>
                                    </div>

                                    <div class="rounded-[20px] bg-zinc-50 px-4 py-3 dark:bg-white/[0.03]">
                                        <div class="text-[11px] uppercase tracking-wide text-zinc-500 dark:text-zinc-400">DNI</div>
                                        <div class="mt-1 font-medium text-slate-900 dark:text-white">{{ $client->dni ?: 'Sin DNI' }}</div>
                                    </div>

                                    <div class="rounded-[20px] bg-zinc-50 px-4 py-3 dark:bg-white/[0.03]">
                                        <div class="text-[11px] uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Número</div>
                                        <div class="mt-1 font-medium text-slate-900 dark:text-white">{{ $client->client_number ?: 'Sin número' }}</div>
                                    </div>

                                    <div class="col-span-2 rounded-[20px] bg-zinc-50 px-4 py-3 dark:bg-white/[0.03]">
                                        <div class="text-[11px] uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Dirección</div>
                                        <div class="mt-1 font-medium text-slate-900 dark:text-white">
                                            {{ collect([$client->address, $client->district, $client->city])->filter()->join(', ') ?: 'Sin dirección' }}
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-4 grid grid-cols-2 gap-2">
                                    <button
                                        type="button"
                                        class="inline-flex items-center justify-center rounded-2xl border border-zinc-200 bg-white px-3 py-2.5 text-zinc-600 transition hover:border-zinc-300 hover:bg-zinc-50 dark:border-white/10 dark:bg-white/[0.03] dark:text-zinc-200 dark:hover:bg-white/[0.06]"
                                        wire:click="showClient({{ $client->id }})"
                                        aria-label="Ver cliente"
                                    >
                                        <flux:icon name="eye" class="size-4" />
                                    </button>

                                    <button
                                        type="button"
                                        class="inline-flex items-center justify-center rounded-2xl bg-rose-500 px-3 py-2.5 text-white transition hover:bg-rose-600 dark:bg-rose-500 dark:hover:bg-rose-600"
                                        wire:click="confirmDelete({{ $client->id }})"
                                        aria-label="Eliminar cliente"
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
                                    <th class="border-b border-zinc-200 px-5 py-4 text-left text-xs font-semibold uppercase tracking-[0.08em] text-slate-700 dark:border-white/10 dark:text-zinc-300">
                                        <button type="button" wire:click="sort('first_name')" class="inline-flex items-center gap-2 transition hover:text-slate-900 dark:hover:text-white">
                                            <span>Nombre</span>
                                            <flux:icon name="chevron-up-down" class="size-4 {{ $sortBy === 'first_name' ? 'text-emerald-600 dark:text-emerald-400' : 'text-zinc-400' }}" />
                                        </button>
                                    </th>
                                    <th class="border-b border-zinc-200 px-5 py-4 text-left text-xs font-semibold uppercase tracking-[0.08em] text-slate-700 dark:border-white/10 dark:text-zinc-300">
                                        <button type="button" wire:click="sort('last_name')" class="inline-flex items-center gap-2 transition hover:text-slate-900 dark:hover:text-white">
                                            <span>Apellido</span>
                                            <flux:icon name="chevron-up-down" class="size-4 {{ $sortBy === 'last_name' ? 'text-emerald-600 dark:text-emerald-400' : 'text-zinc-400' }}" />
                                        </button>
                                    </th>
                                    <th class="border-b border-zinc-200 px-5 py-4 text-left text-xs font-semibold uppercase tracking-[0.08em] text-slate-700 dark:border-white/10 dark:text-zinc-300">
                                        <button type="button" wire:click="sort('email')" class="inline-flex items-center gap-2 transition hover:text-slate-900 dark:hover:text-white">
                                            <span>Correo</span>
                                            <flux:icon name="chevron-up-down" class="size-4 {{ $sortBy === 'email' ? 'text-emerald-600 dark:text-emerald-400' : 'text-zinc-400' }}" />
                                        </button>
                                    </th>
                                    <th class="border-b border-zinc-200 px-5 py-4 text-left text-xs font-semibold uppercase tracking-[0.08em] text-slate-700 dark:border-white/10 dark:text-zinc-300">
                                        <button type="button" wire:click="sort('phone')" class="inline-flex items-center gap-2 transition hover:text-slate-900 dark:hover:text-white">
                                            <span>Teléfono</span>
                                            <flux:icon name="chevron-up-down" class="size-4 {{ $sortBy === 'phone' ? 'text-emerald-600 dark:text-emerald-400' : 'text-zinc-400' }}" />
                                        </button>
                                    </th>
                                    <th class="border-b border-zinc-200 px-5 py-4 text-left text-xs font-semibold uppercase tracking-[0.08em] text-slate-700 dark:border-white/10 dark:text-zinc-300">
                                        <button type="button" wire:click="sort('dni')" class="inline-flex items-center gap-2 transition hover:text-slate-900 dark:hover:text-white">
                                            <span>DNI</span>
                                            <flux:icon name="chevron-up-down" class="size-4 {{ $sortBy === 'dni' ? 'text-emerald-600 dark:text-emerald-400' : 'text-zinc-400' }}" />
                                        </button>
                                    </th>
                                    <th class="border-b border-zinc-200 px-5 py-4 text-right text-xs font-semibold uppercase tracking-[0.08em] text-slate-700 dark:border-white/10 dark:text-zinc-300">Opciones</th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach ($clients as $client)
                                    <tr wire:key="client-row-{{ $client->id }}">
                                        <td class="border-b border-zinc-100 px-5 py-5 align-top font-semibold text-slate-900 dark:border-white/5 dark:text-white">
                                            {{ $client->first_name }}
                                        </td>
                                        <td class="border-b border-zinc-100 px-5 py-5 align-top text-sm text-slate-600 dark:border-white/5 dark:text-zinc-300">
                                            {{ $client->last_name }}
                                        </td>
                                        <td class="border-b border-zinc-100 px-5 py-5 align-top text-sm text-slate-600 dark:border-white/5 dark:text-zinc-300">
                                            {{ $client->email ?: 'Sin correo' }}
                                        </td>
                                        <td class="border-b border-zinc-100 px-5 py-5 align-top text-sm text-slate-600 dark:border-white/5 dark:text-zinc-300">
                                            {{ $client->phone ?: 'Sin teléfono' }}
                                        </td>
                                        <td class="border-b border-zinc-100 px-5 py-5 align-top text-sm text-slate-600 dark:border-white/5 dark:text-zinc-300">
                                            {{ $client->dni ?: 'Sin DNI' }}
                                        </td>
                                        <td class="border-b border-zinc-100 px-5 py-5 align-top dark:border-white/5">
                                            <div class="flex items-center justify-end gap-2">
                                                <button
                                                    type="button"
                                                    class="inline-flex size-9 items-center justify-center rounded-xl border border-zinc-200 bg-white text-zinc-600 transition hover:border-zinc-300 hover:bg-zinc-50 dark:border-white/10 dark:bg-white/[0.03] dark:text-zinc-200 dark:hover:bg-white/[0.06]"
                                                    wire:click="showClient({{ $client->id }})"
                                                    aria-label="Ver cliente"
                                                >
                                                    <flux:icon name="eye" class="size-4" />
                                                </button>

                                                <button
                                                    type="button"
                                                    class="inline-flex size-9 items-center justify-center rounded-xl bg-rose-500 text-white transition hover:bg-rose-600 dark:bg-rose-500 dark:hover:bg-rose-600"
                                                    wire:click="confirmDelete({{ $client->id }})"
                                                    aria-label="Eliminar cliente"
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
                        Mostrando {{ $clients->firstItem() }} a {{ $clients->lastItem() }} de {{ $clients->total() }} resultados
                    </div>

                    <div class="clients-pagination">
                        {{ $clients->links('vendor.pagination.livewire-table-clean') }}
                    </div>
                </div>
            @endif
            </div>
        </div>
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
