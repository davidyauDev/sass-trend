@php
    $settings = $this->settings;
    $location = $this->profileLocation;
    $services = $this->profileServices;
    $team = $this->profileProfessionals;
    $confirmedAppointment = $this->confirmedAppointment;
    $gallery = collect([$settings->heroImageUrl($tenantSlug), ...$settings->galleryUrls($tenantSlug)])->filter()->unique()->values();
    $serviceCategories = $services->pluck('category')->filter()->unique('id')->values();
    $bookingServices = $this->services;
    $bookingServiceGroups = $bookingServices->groupBy(fn ($service) => $service->category?->name ?? 'Servicios');
    $selectedBookingService = $services->firstWhere('id', $service_id);
    $selectedBookingProfessional = $this->professionals->firstWhere('id', $professional_id);
    $bookingDates = collect(range(0, 7))->map(fn (int $day) => \Carbon\CarbonImmutable::today()->addDays($day));
    $canContinueBooking = match ($bookingStep) {
        1 => $service_id !== null,
        2 => $professional_id !== null,
        3 => $selected_starts_at !== '',
        default => true,
    };
    $directionsUrl = $location ? 'https://www.google.com/maps/search/?api=1&query='.urlencode($location->address) : '#';
@endphp

<div
    x-data="{ bookingOpen: false, galleryOpen: false, activeCategory: 'all', showAllServices: false }"
    x-on:open-booking.window="bookingOpen = true"
    class="min-h-screen bg-white"
