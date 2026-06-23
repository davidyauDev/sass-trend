<section class="w-full px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex w-full flex-col gap-6">
        <div class="grid gap-4 xl:grid-cols-[minmax(0,1.6fr)_minmax(280px,1fr)] xl:items-end">
            <div class="min-w-0">
                <flux:badge color="sky" size="sm" inset="left">SaaS</flux:badge>
                <flux:heading size="xl" level="1" class="mt-3">Tenants</flux:heading>
                <flux:subheading size="lg" class="mt-2">
                    Crea negocios, asigna su dueño inicial y controla el estado del workspace compartido.
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
                    Nuevo tenant
                </flux:button>
            </div>
        </div>

        <flux:card class="overflow-hidden border border-zinc-200/80 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="grid gap-4 border-b border-zinc-200/80 px-5 py-4 dark:border-zinc-700 xl:grid-cols-[minmax(0,1fr)_180px_180px_140px] xl:items-center">
                <flux:input
                    wire:model.live.debounce.300ms="search"
                    icon="magnifying-glass"
                    clearable
                    placeholder="Buscar por negocio, slug o correo"
                />

                <flux:select wire:model.live="statusFilter">
                    <option value="">Todos los estados</option>
                    <option value="{{ \App\Models\Tenant::STATUS_PENDING }}">Pendiente</option>
                    <option value="{{ \App\Models\Tenant::STATUS_ACTIVE }}">Activo</option>
                    <option value="{{ \App\Models\Tenant::STATUS_SUSPENDED }}">Suspendido</option>
                    <option value="{{ \App\Models\Tenant::STATUS_FAILED }}">Fallido</option>
                </flux:select>

                <flux:select wire:model.live="planFilter">
                    <option value="">Todos los planes</option>
                    <option value="{{ \App\Models\Tenant::PLAN_BASIC }}">Basic</option>
                    <option value="{{ \App\Models\Tenant::PLAN_PRO }}">Pro</option>
                    <option value="{{ \App\Models\Tenant::PLAN_ENTERPRISE }}">Enterprise</option>
                </flux:select>

                <flux:select wire:model.live="perPage">
                    <option value="10">10 por página</option>
                    <option value="25">25 por página</option>
                    <option value="50">50 por página</option>
                </flux:select>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-zinc-200/80 px-5 py-3 text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                <span>Mostrando {{ $tenants->count() }} de {{ $tenants->total() }} tenants</span>
                <span>Acceso interno compartido en un solo dominio</span>
            </div>

            @if ($tenants->isEmpty())
                <div class="flex flex-col items-center justify-center gap-3 px-6 py-16 text-center">
                    <div class="flex size-14 items-center justify-center rounded-2xl bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-300">
                        <flux:icon.building-office-2 class="size-7" />
                    </div>

                    <div class="space-y-1">
                        <flux:heading size="lg">No hay tenants para mostrar</flux:heading>
                        <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                            Crea el primer tenant o limpia los filtros para intentar de nuevo.
                        </flux:text>
                    </div>

                    <flux:button variant="primary" icon="plus" wire:click="openCreateModal">
                        Nuevo tenant
                    </flux:button>
                </div>
            @else
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Negocio</flux:table.column>
                        <flux:table.column>Reservas</flux:table.column>
                        <flux:table.column>Dueño</flux:table.column>
                        <flux:table.column>Plan</flux:table.column>
                        <flux:table.column>Estado</flux:table.column>
                        <flux:table.column>Provisioning</flux:table.column>
                        <flux:table.column class="min-w-48 text-right">Opciones</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach ($tenants as $tenant)
                            <flux:table.row :key="$tenant->id">
                                <flux:table.cell>
                                    <div class="grid gap-1">
                                        <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $tenant->name }}</span>
                                        <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ $tenant->slug }}</span>
                                    </div>
                                </flux:table.cell>

                                <flux:table.cell>
                                    /negocios/{{ $tenant->slug }}/reservas
                                </flux:table.cell>

                                <flux:table.cell>
                                    <div class="grid gap-1">
                                        <span>{{ $tenant->owner_name }}</span>
                                        <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ $tenant->owner_email }}</span>
                                    </div>
                                </flux:table.cell>

                                <flux:table.cell>
                                    <flux:badge>{{ ucfirst($tenant->plan) }}</flux:badge>
                                </flux:table.cell>

                                <flux:table.cell>
                                    @if ($tenant->status === \App\Models\Tenant::STATUS_ACTIVE)
                                        <flux:badge color="emerald">Activo</flux:badge>
                                    @elseif ($tenant->status === \App\Models\Tenant::STATUS_PENDING)
                                        <flux:badge color="amber">Pendiente</flux:badge>
                                    @elseif ($tenant->status === \App\Models\Tenant::STATUS_FAILED)
                                        <flux:badge color="red">Fallido</flux:badge>
                                    @else
                                        <flux:badge>Suspendido</flux:badge>
                                    @endif
                                </flux:table.cell>

                                <flux:table.cell>
                                    @if ($tenant->provisioned_at)
                                        <span class="text-sm text-zinc-600 dark:text-zinc-300">
                                            {{ $tenant->provisioned_at->diffForHumans() }}
                                        </span>
                                    @elseif ($tenant->provisioning_error)
                                        <flux:tooltip :content="$tenant->provisioning_error">
                                            <span class="cursor-help text-sm text-red-600 dark:text-red-400">Ver error</span>
                                        </flux:tooltip>
                                    @else
                                        <span class="text-sm text-zinc-500 dark:text-zinc-400">Sin completar</span>
                                    @endif
                                </flux:table.cell>

                                <flux:table.cell>
                                    <div class="flex items-center justify-end gap-2">
                                        <flux:button
                                            size="sm"
                                            variant="ghost"
                                            icon="arrow-top-right-on-square"
                                            :href="route('reservas.index', ['tenant' => $tenant->slug])"
                                            target="_blank"
                                        >
                                            Reservas
                                        </flux:button>

                                        @if ($tenant->status === \App\Models\Tenant::STATUS_ACTIVE)
                                            <flux:button size="sm" variant="ghost" icon="pause-circle" wire:click="confirmSuspend('{{ $tenant->id }}')">
                                                Suspender
                                            </flux:button>
                                        @elseif ($tenant->status === \App\Models\Tenant::STATUS_SUSPENDED)
                                            <flux:button size="sm" variant="ghost" icon="play-circle" wire:click="activate('{{ $tenant->id }}')">
                                                Activar
                                            </flux:button>
                                        @endif
                                    </div>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>

                <div class="border-t border-zinc-200/80 px-5 py-4 dark:border-zinc-700">
                    <flux:pagination :paginator="$tenants" />
                </div>
            @endif
        </flux:card>
    </div>

    <flux:modal
        name="create-tenant"
        wire:close="closeCreateModal"
        wire:cancel="closeCreateModal"
        class="w-full max-w-3xl"
    >
        <form wire:submit="save" class="space-y-6">
            <div>
                <flux:heading size="lg">Nuevo tenant</flux:heading>
                <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                    Se creará el tenant, su usuario dueño inicial y el enlace público de reservas con slug.
                </flux:text>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <flux:input wire:model.live.debounce.300ms="form.name" label="Nombre del negocio *" type="text" required />
                <flux:input wire:model.live.debounce.300ms="form.slug" label="Slug del negocio *" type="text" required />

                <div class="md:col-span-2">
                    <flux:callout color="sky" icon="globe-alt">
                        <flux:callout.text>
                            URL pública de reservas: <strong>{{ $this->previewBookingUrl }}</strong>
                        </flux:callout.text>
                    </flux:callout>
                </div>

                <flux:input wire:model="form.owner_name" label="Nombre del dueño *" type="text" required />
                <flux:input wire:model="form.owner_email" label="Correo del dueño *" type="email" required />
                <flux:input wire:model="form.owner_password" label="Contraseña inicial *" type="password" viewable required />
                <flux:input wire:model="form.owner_password_confirmation" label="Confirmar contraseña *" type="password" viewable required />

                <flux:select wire:model="form.plan" label="Plan *">
                    <option value="{{ \App\Models\Tenant::PLAN_BASIC }}">Basic</option>
                    <option value="{{ \App\Models\Tenant::PLAN_PRO }}">Pro</option>
                    <option value="{{ \App\Models\Tenant::PLAN_ENTERPRISE }}">Enterprise</option>
                </flux:select>

                <flux:select wire:model="form.status" label="Estado inicial *">
                    <option value="{{ \App\Models\Tenant::STATUS_ACTIVE }}">Activo</option>
                    <option value="{{ \App\Models\Tenant::STATUS_PENDING }}">Pendiente</option>
                </flux:select>
            </div>

            @error('form.slug')
                <flux:callout color="red" icon="exclamation-triangle">
                    <flux:callout.text>{{ $message }}</flux:callout.text>
                </flux:callout>
            @enderror

            <div class="flex flex-col-reverse gap-3 border-t border-zinc-200/80 pt-4 dark:border-zinc-700 sm:flex-row sm:items-center sm:justify-end">
                <flux:modal.close>
                    <flux:button variant="ghost" type="button" wire:click="closeCreateModal">
                        Cancelar
                    </flux:button>
                </flux:modal.close>

                <flux:button variant="primary" type="submit">
                    Crear tenant
                </flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal
        name="suspend-tenant"
        wire:close="closeSuspendModal"
        wire:cancel="closeSuspendModal"
        class="w-full max-w-lg"
    >
        <div class="space-y-5">
            <div>
                <flux:heading size="lg">Suspender tenant</flux:heading>
                <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                    @if ($tenantPendingSuspension)
                        El tenant {{ $tenantPendingSuspension->name }} dejará de operar hasta ser reactivado.
                    @else
                        El tenant seleccionado dejará de operar hasta ser reactivado.
                    @endif
                </flux:text>
            </div>

            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <flux:modal.close>
                    <flux:button variant="ghost" type="button" wire:click="closeSuspendModal">
                        Cancelar
                    </flux:button>
                </flux:modal.close>

                <flux:button variant="danger" type="button" wire:click="suspend">
                    Suspender tenant
                </flux:button>
            </div>
        </div>
    </flux:modal>
</section>
