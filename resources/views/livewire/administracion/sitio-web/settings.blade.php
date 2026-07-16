@php
    $steps = [
        1 => ['Esenciales', 'Nombre y contacto'],
        2 => ['Ubicación', 'Local publicado'],
        3 => ['Horarios', 'Atención semanal'],
        4 => ['Imágenes', 'Galería del perfil'],
        5 => ['Características', 'Lo que te hace especial'],
        6 => ['Descripción', 'Presenta tu negocio'],
    ];

    $amenityOptions = [
        'Estacionamiento' => 'P',
        'Cerca al transporte público' => 'T',
        'Wi-Fi' => 'W',
        'Aire acondicionado' => 'A',
        'Baño' => 'B',
        'Acceso para silla de ruedas' => 'R',
        'Pagos con tarjeta' => 'C',
        'Bebidas de cortesía' => 'D',
    ];

    $highlightOptions = [
        'Pet friendly',
        'Solo adultos',
        'Apto para niños',
        'Productos orgánicos',
        'Productos veganos',
        'Atención personalizada',
        'Confirmación inmediata',
        'Negocio liderado por mujeres',
    ];

    $timeOptions = collect(range(0, 47))->map(function (int $slot): string {
        $minutes = $slot * 30;

        return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
    });
@endphp

<div data-profile-settings-root>
@if ($editingSection !== null)
    <section wire:key="profile-editor-{{ $editingSection }}" class="fixed inset-0 z-50 overflow-y-auto bg-white text-zinc-950">
        <div class="sticky top-0 z-10 flex h-16 items-center justify-between gap-4 border-t-2 border-zinc-950 bg-white px-5 sm:px-10">
            <div class="text-base font-semibold">{{ $editingSection === 'location' ? '¿Dónde se encuentra tu negocio?' : '' }}</div>
            <div class="flex items-center gap-2">
            <button type="button" wire:click="closeEditor" class="inline-flex h-10 items-center rounded-full border border-zinc-300 px-5 text-sm font-semibold transition hover:bg-zinc-50">Cerrar</button>
            <button type="button" wire:click="saveSection" class="inline-flex h-10 items-center rounded-full bg-zinc-950 px-5 text-sm font-semibold text-white transition hover:bg-zinc-800">
                <span wire:loading.remove wire:target="saveSection">Guardar</span>
                <span wire:loading wire:target="saveSection">Guardando…</span>
            </button>
            </div>
        </div>

        <div class="mx-auto w-full {{ $editingSection === 'images' ? 'max-w-6xl' : 'max-w-2xl' }} px-5 pb-24 pt-8 sm:pt-12">
            @if ($editingSection === 'essentials')
                <h1 class="text-3xl font-semibold tracking-[-0.04em] sm:text-4xl">Elementos esenciales del lugar</h1>
                <p class="mt-3 max-w-xl text-zinc-500">Añade el nombre con el que te gustaría que te conocieran y cómo pueden ponerse en contacto tus clientes.</p>

                <div class="mt-9 space-y-6">
                    <div>
                        <flux:input wire:model="form.site_name" label="Nombre para mostrar de la ubicación" type="text" required />
                        <p class="mt-1 text-xs text-zinc-500">Nombre público visible para tus clientes en las notificaciones y al reservar en línea.</p>
                    </div>

                    <div>
                        <flux:label>Número de teléfono comercial</flux:label>
                        <p class="mb-2 text-xs text-zinc-500">El número de contacto se proporciona para que los clientes llamen si necesitan ayuda con su cita.</p>
                        <div class="grid grid-cols-[92px_minmax(0,1fr)] gap-2">
                            <div class="flex h-11 items-center justify-between rounded-lg border border-zinc-300 bg-zinc-50 px-4 text-sm text-zinc-500"><span>+51</span><span>⌄</span></div>
                            <input wire:model="form.contact_phone" type="text" class="h-11 rounded-lg border border-zinc-300 bg-white px-4 text-sm outline-none focus:border-zinc-950 focus:ring-1 focus:ring-zinc-950">
                        </div>
                        @error('form.contact_phone') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <flux:input wire:model="form.contact_email" label="Correo electrónico empresarial" type="email" />
                        <p class="mt-1 text-xs text-zinc-500">Elige dónde se envían las respuestas de los clientes cuando responden los correos de citas.</p>
                    </div>
                </div>
            @elseif ($editingSection === 'description')
                <h1 class="text-3xl font-semibold tracking-[-0.04em] sm:text-4xl">Cuéntanos un poco sobre este lugar.</h1>
                <p class="mt-3 max-w-xl text-zinc-500">Las descripciones más efectivas muestran detalles clave sobre su negocio y resaltan lo que hace que su local sea único.</p>

                <div class="mt-9" x-data="{ description: @entangle('form.description').live }">
                    <div class="mb-2 flex items-center justify-between text-sm"><label class="font-semibold">Descripción del lugar</label><span class="text-zinc-500" x-text="(description || '').length + '/1200'"></span></div>
                    <div class="relative">
                        <textarea wire:model.live="form.description" rows="13" maxlength="1200" class="w-full rounded-lg border border-zinc-300 bg-white p-4 pb-16 text-sm leading-6 outline-none focus:border-zinc-950 focus:ring-1 focus:ring-zinc-950"></textarea>
                        <button type="button" class="absolute bottom-4 left-4 inline-flex h-9 items-center gap-2 rounded-full border border-indigo-200 bg-white px-4 text-sm font-medium shadow-[0_0_14px_rgba(99,102,241,0.22)]"><span class="text-indigo-600">✧</span> Mejorar</button>
                    </div>
                    @error('form.description') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    @if (! $errors->has('form.description'))<p class="mt-1 text-xs text-zinc-500">Se requiere un mínimo de 200 caracteres.</p>@endif
                </div>
            @elseif ($editingSection === 'location')
                <h1 class="text-3xl font-semibold tracking-[-0.04em] sm:text-4xl">¿Dónde se encuentra tu negocio?</h1>
                <p class="mt-3 max-w-xl text-zinc-500">¿Dónde pueden encontrarte los clientes? Asegúrate de indicar la dirección correcta.</p>

                <div class="mt-8 space-y-5">
                    @if ($this->locations->count() > 1)
                        <flux:select wire:model.live="form.primary_location_id" label="Local publicado">
                            @foreach ($this->locations as $locationOption)
                                <option value="{{ $locationOption->id }}">{{ $locationOption->name }}</option>
                            @endforeach
                        </flux:select>
                    @endif

                    <div>
                        <flux:label>Dirección de ubicación</flux:label>
                        <div class="relative mt-2">
                            <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-zinc-500">⌖</span>
                            <input wire:model.live.debounce.400ms="form.location_address" type="text" class="h-12 w-full rounded-lg border border-zinc-300 bg-white pl-11 pr-4 text-sm outline-none focus:border-zinc-950 focus:ring-1 focus:ring-zinc-950">
                        </div>
                        @error('form.location_address') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="relative h-[430px] overflow-hidden rounded-lg border border-zinc-200 bg-[#e7ede8]">
                        <div class="absolute inset-0 opacity-70" style="background-image: linear-gradient(28deg, transparent 46%, #b6c2b8 47%, #b6c2b8 50%, transparent 51%), linear-gradient(118deg, transparent 46%, #c7d0c9 47%, #c7d0c9 50%, transparent 51%); background-size: 110px 78px;"></div>
                        <div class="absolute left-1/2 top-[48%] flex size-11 -translate-x-1/2 -translate-y-1/2 items-center justify-center rounded-full bg-zinc-950 text-white shadow-xl ring-8 ring-white/60">⌖</div>
                        <div class="absolute right-4 top-[58%] overflow-hidden rounded-full bg-white shadow"><button type="button" class="block size-11 border-b border-zinc-200 text-2xl">+</button><button type="button" class="block size-11 text-2xl">−</button></div>
                        <div class="absolute inset-x-0 bottom-0 flex items-center justify-between gap-4 border-t border-zinc-200 bg-white p-4">
                            <div class="text-sm"><div class="font-medium">{{ $form->location_address ?: 'Dirección pendiente' }}</div><div class="mt-1 text-zinc-500">Lima, Perú</div></div>
                            @if ($form->location_address)<a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($form->location_address) }}" target="_blank" rel="noreferrer" class="rounded-full border border-zinc-300 px-4 py-2 text-sm font-medium">Editar en mapa</a>@endif
                        </div>
                    </div>
                    <p class="text-sm text-zinc-500">Arrastra el mapa para ajustar la posición del marcador.</p>

                    <div class="pt-5" x-data="{ showInstructions: @js(filled($form->directions)) }">
                        <div class="text-sm font-semibold">Cómo llegar <span class="font-normal text-zinc-500">(Opcional)</span></div>
                        <p class="mt-1 text-sm text-zinc-500">Proporciona instrucciones adicionales para ayudar a tus clientes a encontrar fácilmente la entrada.</p>
                        <button x-show="!showInstructions" type="button" x-on:click="showInstructions = true" class="mt-4 rounded-full border border-zinc-300 px-4 py-2 text-sm font-medium">⊕ Añadir instrucciones</button>
                        <div x-cloak x-show="showInstructions" class="mt-4"><flux:textarea wire:model="form.directions" rows="4" placeholder="Ejemplo: segundo piso, puerta verde, estacionamiento al fondo..." /></div>
                    </div>
                </div>
            @elseif ($editingSection === 'hours')
                <h1 class="text-3xl font-semibold tracking-[-0.04em] sm:text-4xl">Añade tus horarios de apertura</h1>
                <p class="mt-3 max-w-xl text-zinc-500">Informa a tus clientes de tu horario habitual. Se mostrará en el perfil y se usará para calcular las reservas disponibles.</p>

                <div class="mt-8 flex flex-wrap gap-3">
                    @foreach ($dayNames as $day => $dayName)
                        <label class="cursor-pointer">
                            <input type="checkbox" wire:model.live="form.schedule.{{ $day }}.is_open" class="peer sr-only">
                            <span class="flex size-14 items-center justify-center rounded-full bg-zinc-100 text-sm font-semibold text-zinc-500 transition peer-checked:bg-indigo-600 peer-checked:text-white">{{ mb_substr($dayName, 0, 3) }}</span>
                        </label>
                    @endforeach
                </div>

                <div class="mt-9 space-y-3">
                    @foreach ($dayNames as $day => $dayName)
                        <div class="grid items-center gap-3 rounded-lg py-2 sm:grid-cols-[210px_minmax(0,1fr)_28px_minmax(0,1fr)]">
                            <label class="flex cursor-pointer items-center gap-3">
                                <input type="checkbox" wire:model.live="form.schedule.{{ $day }}.is_open" class="size-4 rounded border-zinc-300 text-indigo-600 focus:ring-indigo-500">
                                <span><span class="block text-sm font-semibold">{{ $dayName }}</span><span class="block text-sm {{ $form->schedule[$day]['is_open'] ? 'text-emerald-600' : 'text-zinc-400' }}">{{ $form->schedule[$day]['is_open'] ? 'Abierto' : 'Cerrado' }}</span></span>
                            </label>

                            <select wire:model="form.schedule.{{ $day }}.opens_at" @disabled(! $form->schedule[$day]['is_open']) class="h-11 rounded-lg border border-zinc-300 bg-white px-3 text-sm disabled:bg-zinc-100 disabled:text-zinc-400">
                                @foreach ($timeOptions as $time)<option value="{{ $time }}">{{ $time }}</option>@endforeach
                            </select>
                            <span class="text-center text-sm text-zinc-500">a</span>
                            <select wire:model="form.schedule.{{ $day }}.closes_at" @disabled(! $form->schedule[$day]['is_open']) class="h-11 rounded-lg border border-zinc-300 bg-white px-3 text-sm disabled:bg-zinc-100 disabled:text-zinc-400">
                                @foreach ($timeOptions as $time)<option value="{{ $time }}">{{ $time }}</option>@endforeach
                            </select>
                            @error("form.schedule.{$day}.closes_at") <p class="text-xs text-red-600 sm:col-start-2 sm:col-span-3">{{ $message }}</p> @enderror
                        </div>
                    @endforeach
                </div>
            @else
                <h1 class="text-3xl font-semibold tracking-[-0.04em] sm:text-4xl">Actualizar imágenes del lugar</h1>
                <p class="mt-3 max-w-4xl text-zinc-500">Añade al menos 3 imágenes de tu ubicación. Puedes subir hasta 10 imágenes de alta calidad y modificarlas cuando quieras.</p>

                <label class="mt-8 flex min-h-24 cursor-pointer items-center justify-between gap-4 rounded-xl border border-dashed border-zinc-300 p-3 transition hover:border-indigo-400">
                    <span class="flex-1 rounded-lg bg-zinc-50 px-4 py-4"><span class="block text-sm font-semibold">Arrastra y suelta tus imágenes aquí.</span><span class="mt-1 block text-sm text-zinc-500">O haz clic para navegar</span></span>
                    <span class="mr-2 rounded-full border border-zinc-300 bg-white px-5 py-2 text-sm font-medium">Selecciona archivos</span>
                    <input type="file" wire:model="form.gallery_uploads" accept="image/jpeg,image/png" multiple class="sr-only">
                </label>
                <div wire:loading wire:target="form.gallery_uploads" class="mt-2 text-sm font-medium text-indigo-600">Procesando imágenes…</div>
                @error('form.gallery_uploads') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                @error('form.gallery_uploads.*') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                <p class="mt-2 text-sm text-zinc-500">Tipo de archivo: JPG, PNG · Tamaño máximo: 6 MB por imagen</p>

                <div class="mt-5 grid auto-rows-[195px] grid-cols-1 gap-3 sm:grid-cols-3">
                    @foreach ($form->existingGalleryPaths as $index => $path)
                        <div class="group relative overflow-hidden rounded-lg {{ $index === 0 ? 'sm:col-span-2 sm:row-span-2' : '' }}">
                            <img src="{{ \App\Support\TenantAsset::url($path) }}" alt="Imagen de {{ $form->site_name }}" class="h-full w-full object-cover">
                            @if ($index === 0)<span class="absolute bottom-3 left-3 rounded-full bg-white px-3 py-1 text-xs font-medium text-indigo-600">Imagen de portada</span>@endif
                            <div class="absolute right-3 top-3 flex gap-2 opacity-100 sm:opacity-0 sm:transition sm:group-hover:opacity-100">
                                @if ($index !== 0)<button type="button" wire:click="makeGalleryCover({{ $index }})" title="Usar como portada" class="rounded-full bg-white px-3 py-2 text-xs font-medium shadow">Portada</button>@endif
                                <button type="button" wire:click="removeGalleryImage({{ $index }})" title="Eliminar imagen" class="flex size-9 items-center justify-center rounded-full bg-white text-lg font-bold shadow">×</button>
                            </div>
                        </div>
                    @endforeach

                    @foreach ($form->gallery_uploads as $upload)
                        <div class="relative overflow-hidden rounded-lg">
                            <img src="{{ $upload->temporaryUrl() }}" alt="Nueva imagen" class="h-full w-full object-cover">
                            <span class="absolute bottom-3 left-3 rounded-full bg-indigo-600 px-3 py-1 text-xs font-medium text-white">Nueva</span>
                        </div>
                    @endforeach

                    @if (count($form->existingGalleryPaths) + count($form->gallery_uploads) < 10)
                        <label class="flex min-h-48 cursor-pointer items-center justify-center rounded-lg bg-zinc-100 text-3xl text-zinc-700 transition hover:bg-zinc-200">
                            +
                            <input type="file" wire:model="form.gallery_uploads" accept="image/jpeg,image/png" multiple class="sr-only">
                        </label>
                    @endif
                </div>
            @endif
        </div>
    </section>