>
    <header class="sticky top-0 z-30 border-b border-zinc-200/80 bg-white/95 backdrop-blur">
        <div class="mx-auto flex h-16 max-w-6xl items-center justify-between gap-5 px-4 sm:px-6">
            <a href="#inicio" class="flex min-w-0 items-center gap-3">
                @if ($settings->logoUrl($tenantSlug))
                    <img src="{{ $settings->logoUrl($tenantSlug) }}" alt="{{ $settings->site_name }}" class="size-9 rounded-xl object-cover">
                @else
                    <span class="flex size-9 items-center justify-center rounded-xl bg-zinc-950 text-xs font-bold text-white">{{ \Illuminate\Support\Str::of($settings->site_name)->substr(0, 2)->upper() }}</span>
                @endif
                <span class="truncate text-lg font-semibold tracking-[-0.03em]">{{ $settings->site_name }}</span>
            </a>

            <div class="hidden h-10 flex-1 items-center rounded-full border border-zinc-200 bg-zinc-50 px-4 text-sm text-zinc-400 md:flex md:max-w-xl">
                Servicios&nbsp;&nbsp;&nbsp;·&nbsp;&nbsp;&nbsp;{{ $location?->address ?? 'Reserva online' }}
            </div>

            <button type="button" x-on:click="bookingOpen = true" class="shrink-0 rounded-full bg-zinc-950 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-zinc-800">Reservar</button>
        </div>
    </header>

    <main id="inicio" class="mx-auto max-w-6xl px-4 pb-20 pt-8 sm:px-6">
        <section>
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <div class="text-sm text-zinc-500">Perfil verificado · Reserva online</div>
                    <h1 class="mt-1 text-4xl font-semibold tracking-[-0.05em] sm:text-5xl">{{ $settings->site_name }}</h1>
                    <div class="mt-3 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-zinc-500">
                        @if ($location)
                            <span class="font-medium text-emerald-700">Abierto para reservas</span><span>·</span><span>{{ $location->address }}</span><span>·</span><a href="{{ $directionsUrl }}" target="_blank" rel="noreferrer" class="font-medium text-zinc-900 underline underline-offset-4">Cómo llegar</a>
                        @endif
                    </div>
                </div>
                <div class="flex gap-2">
                    <button type="button" x-data x-on:click="navigator.share ? navigator.share({ title: @js($settings->site_name), url: window.location.href }) : navigator.clipboard.writeText(window.location.href)" class="flex size-11 items-center justify-center rounded-full border border-zinc-200 text-lg">↗</button>
                    <button type="button" class="flex size-11 items-center justify-center rounded-full border border-zinc-200 text-xl">♡</button>
                </div>
            </div>

            <div class="mt-7 grid h-[310px] grid-cols-1 gap-3 overflow-hidden rounded-[1.25rem] sm:h-[430px] sm:grid-cols-[2fr_1fr]">
                <button type="button" x-on:click="galleryOpen = true" class="relative overflow-hidden bg-gradient-to-br from-stone-200 via-rose-100 to-amber-100 text-left">
                    @if ($gallery->get(0))
                        <img src="{{ $gallery->get(0) }}" alt="{{ $settings->site_name }}" class="h-full w-full object-cover transition duration-700 hover:scale-[1.02]">
                    @else
                        <div class="absolute inset-0 flex items-end p-8"><span class="max-w-md text-3xl font-semibold tracking-tight text-stone-700">{{ $settings->tagline ?: 'Tu momento, tu estilo, tu esencia.' }}</span></div>
                    @endif
                </button>
                <div class="hidden grid-rows-2 gap-3 sm:grid">
                    @foreach ([1, 2] as $index)
                        <button type="button" x-on:click="galleryOpen = true" class="relative overflow-hidden bg-gradient-to-br from-emerald-50 to-stone-200">
                            @if ($gallery->get($index))
                                <img src="{{ $gallery->get($index) }}" alt="Espacio de {{ $settings->site_name }}" class="h-full w-full object-cover transition duration-700 hover:scale-[1.03]">
                            @else
                                <div class="absolute inset-0 opacity-30" style="background-image: radial-gradient(circle at 30% 30%, white 0 8%, transparent 9%), linear-gradient(135deg, transparent 48%, #9ca3af 49% 51%, transparent 52%);"></div>
                            @endif
                            @if ($index === 2 && $gallery->count() > 0)
                                <span class="absolute bottom-4 right-4 rounded-full bg-white px-4 py-2 text-xs font-semibold text-zinc-950 shadow">Ver {{ $gallery->count() }} imágenes</span>
                            @endif
                        </button>
                    @endforeach
                </div>
            </div>
        </section>

        <div class="mt-10 grid gap-10 lg:grid-cols-[minmax(0,1fr)_330px] lg:items-start">
            <div class="min-w-0 space-y-12">
                <section id="servicios">
                    <div class="flex items-center justify-between gap-4">
                        <h2 class="text-xl font-semibold tracking-tight">Servicios</h2>
                        <span class="text-sm text-zinc-500">{{ $services->count() }} disponibles</span>
                    </div>

                    @if ($serviceCategories->isNotEmpty())
                        <div class="mt-5 flex gap-2 overflow-x-auto pb-2">
                            <button type="button" x-on:click="activeCategory = 'all'" class="shrink-0 rounded-full border px-4 py-2 text-xs font-semibold transition" x-bind:class="activeCategory === 'all' ? 'border-zinc-950 bg-zinc-950 text-white' : 'border-zinc-200 bg-white text-zinc-700'">Todos</button>
                            @foreach ($serviceCategories as $category)
                                <button type="button" x-on:click="activeCategory = '{{ $category->id }}'" class="shrink-0 rounded-full border px-4 py-2 text-xs font-semibold transition" x-bind:class="activeCategory === '{{ $category->id }}' ? 'border-zinc-950 bg-zinc-950 text-white' : 'border-zinc-200 bg-white text-zinc-700'">{{ $category->name }}</button>
                            @endforeach
                        </div>
                    @endif

                    <div class="mt-4 space-y-3">
                        @forelse ($services as $service)
                            <article
                                wire:key="public-service-{{ $service->id }}"
                                x-cloak
                                x-show="(activeCategory === 'all' || activeCategory === '{{ $service->service_category_id }}') && (activeCategory !== 'all' || showAllServices || {{ $loop->index }} < 4)"
                                x-transition.opacity
                                class="flex items-center justify-between gap-5 rounded-2xl border border-zinc-200 bg-white p-5"
                            >
                                <div class="min-w-0">
                                    <h3 class="font-semibold text-zinc-950">{{ $service->name }}</h3>
                                    <p class="mt-1 text-sm text-zinc-500">
                                        <span>{{ $service->duration_minutes }} min</span>
                                        @if ($service->description)
                                            <span> · {{ $service->description }}</span>
                                        @endif
                                    </p>
                                    <div class="mt-2 text-sm font-semibold">{{ $settings->currency_symbol }} {{ number_format((float) $service->price, 2) }}</div>
                                </div>
                                <button type="button" wire:click="chooseService({{ $service->id }})" class="shrink-0 rounded-full border border-zinc-300 px-4 py-2 text-xs font-semibold transition hover:border-zinc-950 hover:bg-zinc-950 hover:text-white">Reservar</button>
                            </article>
                        @empty
                            <div class="rounded-2xl border border-dashed border-zinc-300 px-6 py-10 text-center text-sm text-zinc-500">Los servicios online aparecerán aquí cuando estén publicados.</div>
                        @endforelse
                    </div>

                    @if ($services->count() > 4)
                        <button type="button" x-show="activeCategory === 'all'" x-on:click="showAllServices = ! showAllServices" class="mt-4 rounded-full border border-zinc-300 px-5 py-2.5 text-xs font-semibold" x-text="showAllServices ? 'Ver menos' : 'Ver todos'"></button>
                    @endif
                </section>

                <section class="border-t border-zinc-200 pt-9">
                    <div class="flex items-center justify-between">
                        <h2 class="text-xl font-semibold tracking-tight">Equipo</h2>
                        @if ($team->isNotEmpty())
                            <span class="text-sm text-zinc-500">Ver todos</span>
                        @endif
                    </div>
                    <div class="mt-5 flex gap-8 overflow-x-auto pb-2">
                        @forelse ($team as $professional)
                            <div class="w-24 shrink-0 text-center">
                                @if ($professional->photoUrl($tenantSlug))
                                    <img src="{{ $professional->photoUrl($tenantSlug) }}" alt="{{ $professional->displayName() }}" class="mx-auto size-20 rounded-full object-cover ring-1 ring-zinc-200">
                                @else
                                    <div class="mx-auto flex size-20 items-center justify-center rounded-full bg-sky-100 text-2xl font-medium text-sky-800">{{ $professional->initials() }}</div>
                                @endif
                                <div class="mt-3 truncate text-sm font-medium">{{ $professional->displayName() }}</div>
                            </div>
                        @empty
                            <div class="rounded-2xl border border-dashed border-zinc-300 px-6 py-8 text-sm text-zinc-500">El equipo se mostrará aquí cuando haya profesionales activos.</div>
                        @endforelse
                    </div>
                </section>

                <section class="border-t border-zinc-200 pt-9">
                    <h2 class="text-xl font-semibold tracking-tight">Sobre nosotros</h2>
                    <div class="mt-4 whitespace-pre-line text-[15px] leading-7 text-zinc-600">{{ $settings->description ?: 'Conoce nuestros servicios y reserva una experiencia pensada para ti. Nuestro equipo está listo para atenderte.' }}</div>
                    @if ($settings->tagline)<div class="mt-5 rounded-xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-900">{{ $settings->tagline }}</div>@endif
                </section>

                @if ($settings->amenities || $settings->highlights)
                    <section class="border-t border-zinc-200 pt-9">
                        <h2 class="text-xl font-semibold tracking-tight">Lo que encontrarás</h2>
                        <div class="mt-5 grid gap-3 sm:grid-cols-2">
                            @foreach ([...($settings->amenities ?? []), ...($settings->highlights ?? [])] as $feature)
                                <div class="flex items-center gap-3 rounded-xl border border-zinc-200 px-4 py-3 text-sm"><span class="flex size-7 items-center justify-center rounded-full bg-emerald-100 text-xs font-bold text-emerald-700">✓</span>{{ $feature }}</div>
                            @endforeach
                        </div>
                    </section>
                @endif

                @if ($location)
                    <section class="border-t border-zinc-200 pt-9">
                        <h2 class="text-xl font-semibold tracking-tight">Ubicación</h2>
                        <div class="relative mt-5 h-80 overflow-hidden rounded-[1.25rem] border border-zinc-200 bg-[#e7ede8]">
                            <div class="absolute inset-0 opacity-60" style="background-image: linear-gradient(28deg, transparent 46%, #b6c2b8 47%, #b6c2b8 50%, transparent 51%), linear-gradient(118deg, transparent 46%, #c7d0c9 47%, #c7d0c9 50%, transparent 51%); background-size: 110px 78px;"></div>
                            <div class="absolute left-1/2 top-1/2 flex size-12 -translate-x-1/2 -translate-y-1/2 items-center justify-center rounded-full bg-zinc-950 text-white shadow-xl ring-8 ring-white/60">●</div>
                            <a href="{{ $directionsUrl }}" target="_blank" rel="noreferrer" class="absolute bottom-4 right-4 rounded-full bg-white px-4 py-2 text-sm font-semibold shadow">Abrir mapa ↗</a>
                        </div>
                        <div class="mt-3 text-sm font-medium">{{ $location->address }}</div>
                        @if ($settings->directions)<p class="mt-1 text-sm text-zinc-500">{{ $settings->directions }}</p>@endif
                    </section>

                    <section class="grid gap-10 border-t border-zinc-200 pt-9 sm:grid-cols-2">
                        <div>
                            <h2 class="text-xl font-semibold tracking-tight">Horarios</h2>
                            <div class="mt-4 space-y-2 text-sm">
                                @foreach ([1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo'] as $day => $dayName)
                                    @php($hours = $location->schedules->firstWhere('day_of_week', $day))
                                    <div class="grid grid-cols-[10px_1fr_auto] items-center gap-2"><span class="size-2 rounded-full {{ $hours?->is_open ? 'bg-emerald-500' : 'bg-zinc-300' }}"></span><span>{{ $dayName }}</span><span class="font-medium">{{ $hours?->is_open ? date('g:i A', strtotime($hours->opens_at)).' – '.date('g:i A', strtotime($hours->closes_at)) : 'Cerrado' }}</span></div>
                                @endforeach
                            </div>
                        </div>
                        <div>
                            <h2 class="text-xl font-semibold tracking-tight">Información adicional</h2>
                            <div class="mt-4 space-y-3 text-sm text-zinc-600"><div>✓ {{ $settings->instant_confirmation ? 'Confirmación inmediata' : 'Confirmación sujeta a disponibilidad' }}</div>@if ($settings->contact_phone)<div>Teléfono: {{ $settings->contact_phone }}</div>@endif @if ($settings->contact_email)<div>{{ $settings->contact_email }}</div>@endif</div>
                        </div>
                    </section>
                @endif
            </div>

            <aside class="sticky top-24 hidden rounded-[1.25rem] border border-zinc-200 bg-white p-4 shadow-[0_12px_45px_rgba(0,0,0,0.08)] lg:block">
                <button type="button" x-on:click="bookingOpen = true" class="h-12 w-full rounded-full bg-zinc-950 text-sm font-semibold text-white transition hover:bg-zinc-800">{{ $settings->booking_button_label }}</button>
                <div class="mt-5 space-y-4 border-t border-zinc-200 pt-5 text-sm">
                    <div class="flex gap-3"><span>◷</span><div><div class="font-medium">Reservas disponibles</div><div class="mt-0.5 text-zinc-500">Consulta los horarios en tiempo real</div></div></div>
                    @if ($location)<div class="flex gap-3"><span>⌖</span><div><div class="font-medium">{{ $location->name }}</div><div class="mt-0.5 text-zinc-500">{{ $location->address }}</div></div></div>@endif
                    <div class="flex gap-3"><span>✓</span><div><div class="font-medium">{{ $settings->instant_confirmation ? 'Confirmación inmediata' : 'Solicitud de reserva' }}</div><div class="mt-0.5 text-zinc-500">Recibirás los datos de tu cita al finalizar</div></div></div>
                </div>
            </aside>
        </div>
    </main>

    <button type="button" x-on:click="bookingOpen = true" class="fixed inset-x-4 bottom-4 z-20 h-13 rounded-full bg-zinc-950 text-sm font-semibold text-white shadow-xl lg:hidden">{{ $settings->booking_button_label }}</button>

    <div
        x-cloak
        x-show="bookingOpen || @js((bool) $confirmedAppointment)"
        x-transition.opacity
        class="fixed inset-0 z-50 overflow-y-auto bg-[#f7f7f7]"
        x-on:keydown.escape.window="bookingOpen = false; $wire.resetBookingFlow()"
    >
        <div class="mx-auto min-h-screen max-w-6xl px-4 pb-8 pt-5 sm:px-6 lg:px-10">
            <div class="flex items-center justify-between">
                <button type="button" wire:click="previousBookingStep" @disabled($bookingStep === 1 || $confirmedAppointment) class="flex size-11 items-center justify-center rounded-full border border-zinc-300 bg-white text-xl transition hover:bg-zinc-100 disabled:opacity-30">←</button>
                <button type="button" x-on:click="bookingOpen = false" wire:click="resetBookingFlow" class="flex size-11 items-center justify-center rounded-full border border-zinc-300 bg-white text-xl transition hover:bg-zinc-100">×</button>
            </div>

            <div class="mx-auto mt-3 grid max-w-5xl gap-8 lg:grid-cols-[minmax(0,1fr)_355px] lg:items-start">
                <main class="min-w-0 pb-24">
                    @if ($confirmedAppointment)
                        <section class="rounded-3xl border border-emerald-200 bg-white p-8 shadow-sm sm:p-12">
                            <div class="flex size-14 items-center justify-center rounded-full bg-emerald-600 text-2xl text-white">✓</div>
                            <h2 class="mt-6 text-3xl font-semibold tracking-[-0.04em]">¡Tu cita está confirmada!</h2>
                            <p class="mt-3 text-zinc-600">Código <strong>{{ $confirmedAppointment->reference_code }}</strong>. Te esperamos el {{ $confirmedAppointment->starts_at->format('d/m/Y') }} a las {{ $confirmedAppointment->starts_at->format('H:i') }}.</p>
                            <div class="mt-7 grid gap-3 rounded-2xl bg-zinc-50 p-5 text-sm sm:grid-cols-2">
                                <span>{{ $confirmedAppointment->service->name }}</span>
                                <span>{{ $confirmedAppointment->professional?->fullName() ?: 'Profesional por confirmar' }}</span>
                                <span>{{ $confirmedAppointment->branch?->name }}</span>
                                <span>{{ $confirmedAppointment->client->fullName() }}</span>
                            </div>
                        </section>
                    @else
                        <nav class="flex items-center gap-2 text-xs font-medium text-zinc-400">
                            @foreach ([1 => 'Servicios', 2 => 'Profesional', 3 => 'Tiempo', 4 => 'Confirmar'] as $step => $label)
                                @if ($step > 1)<span>›</span>@endif
                                <span class="{{ $bookingStep === $step ? 'text-zinc-950' : '' }}">{{ $label }}</span>
                            @endforeach
                        </nav>

                        @if ($bookingStep === 1)
                            <section class="mt-4">
                                <h2 class="text-3xl font-semibold tracking-[-0.04em] sm:text-4xl">Selecciona servicios</h2>
                                <div class="mt-7 space-y-9">
                                    @forelse ($bookingServiceGroups as $categoryName => $categoryServices)
                                        <div>
                                            <h3 class="mb-3 text-base font-semibold">{{ $categoryName }}</h3>
                                            <div class="space-y-3">
                                                @foreach ($categoryServices as $bookingService)
                                                    <button type="button" wire:click="selectBookingService({{ $bookingService->id }})" class="flex w-full items-center justify-between gap-5 rounded-2xl border bg-white p-5 text-left transition {{ $service_id === $bookingService->id ? 'border-[#6c4cff] ring-1 ring-[#6c4cff]' : 'border-zinc-200 hover:border-zinc-400' }}">
                                                        <span class="min-w-0">
                                                            <span class="block font-medium">{{ $bookingService->name }}</span>
                                                            <span class="mt-1 block text-sm text-zinc-500">{{ intdiv($bookingService->duration_minutes, 60) > 0 ? intdiv($bookingService->duration_minutes, 60).' h ' : '' }}{{ $bookingService->duration_minutes % 60 > 0 ? ($bookingService->duration_minutes % 60).' min' : '' }}</span>
                                                            <span class="mt-4 block text-sm font-semibold">{{ $settings->currency_symbol }} {{ number_format((float) $bookingService->price, 2) }}</span>
                                                        </span>
                                                        <span class="flex size-8 shrink-0 items-center justify-center rounded-full {{ $service_id === $bookingService->id ? 'bg-[#6c4cff] text-white' : 'border border-zinc-300 bg-white text-xl' }}">{{ $service_id === $bookingService->id ? '✓' : '+' }}</span>
                                                    </button>
                                                @endforeach
                                            </div>
                                        </div>
                                    @empty
                                        <div class="rounded-2xl border border-dashed border-zinc-300 bg-white p-10 text-center text-sm text-zinc-500">No hay servicios disponibles para reservar en línea.</div>
                                    @endforelse
                                </div>
                                @error('service_id')<p class="mt-4 text-sm text-red-600">{{ $message }}</p>@enderror
                            </section>
                        @elseif ($bookingStep === 2)
                            <section class="mt-4">
                                <h2 class="text-3xl font-semibold tracking-[-0.04em] sm:text-4xl">Selecciona profesional</h2>
                                <div class="mt-7 space-y-3">
                                    @if ($this->professionals->isNotEmpty())
                                        @php($firstProfessional = $this->professionals->first())
                                        <button type="button" wire:click="selectBookingProfessional({{ $firstProfessional->id }})" class="flex w-full items-center gap-4 rounded-2xl border bg-white p-5 text-left transition {{ $professional_id === $firstProfessional->id ? 'border-[#6c4cff] ring-1 ring-[#6c4cff]' : 'border-zinc-200 hover:border-zinc-400' }}">
                                            <span class="flex size-16 items-center justify-center rounded-full bg-violet-100 text-2xl text-[#6c4cff]">⌁</span>
                                            <span class="min-w-0 flex-1"><span class="block font-medium">Sin preferencia</span><span class="mt-1 block text-sm text-zinc-500">Máxima disponibilidad</span></span>
                                            <span class="rounded-full border border-zinc-300 px-4 py-2 text-xs font-semibold">Seleccionar</span>
                                        </button>
                                    @endif
                                    @forelse ($this->professionals as $professional)
                                        <button type="button" wire:click="selectBookingProfessional({{ $professional->id }})" class="flex w-full items-center gap-4 rounded-2xl border bg-white p-5 text-left transition {{ $professional_id === $professional->id ? 'border-[#6c4cff] ring-1 ring-[#6c4cff]' : 'border-zinc-200 hover:border-zinc-400' }}">
                                            @if ($professional->photoUrl($tenantSlug))
                                                <img src="{{ $professional->photoUrl($tenantSlug) }}" alt="{{ $professional->displayName() }}" class="size-16 rounded-full object-cover">
                                            @else
                                                <span class="flex size-16 items-center justify-center rounded-full bg-sky-100 text-2xl font-medium text-sky-800">{{ $professional->initials() }}</span>
                                            @endif
                                            <span class="min-w-0 flex-1"><span class="block font-medium">{{ $professional->displayName() }}</span><span class="mt-1 block text-sm text-zinc-500">Ver perfil</span></span>
                                            <span class="rounded-full border border-zinc-300 px-4 py-2 text-xs font-semibold">Seleccionar</span>
                                        </button>
                                    @empty
                                        <div class="rounded-2xl border border-dashed border-zinc-300 bg-white p-10 text-center text-sm text-zinc-500">No hay profesionales disponibles para este servicio.</div>
                                    @endforelse
                                </div>
                                @error('professional_id')<p class="mt-4 text-sm text-red-600">{{ $message }}</p>@enderror
                            </section>
                        @elseif ($bookingStep === 3)
                            <section class="mt-4">
                                <h2 class="text-3xl font-semibold tracking-[-0.04em] sm:text-4xl">Selecciona fecha y hora</h2>
                                @if ($selectedBookingProfessional)
                                    <div class="mt-6 inline-flex items-center gap-2 rounded-full border border-zinc-300 bg-white px-3 py-1.5 text-sm">
                                        <span class="flex size-6 items-center justify-center rounded-full bg-sky-100 text-xs font-bold text-sky-800">{{ $selectedBookingProfessional->initials() }}</span>
                                        <span>{{ $selectedBookingProfessional->displayName() }}</span><span>⌄</span>
                                    </div>
                                @endif
                                <h3 class="mt-6 font-semibold">Selecciona una fecha</h3>
                                <div class="mt-3 flex gap-3 overflow-x-auto pb-2">
                                    @foreach ($bookingDates as $bookingDate)
                                        <button type="button" wire:click="selectBookingDate('{{ $bookingDate->toDateString() }}')" class="flex min-w-14 flex-col items-center rounded-2xl border px-3 py-3 text-center transition {{ $selected_date === $bookingDate->toDateString() ? 'border-[#6c4cff] bg-[#6c4cff] text-white' : 'border-zinc-200 bg-white hover:border-zinc-400' }}">
                                            <span class="text-xs capitalize opacity-80">{{ $bookingDate->locale('es')->translatedFormat('D') }}</span>
                                            <span class="mt-1 text-xl font-semibold">{{ $bookingDate->format('d') }}</span>
                                            <span class="text-xs capitalize opacity-80">{{ $bookingDate->locale('es')->translatedFormat('M') }}</span>
                                        </button>
                                    @endforeach
                                </div>
                                <h3 class="mt-5 font-semibold">Elige una hora</h3>
                                <div class="mt-3 space-y-3">
                                    @forelse ($this->availableSlots as $slot)
                                        <button type="button" wire:click="selectSlot('{{ $slot['starts_at'] }}')" class="w-full rounded-2xl border bg-white px-5 py-4 text-left text-sm font-semibold transition {{ $selected_starts_at === $slot['starts_at'] ? 'border-[#6c4cff] ring-1 ring-[#6c4cff]' : 'border-zinc-200 hover:border-zinc-400' }}">{{ $slot['label'] }}</button>
                                    @empty
                                        <div class="rounded-2xl border border-dashed border-zinc-300 bg-white p-8 text-center text-sm text-zinc-500">No hay horarios disponibles para esta fecha. Prueba con otro día.</div>
                                    @endforelse
                                </div>
                                @error('starts_at')<p class="mt-4 text-sm text-red-600">{{ $message }}</p>@enderror
                            </section>
                        @else
                            <section class="mt-4">
                                <h2 class="text-3xl font-semibold tracking-[-0.04em] sm:text-4xl">Confirma tu reserva</h2>
                                <p class="mt-3 text-sm text-zinc-500">Completa tus datos para recibir la confirmación de la cita.</p>
                                <form wire:submit="submit" class="mt-7 rounded-3xl border border-zinc-200 bg-white p-6 sm:p-8">
                                    <div class="grid gap-5 sm:grid-cols-2">
                                        <flux:input wire:model="first_name" label="Nombre *" required />
                                        <flux:input wire:model="last_name" label="Apellido *" required />
                                        <flux:input wire:model="email" label="Correo electrónico" type="email" />
                                        <flux:input wire:model="phone" label="Teléfono *" required />
                                        <div class="sm:col-span-2"><flux:textarea wire:model="notes" label="Notas (opcional)" rows="3" /></div>
                                    </div>
                                    <button type="submit" class="mt-7 h-12 w-full rounded-full bg-zinc-950 text-sm font-semibold text-white lg:hidden">Confirmar reserva</button>
                                </form>
                            </section>
                        @endif
                    @endif
                </main>

                <aside class="overflow-hidden rounded-3xl border border-zinc-200 bg-white lg:sticky lg:top-5 lg:flex lg:min-h-[calc(100vh-6rem)] lg:flex-col">
                    <div class="p-6">
                        <div class="flex items-center gap-3 border-b border-zinc-200 pb-5">
                            @if ($gallery->first())
                                <img src="{{ $gallery->first() }}" alt="{{ $settings->site_name }}" class="size-12 rounded-lg object-cover">
                            @else
                                <span class="flex size-12 items-center justify-center rounded-lg bg-zinc-950 text-xs font-bold text-white">{{ \Illuminate\Support\Str::of($settings->site_name)->substr(0, 2)->upper() }}</span>
                            @endif
                            <div class="min-w-0"><div class="truncate font-semibold">{{ $settings->site_name }}</div><div class="mt-1 line-clamp-2 text-xs text-zinc-500">{{ $location?->address ?? 'Reserva online' }}</div></div>
                        </div>

                        @if ($confirmedAppointment)
                            <div class="mt-5 space-y-3 text-sm"><div class="font-semibold">{{ $confirmedAppointment->service->name }}</div><div class="text-zinc-500">{{ $confirmedAppointment->starts_at->format('d/m/Y · H:i') }}</div><div class="border-t border-zinc-200 pt-4 font-semibold">Total <span class="float-right">{{ $settings->currency_symbol }} {{ number_format((float) $confirmedAppointment->price, 2) }}</span></div></div>
                        @elseif ($selectedBookingService)
                            @if ($selected_starts_at !== '')
                                <div class="mt-5 space-y-2 border-b border-zinc-200 pb-5 text-sm">
                                    <div>▣ {{ \Carbon\CarbonImmutable::parse($selected_starts_at)->locale('es')->translatedFormat('l, d \d\e F') }}</div>
                                    <div>◷ {{ \Carbon\CarbonImmutable::parse($selected_starts_at)->format('H:i') }} - {{ \Carbon\CarbonImmutable::parse($selected_starts_at)->addMinutes($selectedBookingService->duration_minutes)->format('H:i') }}</div>
                                </div>
                            @endif
                            <div class="mt-5 flex justify-between gap-4 text-sm">
                                <div>
                                    <div class="font-medium">{{ $selectedBookingService->name }}</div>
                                    <div class="mt-1 text-xs text-zinc-500">
                                        <span>{{ $selectedBookingService->duration_minutes }} min</span>
                                        @if ($selectedBookingProfessional)
                                            <span> con <span class="text-[#6c4cff]">{{ $selectedBookingProfessional->displayName() }}</span></span>
                                        @endif
                                    </div>
                                </div>
                                <div class="shrink-0 font-medium">{{ $settings->currency_symbol }} {{ number_format((float) $selectedBookingService->price, 2) }}</div>
                            </div>
                            <div class="mt-5 flex justify-between border-t border-zinc-200 pt-5 text-sm font-semibold"><span>Total</span><span>{{ $settings->currency_symbol }} {{ number_format((float) $selectedBookingService->price, 2) }}</span></div>
                        @else
                            <p class="mt-6 text-sm text-zinc-500">Selecciona un servicio para ver el resumen de tu reserva.</p>
                        @endif
                    </div>

                    @if (! $confirmedAppointment)
                        <div class="mt-auto p-6 pt-2">
                            @if ($bookingStep < 4)
                                <button type="button" wire:click="continueBooking" wire:loading.attr="disabled" @disabled(! $canContinueBooking) class="h-12 w-full rounded-full bg-zinc-950 text-sm font-semibold text-white transition disabled:cursor-not-allowed disabled:bg-zinc-400">Continuar&nbsp; →</button>
                            @else
                                <button type="button" wire:click="submit" wire:loading.attr="disabled" class="hidden h-12 w-full rounded-full bg-zinc-950 text-sm font-semibold text-white lg:block">Confirmar reserva&nbsp; →</button>
                            @endif
                        </div>
                    @endif
                </aside>
            </div>
        </div>
    </div>

    <div x-cloak x-show="galleryOpen" x-transition.opacity class="fixed inset-0 z-50 overflow-y-auto bg-white p-5 sm:p-10">
        <div class="mx-auto max-w-5xl"><div class="mb-6 flex items-center justify-between"><h2 class="text-2xl font-semibold">Galería de {{ $settings->site_name }}</h2><button type="button" x-on:click="galleryOpen = false" class="flex size-11 items-center justify-center rounded-full bg-zinc-100 text-xl">×</button></div><div class="grid gap-4 sm:grid-cols-2">@forelse ($gallery as $image)<img src="{{ $image }}" alt="{{ $settings->site_name }}" class="w-full rounded-2xl object-cover">@empty<div class="col-span-2 rounded-2xl bg-zinc-100 p-16 text-center text-zinc-500">Todavía no hay imágenes publicadas.</div>@endforelse</div></div>
    </div>
</div>
