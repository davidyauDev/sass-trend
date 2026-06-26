<section class="w-full px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex w-full flex-col gap-6">
        <div class="grid gap-4 xl:grid-cols-[minmax(0,1.6fr)_minmax(260px,1fr)] xl:items-end">
            <div class="min-w-0">
                <flux:badge color="sky" size="sm" inset="left">Administración</flux:badge>
                <flux:heading size="xl" level="1" class="mt-3">Locales</flux:heading>
                <flux:subheading size="lg" class="mt-2">
                    Gestiona tus sedes, horarios de atención y configuración de reservas online desde una sola vista.
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

                <flux:button
                    variant="primary"
                    icon="plus"
                    wire:click="openCreateModal"
                    :disabled="! $this->canCreateLocations"
                >
                    Nuevo local
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
                        placeholder="Buscar por nombre, dirección, teléfono o correo"
                    />
                </div>

                <div class="min-w-32 xl:justify-self-end">
                    <flux:select wire:model.live="perPage">
                        <option value="10">10 por página</option>
                        <option value="25">25 por página</option>
                        <option value="50">50 por página</option>
                    </flux:select>
                </div>
            </div>

            <div class="flex items-center justify-between border-b border-zinc-200/80 px-5 py-3 text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                <span>Mostrando {{ $locations->count() }} de {{ $locations->total() }} locales</span>

                @if ($search !== '')
                    <span class="hidden sm:inline">Filtro activo: "{{ $search }}"</span>
                @endif
            </div>

            @if ($locations->isEmpty())
                <div class="flex flex-col items-center justify-center gap-3 px-6 py-16 text-center">
                    <div class="flex size-14 items-center justify-center rounded-2xl bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-300">
                        <flux:icon.building-storefront class="size-7" />
                    </div>

                    <div class="space-y-1">
                        <flux:heading size="lg">No hay locales para mostrar</flux:heading>
                        <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                            Crea tu primer local o ajusta los filtros para intentar de nuevo.
                        </flux:text>
                    </div>

                    <div class="flex flex-wrap items-center justify-center gap-2">
                        <flux:button
                            variant="primary"
                            icon="plus"
                            wire:click="openCreateModal"
                            :disabled="! $this->canCreateLocations"
                        >
                            Nuevo local
                        </flux:button>

                        <flux:button variant="ghost" icon="arrow-path" wire:click="clearFilters">
                            Limpiar filtros
                        </flux:button>
                    </div>
                </div>
            @else
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column class="w-[18%]">Nombre del local</flux:table.column>
                        <flux:table.column class="w-[14%]">Sede agenda</flux:table.column>
                        <flux:table.column class="w-[24%]">Dirección</flux:table.column>
                        <flux:table.column class="w-[12%]">Teléfono</flux:table.column>
                        <flux:table.column class="w-[16%]">Email</flux:table.column>
                        <flux:table.column class="w-[14%]">Zona horaria</flux:table.column>
                        <flux:table.column class="w-[7%]">Estado</flux:table.column>
                        <flux:table.column class="w-[9%]">Reservas online</flux:table.column>
                        <flux:table.column class="w-[10%] min-w-44 text-right">Opciones</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach ($locations as $location)
                            <flux:table.row :key="$location->id">
                                <flux:table.cell class="font-medium text-zinc-900 dark:text-zinc-100">
                                    {{ $location->name }}
                                </flux:table.cell>

                                <flux:table.cell>
                                    {{ $location->branch?->name ?: 'Sin vínculo' }}
                                </flux:table.cell>

                                <flux:table.cell>
                                    {{ $location->address }}
                                </flux:table.cell>

                                <flux:table.cell>
                                    {{ $location->phone ?: 'Sin teléfono' }}
                                </flux:table.cell>

                                <flux:table.cell>
                                    {{ $location->email ?: 'Sin correo' }}
                                </flux:table.cell>

                                <flux:table.cell>
                                    {{ $location->timezone ?: 'Sin zona horaria' }}
                                </flux:table.cell>

                                <flux:table.cell>
                                    @if ($location->is_active)
                                        <flux:badge color="emerald">Activo</flux:badge>
                                    @else
                                        <flux:badge>Inactivo</flux:badge>
                                    @endif
                                </flux:table.cell>

                                <flux:table.cell>
                                    <flux:badge :color="$location->accepts_online_bookings ? 'sky' : 'amber'">
                                        {{ $location->accepts_online_bookings ? 'Activas' : 'Inactivas' }}
                                    </flux:badge>
                                </flux:table.cell>

                                <flux:table.cell>
                                    <div class="flex items-center justify-end gap-2">
                                        <flux:button size="sm" variant="ghost" icon="pencil-square" wire:click="openEditModal({{ $location->id }})">
                                            Editar
                                        </flux:button>

                                        <flux:button size="sm" variant="danger" icon="trash" wire:click="confirmDelete({{ $location->id }})">
                                            Eliminar
                                        </flux:button>
                                    </div>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>

                <div class="border-t border-zinc-200/80 px-5 py-4 dark:border-zinc-700">
                    <flux:pagination :paginator="$locations" />
                </div>
            @endif
        </flux:card>
    </div>

    <flux:modal
        name="upsert-location"
        wire:close="closeModal"
        wire:cancel="closeModal"
        class="w-full max-w-7xl"
    >
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $isEditing ? 'Editar local' : 'Nuevo local' }}</flux:heading>
                <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                    Completa la información básica, horarios de atención y datos del sitio web del local.
                </flux:text>
            </div>

            <form wire:submit="save" class="space-y-6">
                <div class="rounded-3xl border border-zinc-200/80 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="border-b border-zinc-200/80 px-4 pt-4 dark:border-zinc-700">
                        <div class="flex flex-wrap gap-6">
                            <button
                                type="button"
                                wire:click="$set('modalTab', 'basic')"
                                class="@class([
                                    'inline-flex items-center border-b-2 px-1 pb-3 text-sm font-medium transition',
                                    'border-purple-500 text-purple-600 dark:border-purple-400 dark:text-purple-300' => $modalTab === 'basic',
                                    'border-transparent text-zinc-500 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100' => $modalTab !== 'basic',
                                ])"
                            >
                                Datos básicos
                            </button>

                            <button
                                type="button"
                                wire:click="$set('modalTab', 'schedule')"
                                class="@class([
                                    'inline-flex items-center border-b-2 px-1 pb-3 text-sm font-medium transition',
                                    'border-purple-500 text-purple-600 dark:border-purple-400 dark:text-purple-300' => $modalTab === 'schedule',
                                    'border-transparent text-zinc-500 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100' => $modalTab !== 'schedule',
                                ])"
                            >
                                Horario de atención
                            </button>

                            <button
                                type="button"
                                wire:click="$set('modalTab', 'website')"
                                class="@class([
                                    'inline-flex items-center border-b-2 px-1 pb-3 text-sm font-medium transition',
                                    'border-purple-500 text-purple-600 dark:border-purple-400 dark:text-purple-300' => $modalTab === 'website',
                                    'border-transparent text-zinc-500 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100' => $modalTab !== 'website',
                                ])"
                            >
                                Sitio web
                            </button>
                        </div>
                    </div>

                    <div class="border-t-0 p-4">
                        @if ($modalTab === 'basic')
                            <div class="grid gap-4 md:grid-cols-2">
                                <flux:input wire:model="form.name" label="Nombre del local *" type="text" required />
                                <flux:input wire:model="form.address" label="Dirección *" type="text" required />
                                <flux:input wire:model="form.phone" label="Teléfono" type="text" />
                                <flux:input wire:model="form.email" label="Email" type="email" />

                                <div class="md:col-span-2">
                                    <flux:input
                                        wire:model="form.timezone"
                                        label="Zona horaria"
                                        type="text"
                                        list="timezone-suggestions"
                                        placeholder="America/Lima"
                                    />

                                    <datalist id="timezone-suggestions">
                                        @foreach ($this->timezoneSuggestions as $timezone)
                                            <option value="{{ $timezone }}"></option>
                                        @endforeach
                                    </datalist>

                                    <flux:text class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">
                                        Puedes escribirla manualmente o elegir una sugerencia.
                                    </flux:text>
                                </div>
                            </div>

                            <div class="mt-4 rounded-2xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-700 dark:border-sky-900 dark:bg-sky-950/30 dark:text-sky-300">
                                La sede se creará automáticamente al guardar el local.
                            </div>
                        @elseif ($modalTab === 'schedule')
                            <div class="flex flex-col gap-3 border-b border-zinc-200/80 pb-4 dark:border-zinc-700 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <flux:heading size="base">Horario de inicio y fin de la jornada</flux:heading>
                                    <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                                        Activa los días en que atiendes y configura tus horas laborales.
                                    </flux:text>
                                </div>

                                <flux:button type="button" variant="ghost" icon="document-duplicate" wire:click="copyScheduleToAll">
                                    Copiar en todos
                                </flux:button>
                            </div>

                            <div class="overflow-hidden rounded-3xl border border-zinc-200/80 dark:border-zinc-700">
                                <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
                                    <thead class="bg-zinc-50 dark:bg-zinc-950/50">
                                        <tr class="text-left text-zinc-500 dark:text-zinc-400">
                                            <th class="px-4 py-4 font-medium">Día</th>
                                            <th class="px-4 py-4 font-medium">Estado</th>
                                            <th class="px-4 py-4 font-medium">Inicio de la jornada</th>
                                            <th class="px-4 py-4 font-medium">Fin de la jornada</th>
                                            <th class="px-4 py-4 font-medium"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-900">
                                        @foreach ($form->schedules as $index => $schedule)
                                            @php
                                                $isOpen = (bool) data_get($form->schedules, "{$index}.is_open");
                                                $opensAt = (string) data_get($form->schedules, "{$index}.opens_at", '');
                                                $closesAt = (string) data_get($form->schedules, "{$index}.closes_at", '');
                                            @endphp

                                            <tr>
                                                <td class="px-4 py-4 font-medium text-zinc-900 dark:text-zinc-100">
                                                    {{ $schedule['label'] }}
                                                </td>

                                                <td class="px-4 py-4">
                                                    <flux:switch wire:model.live="form.schedules.{{ $index }}.is_open" label="" align="left" />
                                                </td>

                                                <td class="px-4 py-4">
                                                    @if ($isOpen)
                                                        <flux:input
                                                            wire:model="form.schedules.{{ $index }}.opens_at"
                                                            type="time"
                                                            class="min-w-44"
                                                        />
                                                    @else
                                                        <span class="text-zinc-500 dark:text-zinc-400">Local cerrado</span>
                                                    @endif
                                                </td>

                                                <td class="px-4 py-4">
                                                    @if ($isOpen)
                                                        <flux:input
                                                            wire:model="form.schedules.{{ $index }}.closes_at"
                                                            type="time"
                                                            class="min-w-44"
                                                        />
                                                    @else
                                                        <span class="text-zinc-500 dark:text-zinc-400">Local cerrado</span>
                                                    @endif
                                                </td>

                                                <td class="px-4 py-4 text-right">
                                                    @if ($index === 0)
                                                        <button type="button" wire:click="copyScheduleToAll" class="text-sm font-medium text-sky-700 underline decoration-sky-300 underline-offset-4 dark:text-sky-400">
                                                            Copiar en todos
                                                        </button>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="space-y-6">
                                <div class="rounded-3xl border border-zinc-200/80 p-5 dark:border-zinc-700">
                                    <div class="mb-4">
                                        <flux:heading size="base">Marca y portada</flux:heading>
                                    </div>

                                    <div class="grid gap-4 md:grid-cols-2">
                                        <flux:input wire:model="form.site_name" label="Nombre del sitio" type="text" />
                                        <flux:input wire:model="form.tagline" label="Tagline" type="text" />
                                        <flux:input wire:model="form.logo" label="Logo" type="file" accept="image/*" />
                                        <flux:input wire:model="form.hero_image" label="Imagen principal" type="file" accept="image/*" />

                                        <div class="md:col-span-2">
                                            <flux:textarea wire:model="form.description" label="Descripción" rows="4" />
                                        </div>

                                        <div>
                                            <flux:label>Color principal</flux:label>
                                            <div class="mt-2 flex items-center gap-3">
                                                <input wire:model.live="form.primary_color" type="color" class="h-12 w-16 rounded-2xl border border-zinc-200 bg-transparent p-1 dark:border-zinc-700">
                                                <flux:input wire:model.live="form.primary_color" type="text" />
                                            </div>
                                        </div>

                                        <flux:input wire:model="form.booking_button_label" label="Texto del botón" type="text" />

                                        <div class="md:col-span-2">
                                            <flux:textarea wire:model="form.booking_intro" label="Texto de introducción de reserva" rows="3" />
                                        </div>
                                    </div>
                                </div>

                                <div class="rounded-3xl border border-zinc-200/80 p-5 dark:border-zinc-700">
                                    <div class="mb-4">
                                        <flux:heading size="base">Contacto y redes</flux:heading>
                                    </div>

                                    <div class="grid gap-4 md:grid-cols-2">
                                        <flux:input wire:model="form.contact_phone" label="Teléfono de contacto" type="text" />
                                        <flux:input wire:model="form.contact_email" label="Correo de contacto" type="email" />
                                        <flux:input wire:model="form.whatsapp_phone" label="WhatsApp" type="text" />
                                        <flux:input wire:model="form.instagram_url" label="Instagram" type="url" />
                                        <flux:input wire:model="form.facebook_url" label="Facebook" type="url" />
                                        <flux:input wire:model="form.tiktok_url" label="TikTok" type="url" />
                                    </div>
                                </div>

                                <div class="rounded-3xl border border-zinc-200/80 p-5 dark:border-zinc-700">
                                    <div class="mb-4">
                                        <flux:heading size="base">Estado y reglas</flux:heading>
                                    </div>

                                    <div class="grid gap-4 md:grid-cols-2">
                                        <flux:switch
                                            wire:model.live="form.accepts_online_bookings"
                                            label="Acepta reservas online"
                                            description="Define si este local puede recibir reservas desde el sitio web."
                                            align="left"
                                        />

                                        <flux:switch
                                            wire:model.live="form.is_active"
                                            label="Local activo"
                                            description="Desactiva el local si quieres ocultarlo operativamente sin eliminarlo."
                                            align="left"
                                        />

                                        <flux:input wire:model="form.secondary_phone" label="Teléfono secundario" type="text" />
                                        <div></div>
                                    </div>
                                </div>

                                <div class="rounded-3xl border border-zinc-200/80 p-5 dark:border-zinc-700">
                                    <div class="mb-4">
                                        <flux:heading size="base">Vista previa</flux:heading>
                                    </div>

                                    <div class="overflow-hidden rounded-[2rem] border border-zinc-200/70 dark:border-zinc-700">
                                        <div
                                            class="space-y-4 p-5 text-white"
                                            style="background: linear-gradient(135deg, {{ $form->primary_color }} 0%, #1f2937 100%);"
                                        >
                                            <div class="flex items-center gap-3">
                                                @if ($form->logo)
                                                    <img src="{{ $form->logo->temporaryUrl() }}" alt="Logo temporal" class="h-12 w-12 rounded-2xl object-cover ring-1 ring-white/20">
                                                @elseif ($form->existingLogoPath)
                                                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($form->existingLogoPath) }}" alt="Logo actual" class="h-12 w-12 rounded-2xl object-cover ring-1 ring-white/20">
                                                @else
                                                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white/15 text-lg font-semibold">
                                                        {{ \Illuminate\Support\Str::of($form->site_name ?: 'TB')->substr(0, 2)->upper() }}
                                                    </div>
                                                @endif

                                                <div>
                                                    <div class="text-lg font-semibold">{{ $form->site_name ?: $form->name ?: 'Trend Belleza' }}</div>
                                                    <div class="text-sm text-white/75">{{ $form->tagline ?: 'Reserva tus servicios en linea' }}</div>
                                                </div>
                                            </div>

                                            <div class="rounded-3xl bg-white/10 p-4 backdrop-blur">
                                                <div class="text-sm text-white/70">{{ $form->booking_intro ?: 'Selecciona local, servicio, profesional y horario para confirmar tu reserva.' }}</div>
                                                <div class="mt-4 inline-flex rounded-full bg-white px-4 py-2 text-sm font-semibold text-zinc-900">
                                                    {{ $form->booking_button_label ?: 'Reservar ahora' }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
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
        name="delete-location"
        wire:close="closeDeleteModal"
        wire:cancel="closeDeleteModal"
        class="w-full max-w-lg"
    >
        <div class="space-y-5">
            <div>
                <flux:heading size="lg">Eliminar local</flux:heading>
                <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                    @if ($locationPendingDeletion)
                        Esta acción eliminará a {{ $locationPendingDeletion->name }} y sus horarios de forma permanente.
                    @else
                        Esta acción eliminará el local seleccionado de forma permanente.
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
                    Eliminar local
                </flux:button>
            </div>
        </div>
    </flux:modal>
</section>
