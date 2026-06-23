@php
    $settings = $this->settings;
    $confirmedAppointment = $this->confirmedAppointment;
@endphp

<section class="min-h-screen">
    <div
        class="relative overflow-hidden border-b border-white/50"
        style="background: linear-gradient(135deg, {{ $settings->primary_color }} 0%, #111827 100%);"
    >
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,_rgba(255,255,255,0.24),_transparent_28%)]"></div>

        <div class="relative mx-auto flex w-full max-w-7xl flex-col gap-10 px-4 py-10 sm:px-6 lg:px-8 lg:flex-row lg:items-center lg:justify-between">
            <div class="max-w-2xl text-white">
                <div class="flex items-center gap-4">
                    @if ($settings->logoUrl())
                        <img src="{{ $settings->logoUrl() }}" alt="{{ $settings->site_name }}" class="h-16 w-16 rounded-[1.75rem] object-cover ring-1 ring-white/20">
                    @else
                        <div class="flex h-16 w-16 items-center justify-center rounded-[1.75rem] bg-white/10 text-2xl font-semibold">
                            {{ \Illuminate\Support\Str::of($settings->site_name)->substr(0, 2)->upper() }}
                        </div>
                    @endif

                    <div>
                        <div class="text-sm uppercase tracking-[0.3em] text-white/60">Reserva online</div>
                        <h1 class="text-3xl font-semibold tracking-tight sm:text-5xl">{{ $settings->site_name }}</h1>
                    </div>
                </div>

                <p class="mt-5 text-lg text-white/80">
                    {{ $settings->description ?: 'Elige el local, servicio, profesional y horario ideal para tu proxima cita.' }}
                </p>

                <div class="mt-6 flex flex-wrap items-center gap-3 text-sm text-white/75">
                    @if ($settings->contact_phone)
                        <span>{{ $settings->contact_phone }}</span>
                    @endif
                    @if ($settings->contact_email)
                        <span>{{ $settings->contact_email }}</span>
                    @endif
                    @if ($settings->whatsapp_phone)
                        <span>WhatsApp: {{ $settings->whatsapp_phone }}</span>
                    @endif
                </div>
            </div>

            <div class="w-full max-w-xl rounded-[2rem] bg-white/96 p-5 shadow-2xl ring-1 ring-black/5 backdrop-blur dark:bg-zinc-900/95">
                <div class="mb-5 flex items-center justify-between gap-3">
                    <div>
                        <div class="text-xs uppercase tracking-[0.22em] text-zinc-500 dark:text-zinc-400">Reservas</div>
                        <h2 class="mt-1 text-2xl font-semibold text-zinc-900 dark:text-zinc-50">{{ $settings->booking_button_label }}</h2>
                    </div>

                    <flux:badge :color="$confirmedAppointment ? 'emerald' : 'sky'">
                        {{ $confirmedAppointment ? 'Confirmada' : 'Disponible' }}
                    </flux:badge>
                </div>

                @if ($confirmedAppointment)
                    <div class="space-y-4 rounded-[1.75rem] border border-emerald-200 bg-emerald-50 p-5 text-emerald-950 dark:border-emerald-900/50 dark:bg-emerald-950/30 dark:text-emerald-100">
                        <div>
                            <h3 class="text-lg font-semibold">Reserva registrada</h3>
                            <p class="mt-1 text-sm">
                                Codigo {{ $confirmedAppointment->reference_code }} para {{ $confirmedAppointment->client->fullName() }}.
                            </p>
                        </div>

                        <div class="grid gap-3 text-sm sm:grid-cols-2">
                            <div>{{ $confirmedAppointment->branch?->name }}</div>
                            <div>{{ $confirmedAppointment->service->name }}</div>
                            <div>{{ $confirmedAppointment->professional?->fullName() ?: 'Sin profesional' }}</div>
                            <div>{{ $confirmedAppointment->starts_at->format('d/m/Y H:i') }}</div>
                        </div>
                    </div>
                @endif

                <form wire:submit="submit" class="mt-6 space-y-6">
                    <div class="space-y-3">
                        <flux:heading size="sm">1. Elige un local</flux:heading>
                        <div class="grid gap-3">
                            @foreach ($this->locations as $location)
                                <button
                                    type="button"
                                    wire:click="$set('location_id', {{ $location->id }})"
                                    class="{{ $location_id === $location->id ? 'border-zinc-900 bg-zinc-900 text-white dark:border-zinc-100 dark:bg-zinc-100 dark:text-zinc-900' : 'border-zinc-200 bg-white text-zinc-900 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100' }} rounded-[1.4rem] border px-4 py-4 text-left transition"
                                >
                                    <div class="font-medium">{{ $location->name }}</div>
                                    <div class="mt-1 text-sm opacity-70">{{ $location->address }}</div>
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <div class="space-y-3">
                        <flux:heading size="sm">2. Elige un servicio</flux:heading>
                        <flux:select wire:model.live="service_id" :disabled="$location_id === null">
                            <option value="">Selecciona un servicio</option>
                            @foreach ($this->services as $service)
                                <option value="{{ $service->id }}">
                                    {{ $service->name }} - {{ $settings->currency_symbol }} {{ number_format((float) $service->price, 2) }}
                                </option>
                            @endforeach
                        </flux:select>
                    </div>

                    <div class="space-y-3">
                        <flux:heading size="sm">3. Elige un profesional</flux:heading>
                        <div class="grid gap-3 md:grid-cols-2">
                            @forelse ($this->professionals as $professional)
                                <button
                                    type="button"
                                    wire:click="$set('professional_id', {{ $professional->id }})"
                                    class="{{ $professional_id === $professional->id ? 'border-zinc-900 bg-zinc-900 text-white dark:border-zinc-100 dark:bg-zinc-100 dark:text-zinc-900' : 'border-zinc-200 bg-white text-zinc-900 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100' }} flex items-center gap-3 rounded-[1.4rem] border px-4 py-4 text-left transition"
                                    :disabled="$service_id === null"
                                >
                                    @if ($professional->photoUrl())
                                        <img src="{{ $professional->photoUrl() }}" alt="{{ $professional->displayName() }}" class="size-12 rounded-2xl object-cover">
                                    @else
                                        <div class="flex size-12 items-center justify-center rounded-2xl bg-zinc-100 text-sm font-semibold text-zinc-500 dark:bg-zinc-800 dark:text-zinc-300">
                                            {{ $professional->initials() }}
                                        </div>
                                    @endif

                                    <div>
                                        <div class="font-medium">{{ $professional->displayName() }}</div>
                                        <div class="text-sm opacity-70">{{ $professional->locations->pluck('name')->join(', ') }}</div>
                                    </div>
                                </button>
                            @empty
                                <div class="rounded-[1.4rem] border border-dashed border-zinc-300 px-4 py-5 text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                                    Elige primero un local y un servicio para ver profesionales disponibles.
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <div class="space-y-3">
                        <flux:heading size="sm">4. Fecha y hora</flux:heading>
                        <flux:input wire:model.live="selected_date" type="date" min="{{ now()->toDateString() }}" :disabled="$professional_id === null" />

                        <div class="grid gap-2 sm:grid-cols-2">
                            @forelse ($this->availableSlots as $slot)
                                <button
                                    type="button"
                                    wire:click="selectSlot('{{ $slot['starts_at'] }}')"
                                    class="{{ $selected_starts_at === $slot['starts_at'] ? 'border-zinc-900 bg-zinc-900 text-white dark:border-zinc-100 dark:bg-zinc-100 dark:text-zinc-900' : 'border-zinc-200 bg-white text-zinc-900 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100' }} rounded-2xl border px-4 py-3 text-sm font-medium transition"
                                >
                                    {{ $slot['label'] }}
                                </button>
                            @empty
                                <div class="rounded-[1.4rem] border border-dashed border-zinc-300 px-4 py-5 text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-400 sm:col-span-2">
                                    No hay horarios disponibles para la fecha elegida.
                                </div>
                            @endforelse
                        </div>

                        @error('starts_at')
                            <div class="text-sm text-red-600">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="space-y-4 rounded-[1.75rem] border border-zinc-200/70 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-950/70">
                        <flux:heading size="sm">5. Tus datos</flux:heading>

                        <div class="grid gap-4 md:grid-cols-2">
                            <flux:input wire:model="first_name" label="Nombre *" type="text" required />
                            <flux:input wire:model="last_name" label="Apellido *" type="text" required />
                            <flux:input wire:model="email" label="Correo" type="email" />
                            <flux:input wire:model="phone" label="Telefono *" type="text" required />
                        </div>

                        <flux:textarea wire:model="notes" label="Notas para la reserva" rows="3" />
                    </div>

                    <div class="flex items-center justify-end">
                        <flux:button variant="primary" type="submit" :disabled="$selected_starts_at === ''">
                            Confirmar reserva
                        </flux:button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>
