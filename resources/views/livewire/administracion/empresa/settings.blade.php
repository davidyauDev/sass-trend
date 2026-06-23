<section class="w-full px-4 py-6 sm:px-6 lg:px-8">
    <div class="mx-auto flex w-full max-w-6xl flex-col gap-6">
        <div class="grid gap-4 xl:grid-cols-[minmax(0,1.5fr)_minmax(280px,1fr)] xl:items-end">
            <div class="min-w-0">
                <flux:badge color="sky" size="sm" inset="left">Configuración</flux:badge>
                <flux:heading size="xl" level="1" class="mt-3">Empresa</flux:heading>
                <flux:subheading size="lg" class="mt-2">
                    Centraliza el nombre, logo, colores, moneda y redes sociales que usarán las webs de tus locales.
                </flux:subheading>
            </div>

            <div class="rounded-3xl border border-zinc-200/80 bg-white px-5 py-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <flux:text class="text-xs uppercase tracking-[0.22em] text-zinc-500 dark:text-zinc-400">Sitio público</flux:text>
                        <flux:heading size="base" class="mt-2 break-all">{{ $this->bookingUrl }}</flux:heading>
                    </div>

                    <div class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300">
                        {{ $this->settings->is_active ? 'Activo' : 'Inactivo' }}
                    </div>
                </div>

                <div class="mt-4 flex flex-wrap items-center gap-2">
                    <flux:button
                        type="button"
                        variant="ghost"
                        icon="clipboard"
                        x-data
                        x-on:click="navigator.clipboard.writeText(@js($this->bookingUrl))"
                    >
                        Copia tu link
                    </flux:button>

                    <a
                        href="{{ $this->bookingUrl }}"
                        target="_blank"
                        rel="noreferrer"
                        class="inline-flex items-center justify-center gap-2 rounded-full border border-zinc-200 px-4 py-2 text-sm font-medium text-zinc-700 transition hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800"
                    >
                        Ver sitio
                    </a>
                </div>
            </div>
        </div>

        <form wire:submit="save" class="grid gap-6 xl:grid-cols-[minmax(0,1.6fr)_minmax(320px,1fr)]">
            <div class="space-y-6">
                <div class="rounded-3xl border border-zinc-200/80 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="mb-4">
                        <flux:heading size="base">Empresa</flux:heading>
                        <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                            Esta información se reutiliza en las webs de tus locales y en la reserva pública.
                        </flux:text>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <flux:input wire:model="form.site_name" label="Nombre de tu empresa *" type="text" required />
                        <flux:input wire:model="form.tagline" label="Descripción corta" type="text" />

                        <div class="md:col-span-2">
                            <flux:textarea wire:model="form.description" label="Descripción" rows="4" />
                        </div>

                        <div>
                            <flux:input wire:model="form.logo" label="Tu logo" type="file" accept="image/*" />
                            <flux:text class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                                Recomendado: fondo transparente o cuadrado.
                            </flux:text>
                        </div>

                        <div>
                            <flux:input wire:model="form.hero_image" label="Portada para tu sitio web" type="file" accept="image/*" />
                            <flux:text class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                                Recomendado: 820x360px como mínimo.
                            </flux:text>
                        </div>

                        <flux:input wire:model="form.contact_phone" label="Teléfono de contacto" type="text" />
                        <flux:input wire:model="form.contact_email" label="Correo de contacto" type="email" />
                        <flux:input wire:model="form.whatsapp_phone" label="WhatsApp" type="text" />
                        <flux:input wire:model="form.booking_button_label" label="Texto del botón" type="text" required />

                        <div class="md:col-span-2">
                            <flux:textarea wire:model="form.booking_intro" label="Texto de introducción de reserva" rows="3" />
                        </div>

                        <div class="md:col-span-2">
                            <flux:label>Símbolo de moneda *</flux:label>
                            <div class="mt-2 flex flex-wrap gap-3">
                                <label class="flex items-center gap-2 rounded-2xl border border-zinc-200 px-4 py-3 dark:border-zinc-700">
                                    <input wire:model.live="form.currency_symbol" type="radio" value="$" class="border-zinc-300 text-zinc-900 focus:ring-zinc-400 dark:border-zinc-600">
                                    <span class="text-sm">$</span>
                                </label>

                                <label class="flex items-center gap-2 rounded-2xl border border-zinc-200 px-4 py-3 dark:border-zinc-700">
                                    <input wire:model.live="form.currency_symbol" type="radio" value="S/" class="border-zinc-300 text-zinc-900 focus:ring-zinc-400 dark:border-zinc-600">
                                    <span class="text-sm">S/</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rounded-3xl border border-zinc-200/80 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="mb-4">
                        <flux:heading size="base">Personalización</flux:heading>
                        <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                            Configura el color principal que verán tus clientes en el sitio web y reservas.
                        </flux:text>
                    </div>

                    <div class="grid gap-4 md:grid-cols-[minmax(0,1fr)_auto] md:items-end">
                        <div>
                            <flux:label>Color principal del sitio web *</flux:label>
                            <div class="mt-2 flex items-center gap-3">
                                <input wire:model.live="form.primary_color" type="color" class="h-12 w-16 rounded-2xl border border-zinc-200 bg-transparent p-1 dark:border-zinc-700">
                                <flux:input wire:model.live="form.primary_color" type="text" />
                            </div>
                        </div>

                        <flux:switch
                            wire:model.live="form.is_active"
                            label="Sitio público activo"
                            description="Si lo desactivas, el enlace público quedará disponible solo cuando lo vuelvas a activar."
                            align="left"
                        />
                    </div>

                    <div class="mt-5 overflow-hidden rounded-[2rem] border border-zinc-200/70 dark:border-zinc-700">
                        <div
                            class="space-y-4 p-5 text-white"
                            style="background: linear-gradient(135deg, {{ $form->primary_color }} 0%, #111827 100%);"
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

                                <div class="min-w-0">
                                    <div class="text-lg font-semibold">{{ $form->site_name ?: 'Trend Belleza' }}</div>
                                    <div class="truncate text-sm text-white/75">{{ $form->tagline ?: 'Reserva tus servicios en linea' }}</div>
                                </div>
                            </div>

                            <div class="grid gap-3 rounded-3xl bg-white/10 p-4 backdrop-blur sm:grid-cols-2">
                                <div>
                                    <div class="text-xs uppercase tracking-[0.22em] text-white/60">Moneda</div>
                                    <div class="mt-1 text-sm font-semibold">{{ $form->currency_symbol }}</div>
                                </div>
                                <div>
                                    <div class="text-xs uppercase tracking-[0.22em] text-white/60">Locales online</div>
                                    <div class="mt-1 text-sm font-semibold">{{ $this->onlineLocationsCount }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rounded-3xl border border-zinc-200/80 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="mb-4">
                        <flux:heading size="base">Redes sociales</flux:heading>
                        <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                            Estas direcciones también se reutilizan en las webs de tus locales.
                        </flux:text>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <flux:input wire:model="form.website_url" label="Sitio web" type="url" placeholder="https://www.tuempresa.com" />
                        <flux:input wire:model="form.instagram_url" label="Instagram" type="url" placeholder="https://www.instagram.com/..." />
                        <flux:input wire:model="form.facebook_url" label="Facebook" type="url" placeholder="https://www.facebook.com/..." />
                        <flux:input wire:model="form.tiktok_url" label="TikTok" type="url" placeholder="https://www.tiktok.com/@..." />
                        <flux:input wire:model="form.youtube_url" label="YouTube" type="url" placeholder="https://www.youtube.com/@..." class="md:col-span-2" />
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <div class="rounded-3xl border border-zinc-200/80 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="mb-4">
                        <flux:heading size="base">Vista previa</flux:heading>
                        <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                            Así se verá la marca en el encabezado de tus páginas públicas.
                        </flux:text>
                    </div>

                    <div class="overflow-hidden rounded-[2rem] border border-zinc-200/70 dark:border-zinc-700">
                        <div
                            class="space-y-4 p-5 text-white"
                            style="background: linear-gradient(135deg, {{ $form->primary_color }} 0%, #1f2937 100%);"
                        >
                            <div class="flex items-center gap-3">
                                @if ($form->logo)
                                    <img src="{{ $form->logo->temporaryUrl() }}" alt="Logo temporal" class="h-14 w-14 rounded-[1.5rem] object-cover ring-1 ring-white/20">
                                @elseif ($form->existingLogoPath)
                                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($form->existingLogoPath) }}" alt="Logo actual" class="h-14 w-14 rounded-[1.5rem] object-cover ring-1 ring-white/20">
                                @else
                                    <div class="flex h-14 w-14 items-center justify-center rounded-[1.5rem] bg-white/15 text-lg font-semibold">
                                        {{ \Illuminate\Support\Str::of($form->site_name ?: 'TB')->substr(0, 2)->upper() }}
                                    </div>
                                @endif

                                <div>
                                    <div class="text-lg font-semibold">{{ $form->site_name ?: 'Trend Belleza' }}</div>
                                    <div class="text-sm text-white/75">{{ $form->tagline ?: 'Reserva tus servicios en linea' }}</div>
                                </div>
                            </div>

                            <div class="rounded-3xl bg-white/10 p-4 backdrop-blur">
                                <div class="text-sm text-white/70">
                                    {{ $form->description ?: 'Explora nuestros servicios, elige a tu profesional y agenda en minutos.' }}
                                </div>
                                <div class="mt-4 inline-flex rounded-full bg-white px-4 py-2 text-sm font-semibold text-zinc-900">
                                    {{ $form->booking_button_label ?: 'Reservar ahora' }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rounded-3xl border border-zinc-200/80 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="mb-4">
                        <flux:heading size="base">Enlace de reservas</flux:heading>
                    </div>

                    <div class="space-y-3">
                        <flux:text class="break-all text-sm text-zinc-500 dark:text-zinc-400">
                            {{ $this->bookingUrl }}
                        </flux:text>

                        <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                            Comparte este enlace cuando tu sitio esté activo.
                        </flux:text>
                    </div>
                </div>

                <div class="flex flex-col-reverse gap-3 border-t border-zinc-200/80 pt-4 dark:border-zinc-700 sm:flex-row sm:items-center sm:justify-end">
                    <flux:button variant="primary" type="submit">
                        Guardar cambios
                    </flux:button>
                </div>
            </div>
        </form>
    </div>
</section>