@else
<section wire:key="profile-settings-summary" class="min-h-screen bg-[#fafafa] text-zinc-950 dark:bg-zinc-950 dark:text-white">
    <div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:py-10">
        <div class="flex flex-col gap-5">
            <div class="flex flex-wrap items-center gap-3 text-sm">
                <a href="{{ route('administracion.empresa') }}" wire:navigate class="inline-flex h-9 items-center gap-2 rounded-full border border-zinc-300 bg-white px-4 font-medium transition hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">← Atrás</a>
                <span class="font-medium">Perfil en línea</span>
                <span class="text-zinc-400">·</span>
                <span class="font-medium">{{ $form->site_name }}</span>
            </div>

            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <div class="flex flex-wrap items-center gap-3">
                        <h1 class="text-3xl font-semibold tracking-[-0.04em]">{{ $form->site_name ?: 'Perfil web' }}</h1>
                        <span class="rounded-full border px-3 py-1 text-xs font-semibold {{ $form->is_active ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-amber-200 bg-amber-50 text-amber-700' }}">{{ $form->is_active ? 'Listado' : 'Borrador' }}</span>
                    </div>
                    <div class="mt-2 flex flex-wrap items-center gap-2 text-sm text-zinc-500">
                        <span>Aún no hay reseñas.</span>
                        <span>·</span>
                        @php($headerLocation = $this->locations->firstWhere('id', $form->primary_location_id))
                        <span class="font-medium text-emerald-700">{{ $headerLocation ? 'Abierto para reservas' : 'Ubicación pendiente' }}</span>
                        @if ($headerLocation)<span>·</span><span>{{ $headerLocation->address }}</span>@endif
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <a href="{{ $this->bookingUrl }}" target="_blank" rel="noreferrer" class="inline-flex h-11 items-center rounded-full border border-zinc-300 bg-white px-5 text-sm font-semibold transition hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">Perfil</a>
                    <button type="button" wire:click="goToStep(1)" class="inline-flex h-11 items-center rounded-full border border-zinc-300 bg-white px-5 text-sm font-semibold transition hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">Editar</button>
                    <button type="button" x-data x-on:click="navigator.clipboard.writeText(@js($this->bookingUrl))" title="Copiar enlace" class="flex size-11 items-center justify-center rounded-full border border-zinc-300 bg-white text-xl font-bold dark:border-zinc-700 dark:bg-zinc-900">⋮</button>
                </div>
            </div>
        </div>

        <div class="mt-8 grid gap-6 lg:grid-cols-[255px_minmax(0,1fr)] lg:items-start">
            <aside class="h-fit rounded-xl border border-zinc-200 bg-white p-3 dark:border-zinc-800 dark:bg-zinc-900 lg:sticky lg:top-6">
                <nav class="space-y-1 text-sm">
                    <button type="button" wire:click="goToStep(0)" class="flex w-full items-center gap-3 rounded-lg px-4 py-3 text-left font-medium transition {{ $activeStep === 0 ? 'bg-[#efefff]' : 'hover:bg-zinc-50 dark:hover:bg-zinc-800' }}"><span class="w-5 text-center">⌘</span>Descripción general</button>
                    <div class="mx-3 border-t border-zinc-200"></div>
                    @foreach ([1 => ['▣', 'Elementos esenciales'], 2 => ['⌖', 'Ubicación del negocio'], 3 => ['◷', 'Horario de apertura'], 4 => ['▤', 'Imágenes del lugar'], 5 => ['☺', 'Servicios y aspectos destacados'], 6 => ['≡', 'Descripción del lugar']] as $number => [$icon, $label])
                        <button type="button" wire:click="goToStep({{ $number }})" class="flex w-full items-center gap-3 rounded-lg px-4 py-3 text-left transition {{ $activeStep === $number ? 'bg-[#efefff] font-medium' : 'hover:bg-zinc-50 dark:hover:bg-zinc-800' }}"><span class="w-5 text-center text-base">{{ $icon }}</span><span class="truncate">{{ $label }}</span></button>
                    @endforeach
                    <div class="mx-3 border-t border-zinc-200"></div>
                    <a href="{{ route('locales.index') }}" wire:navigate class="flex items-center gap-3 rounded-lg px-4 py-3 text-zinc-500 transition hover:bg-zinc-50 dark:hover:bg-zinc-800"><span class="w-5 text-center">↗</span><span class="truncate">Configuración de ubicación</span></a>
                    <a href="{{ route('administracion.servicios.index') }}" wire:navigate class="flex items-center gap-3 rounded-lg px-4 py-3 text-zinc-500 transition hover:bg-zinc-50 dark:hover:bg-zinc-800"><span class="w-5 text-center">↗</span><span class="truncate">Configuración de reservas</span></a>
                </nav>
            </aside>

            @if ($activeStep === 0)
                <div class="space-y-6">
                    <div class="rounded-xl border border-zinc-200 bg-white p-6 sm:p-8 dark:border-zinc-800 dark:bg-zinc-900">
                        <div class="flex items-start justify-between gap-4">
                            <div><h2 class="text-xl font-semibold">Actuación</h2><p class="mt-1 text-sm text-zinc-500">Valor acumulado generado por tu perfil en línea</p></div>
                            <a href="{{ route('sales.index') }}" wire:navigate class="rounded-full border border-zinc-300 px-4 py-2 text-sm font-medium">Ver informe</a>
                        </div>

                        <div class="mt-7 space-y-3">
                            @foreach ([
                                ['☺', 'Total de nuevos clientes', number_format($this->performanceMetrics['clients'])],
                                ['◉', 'Valor total de las citas', $form->currency_symbol.' '.number_format($this->performanceMetrics['value'], 2)],
                                ['♧', 'Reservas realizadas en línea', number_format($this->performanceMetrics['appointments'])],
                                ['▣', 'Perfil completado', $this->completionPercentage.'%'],
                                ['⌁', 'Retorno de la inversión', number_format($this->performanceMetrics['roi'], 1).'%'],
                            ] as [$icon, $label, $value])
                                <div class="flex min-h-14 items-center justify-between gap-4 rounded-xl border border-[#d9d8ff] bg-[#f0f0ff] px-5 py-3 text-sm">
                                    <div class="flex items-center gap-3"><span class="text-lg">{{ $icon }}</span><span>{{ $label }}</span><span class="text-zinc-400">ⓘ</span></div>
                                    <strong class="text-lg sm:text-xl">{{ $value }}</strong>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="rounded-xl border border-zinc-200 bg-white p-6 sm:p-8 dark:border-zinc-800 dark:bg-zinc-900">
                        <div class="flex items-center justify-between gap-4"><h2 class="text-lg font-semibold">Última actividad</h2><button type="button" class="text-sm font-medium text-indigo-600">Ver toda la actividad</button></div>
                        <div class="mt-6 overflow-x-auto">
                            <table class="w-full min-w-[540px] text-left text-sm">
                                <thead><tr class="border-b border-zinc-200"><th class="px-4 py-3 font-semibold">Fecha</th><th class="px-4 py-3 font-semibold">Miembro del equipo</th><th class="px-4 py-3 font-semibold">Acción</th></tr></thead>
                                <tbody>
                                    <tr class="border-b border-zinc-200"><td class="px-4 py-4">{{ $this->settings->updated_at?->format('d/m/Y, H:i') }}</td><td class="px-4 py-4">{{ auth()->user()?->name }}</td><td class="px-4 py-4">Perfil actualizado</td></tr>
                                    @forelse ($this->recentWebAppointments as $appointment)
                                        <tr class="border-b border-zinc-200 last:border-0"><td class="px-4 py-4">{{ $appointment->created_at?->format('d/m/Y, H:i') }}</td><td class="px-4 py-4">{{ $appointment->client->fullName() }}</td><td class="px-4 py-4">Reserva web: {{ $appointment->service->name }}</td></tr>
                                    @empty
                                        <tr><td colspan="3" class="px-4 py-7 text-center text-zinc-500">Todavía no hay reservas realizadas desde el perfil.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @else
        <form wire:submit="save" class="{{ in_array($activeStep, [1, 2, 3, 4], true) ? 'space-y-6' : 'overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900' }}">
            @if (! in_array($activeStep, [1, 2, 3, 4], true))
            <div class="h-1 bg-zinc-100 dark:bg-zinc-800">
                <div class="h-full bg-emerald-500 transition-all duration-300" style="width: {{ ($activeStep / 6) * 100 }}%"></div>
            </div>
            @endif

            <div class="{{ in_array($activeStep, [1, 2, 3, 4], true) ? 'space-y-6' : 'p-5 sm:p-8 lg:p-10' }}">
                @if ($activeStep === 1)
                    <div class="rounded-xl border border-zinc-200 bg-white p-6 sm:p-8 dark:border-zinc-800 dark:bg-zinc-900">
                        <div class="flex items-start justify-between gap-4">
                            <h2 class="text-xl font-semibold">Elementos esenciales del lugar</h2>
                            <button type="button" wire:click.prevent="openEditor('essentials')" data-test="edit-essentials" class="rounded-full border border-zinc-300 px-4 py-2 text-sm font-medium transition hover:bg-zinc-50">Editar</button>
                        </div>
                        <dl class="mt-7 space-y-5 text-sm">
                            <div><dt class="font-medium">Nombre para mostrar</dt><dd class="mt-0.5 text-zinc-500">{{ $form->site_name ?: 'Sin configurar' }}</dd></div>
                            <div><dt class="font-medium">Número de teléfono comercial</dt><dd class="mt-0.5 text-zinc-500">{{ $form->contact_phone ?: 'Sin configurar' }}</dd></div>
                            <div><dt class="font-medium">Correo electrónico empresarial</dt><dd class="mt-0.5 text-zinc-500">{{ $form->contact_email ?: 'Sin configurar' }}</dd></div>
                        </dl>
                    </div>

                    <div class="rounded-xl border border-zinc-200 bg-white p-6 sm:p-8 dark:border-zinc-800 dark:bg-zinc-900">
                        <div class="flex items-start justify-between gap-4">
                            <h2 class="text-xl font-semibold">Acerca de</h2>
                            <button type="button" wire:click.prevent="openEditor('description')" data-test="edit-description" class="rounded-full border border-zinc-300 px-4 py-2 text-sm font-medium transition hover:bg-zinc-50">Editar</button>
                        </div>
                        <div class="mt-7 text-sm"><div class="font-medium">Descripción del lugar</div><p class="mt-1 whitespace-pre-line leading-6 text-zinc-600">{{ $form->description ?: 'Todavía no has añadido una descripción para este lugar.' }}</p></div>
                    </div>
                @elseif ($activeStep === 2)
                    <div class="rounded-xl border border-zinc-200 bg-white p-6 sm:p-8 dark:border-zinc-800 dark:bg-zinc-900">
                        <div class="flex items-start justify-between gap-4">
                            <div><h2 class="text-xl font-semibold">Ubicación del negocio</h2><p class="mt-1 text-sm text-zinc-500">{{ $form->location_address ?: 'Dirección pendiente' }}</p></div>
                            <button type="button" wire:click.prevent="openEditor('location')" data-test="edit-location" class="rounded-full border border-zinc-300 px-4 py-2 text-sm font-medium transition hover:bg-zinc-50">Editar</button>
                        </div>
                        <div class="relative mt-7 h-[370px] overflow-hidden rounded-lg border border-zinc-200 bg-[#e7ede8]">
                            <div class="absolute inset-0 opacity-70" style="background-image: linear-gradient(28deg, transparent 46%, #b6c2b8 47%, #b6c2b8 50%, transparent 51%), linear-gradient(118deg, transparent 46%, #c7d0c9 47%, #c7d0c9 50%, transparent 51%); background-size: 110px 78px;"></div>
                            <div class="absolute left-1/2 top-1/2 flex size-11 -translate-x-1/2 -translate-y-1/2 items-center justify-center rounded-full bg-zinc-950 text-white shadow-xl ring-8 ring-white/60">⌖</div>
                        </div>
                    </div>
                @elseif ($activeStep === 3)
                    <div class="rounded-xl border border-zinc-200 bg-white p-6 sm:p-8 dark:border-zinc-800 dark:bg-zinc-900">
                        <div class="flex items-start justify-between gap-4">
                            <div><h2 class="text-xl font-semibold">Tu horario de apertura</h2><p class="mt-1 max-w-xl text-sm text-zinc-500">Los horarios se muestran en tu perfil y son el horario comercial predeterminado para las reservas.</p></div>
                            <button type="button" wire:click.prevent="openEditor('hours')" data-test="edit-hours" class="rounded-full border border-zinc-300 px-4 py-2 text-sm font-medium transition hover:bg-zinc-50">Editar</button>
                        </div>

                        <div class="mt-7 space-y-1">
                            @foreach ($dayNames as $day => $dayName)
                                <div class="grid grid-cols-[14px_minmax(0,1fr)_auto] items-center gap-2 py-3 text-sm">
                                    <span class="size-3 rounded-full {{ $form->schedule[$day]['is_open'] ? 'bg-emerald-500' : 'bg-zinc-300' }}"></span>
                                    <span class="font-semibold">{{ $dayName }}</span>
                                    <span class="text-zinc-500">{{ $form->schedule[$day]['is_open'] ? $form->schedule[$day]['opens_at'].' a '.$form->schedule[$day]['closes_at'] : 'Cerrado' }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @elseif ($activeStep === 4)
                    <div class="rounded-xl border border-zinc-200 bg-white p-6 sm:p-8 dark:border-zinc-800 dark:bg-zinc-900">
                        <div class="flex items-start justify-between gap-4">
                            <div><h2 class="text-xl font-semibold">Imágenes del lugar</h2><p class="mt-1 max-w-xl text-sm text-zinc-500">Los perfiles con más de 5 imágenes se muestran mejor en las búsquedas. Puedes añadir hasta 10 imágenes.</p></div>
                            <button type="button" wire:click.prevent="openEditor('images')" data-test="edit-images" class="rounded-full border border-zinc-300 px-4 py-2 text-sm font-medium transition hover:bg-zinc-50">Editar</button>
                        </div>

                        <div class="mt-7 grid auto-rows-[165px] grid-cols-2 gap-3">
                            @foreach ($form->existingGalleryPaths as $index => $path)
                                @if ($index < 3)
                                <div class="relative overflow-hidden rounded-lg {{ $index === 0 ? 'col-span-2 row-span-2' : '' }}">
                                    <img src="{{ \App\Support\TenantAsset::url($path) }}" alt="Imagen del perfil" class="h-full w-full object-cover">
                                    @if ($index === 0)<span class="absolute top-3 left-3 rounded-full bg-white px-3 py-1 text-xs font-medium text-indigo-600">Imagen de portada</span>@endif
                                </div>
                                @endif
                            @endforeach
                            @if (count($form->existingGalleryPaths) === 0)
                                <div class="col-span-2 flex min-h-80 items-center justify-center rounded-lg bg-zinc-100 text-center text-sm text-zinc-500">Todavía no has añadido imágenes del lugar.</div>
                            @endif
                        </div>
                    </div>
                @elseif ($activeStep === 5)
                    <div class="mx-auto max-w-3xl">
                        <h2 class="text-3xl font-semibold tracking-[-0.035em]">Haz que tu perfil destaque</h2>
                        <p class="mt-2 text-zinc-500">Selecciona todo lo que tus clientes deberían saber antes de visitarte.</p>

                        <div class="mt-8">
                            <h3 class="text-sm font-bold">Comodidades</h3>
                            <div class="mt-3 flex flex-wrap gap-2">
                                @foreach ($amenityOptions as $amenity => $initial)
                                    <label class="cursor-pointer">
                                        <input type="checkbox" wire:model.live="form.amenities" value="{{ $amenity }}" class="peer sr-only">
                                        <span class="inline-flex items-center gap-2 rounded-full border border-zinc-200 px-4 py-2.5 text-sm font-medium transition peer-checked:border-zinc-950 peer-checked:bg-zinc-950 peer-checked:text-white dark:border-zinc-700 dark:peer-checked:border-white dark:peer-checked:bg-white dark:peer-checked:text-zinc-950"><span class="flex size-5 items-center justify-center rounded-full bg-zinc-100 text-[10px] font-bold text-zinc-600 peer-checked:bg-white/20">{{ $initial }}</span>{{ $amenity }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div class="mt-8">
                            <h3 class="text-sm font-bold">Aspectos destacados</h3>
                            <div class="mt-3 flex flex-wrap gap-2">
                                @foreach ($highlightOptions as $highlight)
                                    <label class="cursor-pointer">
                                        <input type="checkbox" wire:model.live="form.highlights" value="{{ $highlight }}" class="peer sr-only">
                                        <span class="inline-flex rounded-full border border-zinc-200 px-4 py-2.5 text-sm font-medium transition peer-checked:border-emerald-600 peer-checked:bg-emerald-50 peer-checked:text-emerald-800 dark:border-zinc-700 dark:peer-checked:bg-emerald-950/40 dark:peer-checked:text-emerald-200">{{ $highlight }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @else
                    <div class="mx-auto max-w-3xl">
                        <h2 class="text-3xl font-semibold tracking-[-0.035em]">Cuéntanos un poco sobre este lugar</h2>
                        <p class="mt-2 text-zinc-500">Una buena descripción transmite tu experiencia, especialidades y el ambiente que encontrarán tus clientes.</p>

                        <div class="mt-8" x-data="{ count: @entangle('form.description').live }">
                            <div class="mb-2 flex items-center justify-between text-sm"><label class="font-semibold">Descripción del perfil</label><span class="text-zinc-400" x-text="(count || '').length + '/2000'"></span></div>
                            <textarea wire:model.live="form.description" rows="11" maxlength="2000" placeholder="Describe tu negocio, especialidades y aquello que hace única la experiencia..." class="w-full rounded-2xl border border-zinc-300 bg-white p-4 text-sm leading-7 outline-none transition focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 dark:border-zinc-700 dark:bg-zinc-950"></textarea>
                            @error('form.description') <div class="mt-2 text-sm text-red-600">{{ $message }}</div> @enderror
                            <p class="mt-2 text-xs text-zinc-400">Recomendamos al menos 80 caracteres para una presentación completa.</p>
                        </div>

                        <div class="mt-6"><flux:textarea wire:model="form.booking_intro" label="Mensaje antes de reservar" rows="3" /></div>
                    </div>
                @endif
            </div>

            @if (! in_array($activeStep, [1, 2, 3, 4], true))
            <div class="flex flex-col-reverse gap-3 border-t border-zinc-200 bg-zinc-50 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-8 dark:border-zinc-800 dark:bg-zinc-950/50">
                <button type="button" wire:click="goToStep({{ max(0, $activeStep - 1) }})" class="h-11 rounded-full px-5 text-sm font-semibold text-zinc-600 dark:text-zinc-300">← Atrás</button>
                <div class="flex items-center justify-end gap-2">
                    <flux:button type="submit" variant="ghost">Guardar y salir</flux:button>
                    @if ($activeStep < 6)
                        <flux:button type="button" wire:click="saveAndContinue" variant="primary">Continuar →</flux:button>
                    @else
                        <flux:button type="submit" variant="primary">Publicar perfil</flux:button>
                    @endif
                </div>
            </div>
            @endif
        </form>
            @endif
        </div>
    </div>
</section>
@endif
</div>
