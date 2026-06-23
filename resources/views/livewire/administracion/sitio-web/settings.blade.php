<section class="w-full px-4 py-6 sm:px-6 lg:px-8">
    <div class="mx-auto flex w-full max-w-6xl flex-col gap-6">
        <div class="grid gap-4 xl:grid-cols-[minmax(0,1.5fr)_minmax(280px,1fr)] xl:items-end">
            <div class="min-w-0">
                <flux:badge color="sky" size="sm" inset="left">Administracion</flux:badge>
                <flux:heading size="xl" level="1" class="mt-3">Sitio web</flux:heading>
                <flux:subheading size="lg" class="mt-2">
                    Configura la identidad de marca, la activacion del sitio y el enlace publico de reservas.
                </flux:subheading>
            </div>

            <div class="rounded-3xl border border-zinc-200/80 bg-white px-5 py-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <flux:text class="text-xs uppercase tracking-[0.22em] text-zinc-500 dark:text-zinc-400">Enlace publico</flux:text>
                <flux:heading size="base" class="mt-2 break-all">{{ $this->bookingUrl }}</flux:heading>
                <flux:text class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">
                    Comparte este enlace cuando el sitio este activo.
                </flux:text>
            </div>
        </div>

        <form wire:submit="save" class="grid gap-6 xl:grid-cols-[minmax(0,1.6fr)_minmax(320px,1fr)]">
            <div class="space-y-6">
                <div class="rounded-3xl border border-zinc-200/80 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="mb-4">
                        <flux:heading size="base">Marca y portada</flux:heading>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <flux:input wire:model="form.site_name" label="Nombre del sitio *" type="text" required />
                        <flux:input wire:model="form.tagline" label="Tagline" type="text" />
                        <flux:input wire:model="form.logo" label="Logo" type="file" accept="image/*" />
                        <flux:input wire:model="form.hero_image" label="Imagen principal" type="file" accept="image/*" />

                        <div class="md:col-span-2">
                            <flux:textarea wire:model="form.description" label="Descripcion" rows="4" />
                        </div>

                        <div>
                            <flux:label>Color principal *</flux:label>
                            <div class="mt-2 flex items-center gap-3">
                                <input wire:model.live="form.primary_color" type="color" class="h-12 w-16 rounded-2xl border border-zinc-200 bg-transparent p-1 dark:border-zinc-700">
                                <flux:input wire:model.live="form.primary_color" type="text" />
                            </div>
                        </div>

                        <flux:input wire:model="form.booking_button_label" label="Texto del boton *" type="text" required />

                        <div class="md:col-span-2">
                            <flux:textarea wire:model="form.booking_intro" label="Texto de introduccion de reserva" rows="3" />
                        </div>
                    </div>
                </div>

                <div class="rounded-3xl border border-zinc-200/80 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="mb-4">
                        <flux:heading size="base">Contacto y redes</flux:heading>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <flux:input wire:model="form.contact_phone" label="Telefono de contacto" type="text" />
                        <flux:input wire:model="form.contact_email" label="Correo de contacto" type="email" />
                        <flux:input wire:model="form.whatsapp_phone" label="WhatsApp" type="text" />
                        <flux:input wire:model="form.instagram_url" label="Instagram" type="url" />
                        <flux:input wire:model="form.facebook_url" label="Facebook" type="url" />
                        <flux:input wire:model="form.tiktok_url" label="TikTok" type="url" />
                    </div>
                </div>

                <div class="flex items-center justify-end">
                    <flux:button variant="primary" type="submit">
                        Guardar configuracion
                    </flux:button>
                </div>
            </div>

            <div class="space-y-6">
                <div class="rounded-3xl border border-zinc-200/80 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="mb-4">
                        <flux:heading size="base">Estado del sitio</flux:heading>
                    </div>

                    <div class="space-y-4">
                        <flux:switch
                            wire:model.live="form.is_active"
                            label="Sitio publico activo"
                            description="Si esta apagado, la ruta publica de reservas devolvera 404."
                            align="left"
                        />

                        <div class="rounded-2xl border border-zinc-200/70 bg-zinc-50 px-4 py-4 dark:border-zinc-700 dark:bg-zinc-950/60">
                            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                                Locales online disponibles:
                                {{ \App\Models\Location::query()->where('is_active', true)->where('accepts_online_bookings', true)->whereNotNull('branch_id')->count() }}
                            </flux:text>
                        </div>
                    </div>
                </div>

                <div class="rounded-3xl border border-zinc-200/80 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
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
                                    <div class="text-lg font-semibold">{{ $form->site_name ?: 'Trend Belleza' }}</div>
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
        </form>
    </div>
</section>
