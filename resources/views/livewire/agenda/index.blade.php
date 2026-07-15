<section
    @class(['agenda-page', 'fixed inset-0 z-50' => $isFullscreen])
    x-data="agendaAppointmentPreview()"
    wire:poll.30s
    data-testid="agenda-page"
>
    <header class="agenda-toolbar">
        <div class="agenda-toolbar-group">
            <button type="button" class="agenda-control" wire:click="today">Hoy</button>

            <div
                class="agenda-month-switcher"
                wire:key="agenda-date-picker-{{ $this->selectedDate }}"
                x-data="agendaDatePicker({
                    selectedDate: @js($this->selectedDate),
                    onChange(date) {
                        $wire.set('selectedDate', date);
                    },
                })"
                @click.outside="close()"
                @keydown.escape.window="close()"
            >
                <button type="button" aria-label="Periodo anterior" wire:click="previous">
                    <flux:icon.chevron-left class="size-4" />
                </button>
                <button
                    type="button"
                    class="agenda-date-trigger"
                    title="{{ $this->periodLabel }}"
                    :aria-expanded="open"
                    @click="open = ! open"
                >
                    {{ $this->periodLabel }}
                </button>
                <button type="button" aria-label="Periodo siguiente" wire:click="next">
                    <flux:icon.chevron-right class="size-4" />
                </button>

                <div
                    x-show="open"
                    x-cloak
                    x-transition.opacity.scale.origin.top.left
                    class="agenda-date-picker"
                    role="dialog"
                    aria-label="Seleccionar fecha"
                >
                    <div class="agenda-date-picker__months-header">
                        <button type="button" aria-label="Mes anterior" @click="shiftMonths(-1)">
                            <flux:icon.chevron-left class="size-5" />
                        </button>
                        <strong x-text="monthTitle(0)"></strong>
                        <strong x-text="monthTitle(1)"></strong>
                        <button type="button" aria-label="Mes siguiente" @click="shiftMonths(1)">
                            <flux:icon.chevron-right class="size-5" />
                        </button>
                    </div>

                    <div class="agenda-date-picker__months">
                        <template x-for="monthIndex in [0, 1]" :key="monthIndex">
                            <section class="agenda-date-picker__month">
                                <div class="agenda-date-picker__weekdays">
                                    <template x-for="weekday in weekdays" :key="weekday">
                                        <span x-text="weekday"></span>
                                    </template>
                                </div>
                                <div class="agenda-date-picker__days">
                                    <template x-for="(day, dayIndex) in monthDays(monthIndex)" :key="`${monthIndex}-${dayIndex}`">
                                        <button
                                            type="button"
                                            :disabled="day === null"
                                            :class="{ 'is-selected': isSelected(day, monthIndex) }"
                                            @click="chooseDate(day, monthIndex)"
                                            x-text="day ?? ''"
                                        ></button>
                                    </template>
                                </div>
                            </section>
                        </template>
                    </div>

                    <div class="agenda-date-picker__quick-actions">
                        <template x-for="weeks in [1, 2, 3, 4, 5]" :key="weeks">
                            <button type="button" @click="chooseWeeks(weeks)" x-text="`En ${weeks} semana${weeks > 1 ? 's' : ''}`"></button>
                        </template>
                        <div class="agenda-date-picker__more" @click.outside="moreOpen = false">
                            <button type="button" @click="moreOpen = ! moreOpen">
                                Más
                                <flux:icon.chevron-down class="size-4" />
                            </button>
                            <div x-show="moreOpen" x-cloak class="agenda-date-picker__more-menu">
                                <button type="button" @click="chooseWeeks(6)">En 6 semanas</button>
                                <button type="button" @click="chooseWeeks(8)">En 8 semanas</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div
                x-data="agendaTeamFilter({
                    selectedIds: @js($professionalFilterIds),
                    professionals: @js(
                        $this->professionalsCatalog
                            ->values()
                            ->map(fn ($professional, int $index): array => [
                                'id' => (int) $professional->id,
                                'label' => $professional->fullName(),
                                'initial' => mb_strtoupper(mb_substr($professional->fullName(), 0, 1)),
                                'photo' => $professional->photoUrl(),
                                'isCurrent' => (int) $professional->id === (int) auth()->id(),
                                'tone' => ($index % 3) + 1,
                            ])
                    ),
                    onChange(ids) {
                        $wire.set('professionalFilterIds', ids);
                    },
                })"
                class="agenda-team-filter"
                @click.outside="closePanel()"
                @keydown.escape.window="closePanel()"
            >
                <button
                    type="button"
                    class="agenda-control agenda-team-filter__trigger"
                    aria-label="Filtrar miembros del equipo"
                    x-bind:aria-expanded="open"
                    @click="open ? closePanel() : openPanel()"
                >
                    <span class="truncate" x-text="triggerLabel"></span>
                    <flux:icon.chevron-down class="size-4 shrink-0 transition" x-bind:class="{ 'rotate-180': open }" />
                </button>

                <div
                    x-show="open"
                    x-cloak
                    x-transition.opacity.scale.origin.top.left
                    class="agenda-team-filter__panel"
                    role="dialog"
                    aria-label="Miembros del equipo"
                >
                    <label class="agenda-team-filter__search">
                        <flux:icon.magnifying-glass class="size-5" />
                        <input
                            x-ref="search"
                            x-model="query"
                            type="search"
                            placeholder="Buscar"
                            aria-label="Buscar profesional"
                        >
                    </label>

                    <div class="agenda-team-filter__scheduled">
                        <div class="agenda-team-filter__heading">
                            <flux:icon.calendar-days class="size-5" />
                            <span>Equipo programado</span>
                        </div>

                        <button
                            type="button"
                            class="agenda-team-filter__quick-option"
                            :class="{ 'is-selected': allSelected }"
                            @click="selectAll()"
                        >
                            <span class="agenda-team-filter__option-icon">
                                <flux:icon.users class="size-5" />
                            </span>
                            <span>Todo el equipo</span>
                        </button>

                        <template x-if="currentProfessional">
                            <button
                                type="button"
                                class="agenda-team-filter__quick-option"
                                :class="{ 'is-selected': onlyCurrentSelected }"
                                @click="selectCurrent()"
                            >
                                <span class="agenda-team-filter__option-avatar agenda-team-filter__option-avatar--1">
                                    <img x-show="currentProfessional.photo" :src="currentProfessional.photo" alt="">
                                    <span x-show="! currentProfessional.photo" x-text="currentProfessional.initial"></span>
                                </span>
                                <span class="truncate">
                                    <span x-text="currentProfessional.label"></span>
                                    <span>(Tú)</span>
                                </span>
                            </button>
                        </template>
                    </div>

                    <div class="agenda-team-filter__divider"></div>

                    <div class="agenda-team-filter__members">
                        <button
                            type="button"
                            class="agenda-team-filter__member agenda-team-filter__member--all"
                            role="checkbox"
                            :aria-checked="allSelected"
                            @click="toggleAll()"
                        >
                            <span class="agenda-team-filter__checkbox" :class="{ 'is-checked': allSelected }">
                                <flux:icon.check class="size-4" />
                            </span>
                            <strong>Todos los miembros del equipo</strong>
                        </button>

                        <div class="agenda-team-filter__member-list">
                            <template x-for="professional in filteredProfessionals" :key="professional.id">
                                <button
                                    type="button"
                                    class="agenda-team-filter__member"
                                    role="checkbox"
                                    :aria-checked="isSelected(professional.id)"
                                    @click="toggleProfessional(professional.id)"
                                >
                                    <span class="agenda-team-filter__checkbox" :class="{ 'is-checked': isSelected(professional.id) }">
                                        <flux:icon.check class="size-4" />
                                    </span>
                                    <span
                                        class="agenda-team-filter__option-avatar"
                                        :class="`agenda-team-filter__option-avatar--${professional.tone}`"
                                    >
                                        <img x-show="professional.photo" :src="professional.photo" alt="">
                                        <span x-show="! professional.photo" x-text="professional.initial"></span>
                                    </span>
                                    <span class="truncate">
                                        <span x-text="professional.label"></span>
                                        <span x-show="professional.isCurrent"> (Tú)</span>
                                    </span>
                                </button>
                            </template>
                        </div>

                        <div x-show="filteredProfessionals.length === 0" class="agenda-team-filter__empty">
                            No encontramos profesionales.
                        </div>
                    </div>
                </div>
            </div>

            <details class="agenda-filters" x-data @agenda-open-filters.window="$el.open = true">
                <summary class="agenda-icon-button" aria-label="Abrir filtros">
                    <flux:icon.adjustments-horizontal class="size-5" />
                </summary>
                <div class="agenda-filters-panel">
                    <div class="agenda-filters-title">
                        <strong>Filtros</strong>
                        <button type="button" wire:click="clearFilters">Restablecer</button>
                    </div>
                    <label>
                        <span>Sucursal</span>
                        <select wire:model.live="branchFilterId">
                            <option value="">Todas las sucursales</option>
                            @foreach ($this->branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>
                        <span>Recurso</span>
                        <select wire:model.live="resourceFilterId">
                            <option value="">Todos los recursos</option>
                            @foreach ($this->resourcesCatalog as $resource)
                                <option value="{{ $resource->id }}">{{ $resource->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="agenda-filter-check">
                        <input type="checkbox" wire:model.live="onlyAvailable">
                        <span>Solo disponibles</span>
                    </label>
                </div>
            </details>
        </div>

        <div class="agenda-toolbar-group agenda-toolbar-actions">
            <button type="button" class="agenda-icon-button" aria-label="Pantalla completa" wire:click="toggleFullscreen">
                <flux:icon.cog-6-tooth class="size-5" />
            </button>
            <livewire:agenda.waitlist-panel />
            <button type="button" class="agenda-icon-button" aria-label="Actualizar" wire:click="$refresh">
                <flux:icon.arrow-path class="size-5" />
            </button>

            <div
                class="agenda-view-select"
                x-data="{
                    open: false,
                    view: $wire.entangle('viewMode').live,
                    labels: { day: 'Día', three_days: '3 días', week: 'Semana', month: 'Mes' },
                    choose(value) {
                        this.view = value;
                        this.open = false;
                    },
                }"
                @click.outside="open = false"
                @keydown.escape.window="open = false"
            >
                <button
                    type="button"
                    class="agenda-control agenda-view-select__trigger"
                    aria-label="Vista del calendario"
                    :aria-expanded="open"
                    @click="open = ! open"
                >
                    <span x-text="labels[view]"></span>
                    <flux:icon.chevron-down class="size-4" x-bind:class="{ 'rotate-180': open }" />
                </button>

                <div
                    x-show="open"
                    x-cloak
                    x-transition.opacity.scale.origin.top.right
                    class="agenda-view-select__menu"
                    role="menu"
                    aria-label="Seleccionar vista"
                >
                    <button type="button" role="menuitemradio" :aria-checked="view === 'day'" :class="{ 'is-selected': view === 'day' }" @click="choose('day')">
                        <span class="agenda-view-glyph agenda-view-glyph--day" aria-hidden="true"></span>
                        <span>Día</span>
                    </button>
                    <button type="button" role="menuitemradio" :aria-checked="view === 'three_days'" :class="{ 'is-selected': view === 'three_days' }" @click="choose('three_days')">
                        <span class="agenda-view-glyph agenda-view-glyph--three-days" aria-hidden="true"></span>
                        <span>3 días</span>
                    </button>
                    <button type="button" role="menuitemradio" :aria-checked="view === 'week'" :class="{ 'is-selected': view === 'week' }" @click="choose('week')">
                        <span class="agenda-view-glyph agenda-view-glyph--week" aria-hidden="true"></span>
                        <span>Semana</span>
                    </button>
                    <button type="button" role="menuitemradio" :aria-checked="view === 'month'" :class="{ 'is-selected': view === 'month' }" @click="choose('month')">
                        <span class="agenda-view-glyph agenda-view-glyph--month" aria-hidden="true"></span>
                        <span>Mes</span>
                    </button>
                </div>
            </div>

            <button type="button" class="agenda-primary-button" wire:click="openCreateModal">
                Agregar
                <flux:icon.chevron-down class="size-4" />
            </button>
        </div>
    </header>

    @if ($this->viewMode === 'month')
        <div
            class="agenda-grid-scroll"
            x-data="{
                swipeActive: false,
                swipeHandled: false,
                swipeAxis: null,
                swipeAnimating: false,
                swipeWheelLocked: false,
                swipeStartX: 0,
                swipeStartY: 0,
                dragX: 0,
                startSwipeAt(x, y) {
                    if (this.swipeAnimating) return;

                    this.swipeActive = true;
                    this.swipeHandled = false;
                    this.swipeAxis = null;
                    this.dragX = 0;
                    this.swipeStartX = x;
                    this.swipeStartY = y;
                },
                moveSwipeAt(x, y, event) {
                    if (! this.swipeActive || this.swipeAnimating) return;

                    const distanceX = x - this.swipeStartX;
                    const distanceY = y - this.swipeStartY;

                    if (this.swipeAxis === null && Math.max(Math.abs(distanceX), Math.abs(distanceY)) >= 6) {
                        this.swipeAxis = Math.abs(distanceX) > Math.abs(distanceY) * 1.1 ? 'horizontal' : 'vertical';
                    }

                    if (this.swipeAxis !== 'horizontal') return;

                    event.preventDefault();
                    const limit = this.$el.clientWidth * 0.92;
                    this.dragX = Math.max(-limit, Math.min(limit, distanceX));
                },
                finishSwipeAt(x, y) {
                    if (! this.swipeActive) return;

                    this.swipeActive = false;

                    const distanceX = x - this.swipeStartX;
                    const distanceY = y - this.swipeStartY;
                    const isHorizontalSwipe = this.swipeAxis === 'horizontal'
                        && Math.abs(distanceX) >= Math.min(90, this.$el.clientWidth * 0.12)
                        && Math.abs(distanceX) > Math.abs(distanceY) * 1.1;

                    if (! isHorizontalSwipe) {
                        this.snapBack();
                        return;
                    }

                    this.swipeHandled = true;
                    this.$dispatch('agenda-quick-open', { date: null });
                    this.navigateMonth(distanceX < 0 ? 1 : -1);
                },
                navigateMonth(direction) {
                    if (this.swipeAnimating) return;

                    this.swipeAnimating = true;
                    this.dragX = direction > 0 ? -this.$el.clientWidth : this.$el.clientWidth;

                    setTimeout(() => {
                        const navigation = direction > 0 ? $wire.next() : $wire.previous();
                        const resetFallback = setTimeout(() => this.finishNavigation(), 1500);

                        navigation.then(() => {
                            clearTimeout(resetFallback);
                            this.$nextTick(() => this.finishNavigation());
                        });
                    }, 230);
                },
                finishNavigation() {
                    this.swipeActive = false;
                    this.swipeAnimating = false;
                    this.dragX = 0;
                    this.swipeAxis = null;
                },
                snapBack() {
                    if (this.dragX === 0) {
                        this.swipeAxis = null;
                        return;
                    }

                    this.swipeAnimating = true;
                    this.dragX = 0;
                    setTimeout(() => {
                        this.swipeAnimating = false;
                        this.swipeAxis = null;
                    }, 230);
                },
                handleHorizontalWheel(event) {
                    if (this.swipeWheelLocked || this.swipeAnimating) return;

                    const isHorizontalSwipe = Math.abs(event.deltaX) >= 40
                        && Math.abs(event.deltaX) > Math.abs(event.deltaY);

                    if (! isHorizontalSwipe) return;

                    event.preventDefault();
                    this.swipeWheelLocked = true;
                    this.$dispatch('agenda-quick-open', { date: null });
                    this.navigateMonth(event.deltaX > 0 ? 1 : -1);
                    setTimeout(() => this.swipeWheelLocked = false, 500);
                },
                cancelSwipe() {
                    this.swipeActive = false;
                    this.snapBack();
                },
                consumeSwipeClick(event) {
                    if (! this.swipeHandled) return;

                    event.preventDefault();
                    event.stopImmediatePropagation();
                    this.swipeHandled = false;
                },
            }"
            :class="{ 'agenda-grid-scroll--dragging': swipeActive && swipeAxis === 'horizontal' }"
            @pointerdown="if ($event.button === 0 || $event.pointerType !== 'mouse') startSwipeAt($event.clientX, $event.clientY)"
            @pointermove.window="moveSwipeAt($event.clientX, $event.clientY, $event)"
            @pointerup.window="finishSwipeAt($event.clientX, $event.clientY)"
            @pointercancel.window="cancelSwipe()"
            @wheel="handleHorizontalWheel($event)"
            @click.capture="consumeSwipeClick($event)"
        >
            <div
                class="agenda-month-track"
                wire:key="agenda-month-track-{{ substr($this->selectedDate, 0, 7) }}"
                :class="{ 'agenda-month-track--animating': swipeAnimating }"
                :style="`transform: translate3d(calc(-33.333333% + ${dragX}px), 0, 0)`"
            >
            @foreach ($this->monthSlides as $slide)
            <div
                class="agenda-calendar agenda-calendar--slide"
                wire:key="agenda-month-slide-{{ $slide['key'] }}"
                data-month="{{ $slide['key'] }}"
                @if ($slide['offset'] !== 0) aria-hidden="true" @endif
            >
                <div class="agenda-weekdays" aria-hidden="true">
                    <div>Domingo</div>
                    <div>Lunes</div>
                    <div>Martes</div>
                    <div>Miércoles</div>
                    <div>Jueves</div>
                    <div>Viernes</div>
                    <div>Sábado</div>
                </div>

                <div
                    class="agenda-month-grid"
                    style="--agenda-weeks: {{ count($slide['grid']) / 7 }}"
                    @if ($slide['offset'] === 0) data-testid="agenda-month-grid" @endif
                >
                    @foreach ($slide['grid'] as $cell)
                        @if ($slide['offset'] !== 0)
                            <article
                                wire:key="agenda-preview-{{ $slide['key'] }}-{{ $cell['key'] }}"
                                @class([
                                    'agenda-day',
                                    'agenda-day--outside' => ! $cell['is_in_month'] || $cell['is_unavailable'],
                                ])
                            >
                                <span @class(['agenda-day-number', 'agenda-day-number--today' => $cell['is_today']])>
                                    {{ $cell['day'] === 1 ? $cell['date']->translatedFormat('j \d\e F') : $cell['day'] }}
                                </span>
                            </article>
                            @continue
                        @endif

                        <article
                            wire:key="agenda-day-{{ $cell['key'] }}"
                            x-data="{ quickOpen: false }"
                            @agenda-quick-open.window="quickOpen = $event.detail.date === '{{ $cell['key'] }}'"
                            @keydown.escape.window="quickOpen = false"
                            @click="$dispatch('agenda-quick-open', { date: '{{ $cell['key'] }}' })"
                            @class([
                                'agenda-day',
                                'agenda-day--outside' => ! $cell['is_in_month'] || $cell['is_unavailable'],
                                'agenda-day--selected' => $cell['is_selected'] && ! $cell['is_today'],
                            ])
                        >
                            <button
                                type="button"
                                @class(['agenda-day-number', 'agenda-day-number--today' => $cell['is_today']])
                            >
                                {{ $cell['day'] === 1 ? $cell['date']->translatedFormat('j \d\e F') : $cell['day'] }}
                            </button>

                            <div
                                x-show="quickOpen"
                                x-cloak
                                @click.stop
                                @click.outside="quickOpen = false"
                                @class([
                                    'agenda-quick-menu',
                                    'agenda-quick-menu--left' => ($loop->index % 7) >= 2,
                                    'agenda-quick-menu--up' => $loop->index >= count($slide['grid']) - 14,
                                ])
                            >
                                <div class="agenda-quick-header">
                                    <strong>{{ ucfirst($cell['date']->translatedFormat('l, j \d\e F')) }}</strong>
                                    <button type="button" aria-label="Cerrar acciones rápidas" @click="quickOpen = false">
                                        <flux:icon.x-mark class="size-4" />
                                    </button>
                                </div>

                                <div class="agenda-quick-actions">
                                    <button type="button" wire:click="openCreateModalForDate('{{ $cell['key'] }}')" @click="quickOpen = false">
                                        <flux:icon.calendar-days class="size-5" />
                                        <span>Agregar cita</span>
                                    </button>
                                    <button type="button" wire:click="openScheduleBlockModalForDate('{{ $cell['key'] }}')" @click="quickOpen = false">
                                        <flux:icon.calendar-days class="size-5" />
                                        <span>Agregar tiempo bloqueado</span>
                                    </button>
                                    <button type="button" wire:click="openDayView('{{ $cell['key'] }}')" @click="quickOpen = false">
                                        <flux:icon.rectangle-stack class="size-5" />
                                        <span>Vista diurna</span>
                                    </button>
                                    <button type="button" class="agenda-quick-settings" @click="quickOpen = false; $dispatch('agenda-open-filters')">
                                        Configuración de acciones rápidas
                                    </button>
                                </div>
                            </div>

                            <div class="agenda-events">
                                @foreach ($cell['appointments'] as $appointment)
                                    <button
                                        type="button"
                                        @class(['agenda-event', 'agenda-event--'.$appointment->status->slug])
                                        wire:key="agenda-appointment-{{ $appointment->id }}"
                                        wire:click.stop="openDrawer({{ $appointment->id }})"
                                        @mouseenter="showAppointmentPreview($event, @js($this->appointmentPreviewData($appointment)))"
                                        @mouseleave="scheduleAppointmentPreviewHide()"
                                        @click="hideAppointmentPreview()"
                                        title="{{ $appointment->title }}"
                                    >
                                        <span>{{ $appointment->starts_at?->format('H:i') }} {{ $appointment->client->fullName() }}</span>
                                        @switch($appointment->status->slug)
                                            @case(\App\Services\Agenda\AppointmentStatusCatalog::COMPLETED)<flux:icon.tag class="size-4" />@break
                                            @case(\App\Services\Agenda\AppointmentStatusCatalog::NO_SHOW)<flux:icon.eye-slash class="size-4" />@break
                                            @case(\App\Services\Agenda\AppointmentStatusCatalog::CONFIRMED)<flux:icon.hand-thumb-up class="size-4" />@break
                                            @case(\App\Services\Agenda\AppointmentStatusCatalog::ARRIVED)<flux:icon.map-pin class="size-4" />@break
                                            @case(\App\Services\Agenda\AppointmentStatusCatalog::IN_PROGRESS)<flux:icon.play class="size-4" />@break
                                        @endswitch
                                    </button>
                                @endforeach

                                @foreach ($cell['blocks'] as $block)
                                    <div class="agenda-block">
                                        {{ $block['starts_at']->format('H:i') }} · {{ $block['label'] }}
                                    </div>
                                @endforeach
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
            @endforeach
            </div>
        </div>
    @elseif ($this->viewMode === 'list')
        <div class="agenda-list-view">
            @forelse ($this->appointments as $appointment)
                <button
                    type="button"
                    class="agenda-list-item"
                    wire:click="openDrawer({{ $appointment->id }})"
                    @mouseenter="showAppointmentPreview($event, @js($this->appointmentPreviewData($appointment)))"
                    @mouseleave="scheduleAppointmentPreviewHide()"
                    @click="hideAppointmentPreview()"
                >
                    <time>{{ $appointment->starts_at?->translatedFormat('D d M · H:i') }}</time>
                    <strong>{{ $appointment->client->fullName() }}</strong>
                    <span>{{ $appointment->service->name }} · {{ $appointment->branch->name }}</span>
                    <span>{{ $appointment->status?->name }}</span>
                </button>
            @empty
                <div class="agenda-empty-view">No hay citas en este periodo.</div>
            @endforelse
        </div>
    @elseif ($this->viewMode === 'day')
        @php
            $day = $this->rangeDays[0];
            $dayStart = $day['date']->setTime(8, 0);
            $dayEnd = $day['date']->setTime(20, 0);
        @endphp
        <div class="agenda-day-schedule" style="--agenda-professionals: {{ max(1, $this->scheduleProfessionals->count()) }}">
            <header class="agenda-day-schedule__header">
                <span class="agenda-day-schedule__corner"></span>
                @forelse ($this->scheduleProfessionals as $professional)
                    <div class="agenda-professional-heading">
                        <span class="agenda-professional-avatar">
                            @if ($professional->photoUrl())
                                <img src="{{ $professional->photoUrl() }}" alt="">
                            @else
                                {{ $professional->initials() }}
                            @endif
                        </span>
                        <strong>{{ $professional->fullName() }}</strong>
                    </div>
                @empty
                    <div class="agenda-schedule-empty">Selecciona al menos un miembro del equipo.</div>
                @endforelse
            </header>

            <div class="agenda-day-schedule__scroll">
                <div class="agenda-day-schedule__body">
                    <aside class="agenda-time-axis">
                        @for ($hour = 8; $hour <= 20; $hour++)
                            <span style="--agenda-hour: {{ $hour - 8 }}">{{ sprintf('%02d:00', $hour) }}</span>
                        @endfor
                    </aside>

                    @foreach ($this->scheduleProfessionals as $professional)
                        <section class="agenda-day-professional">
                            <div class="agenda-day-slots" aria-label="Intervalos de 15 minutos para {{ $professional->fullName() }}">
                                @for ($slotIndex = 0; $slotIndex < 48; $slotIndex++)
                                    @php
                                        $slotDateTime = $dayStart->addMinutes($slotIndex * 15);
                                    @endphp
                                    <button
                                        type="button"
                                        class="agenda-day-slot"
                                        :class="{ 'is-selected': quickSlot?.dateTime === @js($slotDateTime->format('Y-m-d\TH:i')) && quickSlot?.professionalId === {{ $professional->id }} }"
                                        title="{{ $slotDateTime->format('H:i') }}"
                                        @click.stop="openDaySlotMenu($event, @js($slotDateTime->format('Y-m-d\TH:i')), {{ $professional->id }})"
                                    >
                                        <span>{{ $slotDateTime->format('H:i') }}</span>
                                    </button>
                                @endfor
                            </div>

                            @foreach ($day['appointments']->filter(fn ($appointment) => (int) $appointment->professional_id === (int) $professional->id || ($loop->first && $appointment->professional_id === null)) as $appointment)
                                @php
                                    $startOffset = max(0, $dayStart->diffInMinutes($appointment->starts_at, false));
                                    $duration = max(24, $appointment->starts_at->diffInMinutes($appointment->ends_at));
                                @endphp
                                <button
                                    type="button"
                                    @class(['agenda-timeline-event', 'agenda-timeline-event--'.$appointment->status->slug])
                                    style="--agenda-event-start: {{ $startOffset }}; --agenda-event-duration: {{ $duration }}"
                                    wire:click="openDrawer({{ $appointment->id }})"
                                    @mouseenter="showAppointmentPreview($event, @js($this->appointmentPreviewData($appointment)))"
                                    @mouseleave="scheduleAppointmentPreviewHide()"
                                    @click="hideAppointmentPreview()"
                                >
                                    <span><strong>{{ $appointment->starts_at->format('H:i') }} - {{ $appointment->ends_at->format('H:i') }} {{ $appointment->client->fullName() }}</strong><small>{{ $appointment->service->name }}</small></span>
                                    @switch($appointment->status->slug)
                                        @case(\App\Services\Agenda\AppointmentStatusCatalog::COMPLETED)<flux:icon.tag class="size-4" />@break
                                        @case(\App\Services\Agenda\AppointmentStatusCatalog::NO_SHOW)<flux:icon.eye-slash class="size-4" />@break
                                        @case(\App\Services\Agenda\AppointmentStatusCatalog::CONFIRMED)<flux:icon.hand-thumb-up class="size-4" />@break
                                        @case(\App\Services\Agenda\AppointmentStatusCatalog::ARRIVED)<flux:icon.map-pin class="size-4" />@break
                                        @case(\App\Services\Agenda\AppointmentStatusCatalog::IN_PROGRESS)<flux:icon.play class="size-4" />@break
                                    @endswitch
                                </button>
                            @endforeach
                        </section>
                    @endforeach

                    @if ($day['date']->isToday() && now()->between($dayStart, $dayEnd))
                        <div class="agenda-current-time" style="--agenda-now: {{ $dayStart->diffInMinutes(now()) }}">
                            <span>{{ now()->format('H:i') }}</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @else
        <div class="agenda-multi-schedule agenda-multi-schedule--{{ $this->viewMode }}" style="--agenda-days: {{ count($this->rangeDays) }}">
            <header class="agenda-multi-schedule__header">
                <span></span>
                @foreach ($this->rangeDays as $day)
                    <button
                        type="button"
                        @class(['is-selected' => $day['is_selected'], 'is-muted' => $day['date']->isBefore(now()->startOfDay())])
                        wire:click="$set('selectedDate', '{{ $day['key'] }}')"
                    >
                        <strong>{{ $day['date']->format('j') }}</strong>
                        <span>{{ $day['date']->translatedFormat('l') }}</span>
                    </button>
                @endforeach
            </header>

            <div class="agenda-multi-schedule__body">
                @forelse ($this->scheduleProfessionals as $professional)
                    <section class="agenda-professional-row">
                        <aside>
                            <span class="agenda-professional-avatar agenda-professional-avatar--small">
                                @if ($professional->photoUrl())
                                    <img src="{{ $professional->photoUrl() }}" alt="">
                                @else
                                    {{ $professional->initials() }}
                                @endif
                            </span>
                            <strong>{{ $professional->fullName() }}</strong>
                        </aside>

                        @foreach ($this->rangeDays as $day)
                            <div
                                @class(['agenda-professional-day', 'is-selected' => $day['is_selected'], 'is-closed' => $day['date']->isSunday()])
                                @click.self="openDateQuickMenu($event, @js($day['key']), @js(ucfirst($day['date']->translatedFormat('l, j \d\e F'))), {{ $professional->id }})"
                            >
                                <div class="agenda-events">
                                    @foreach ($day['appointments']->filter(fn ($appointment) => (int) $appointment->professional_id === (int) $professional->id || ($loop->parent->first && $appointment->professional_id === null)) as $appointment)
                                        <button
                                            type="button"
                                            @class(['agenda-event', 'agenda-event--'.$appointment->status->slug])
                                            wire:click="openDrawer({{ $appointment->id }})"
                                            @mouseenter="showAppointmentPreview($event, @js($this->appointmentPreviewData($appointment)))"
                                            @mouseleave="scheduleAppointmentPreviewHide()"
                                            @click="hideAppointmentPreview()"
                                        >
                                            <span>
                                                {{ $appointment->starts_at->format('H:i') }}
                                                @if ($this->viewMode === 'three_days')
                                                    - {{ $appointment->ends_at->format('H:i') }}
                                                @endif
                                                {{ $appointment->client->fullName() }}
                                            </span>
                                            @switch($appointment->status->slug)
                                                @case(\App\Services\Agenda\AppointmentStatusCatalog::COMPLETED)<flux:icon.tag class="size-4" />@break
                                                @case(\App\Services\Agenda\AppointmentStatusCatalog::NO_SHOW)<flux:icon.eye-slash class="size-4" />@break
                                                @case(\App\Services\Agenda\AppointmentStatusCatalog::CONFIRMED)<flux:icon.hand-thumb-up class="size-4" />@break
                                                @case(\App\Services\Agenda\AppointmentStatusCatalog::ARRIVED)<flux:icon.map-pin class="size-4" />@break
                                                @case(\App\Services\Agenda\AppointmentStatusCatalog::IN_PROGRESS)<flux:icon.play class="size-4" />@break
                                            @endswitch
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </section>
                @empty
                    <div class="agenda-schedule-empty">Selecciona al menos un miembro del equipo.</div>
                @endforelse
            </div>
        </div>
    @endif

    <aside
        x-show="quickSlot"
        x-cloak
        class="agenda-slot-menu"
        :style="`left: ${quickSlotX}px; top: ${quickSlotY}px`"
        @click.outside="closeDaySlotMenu()"
        @keydown.escape.window="closeDaySlotMenu()"
    >
        <header>
            <strong x-text="quickSlot?.title"></strong>
            <button type="button" aria-label="Cerrar acciones rápidas" @click="closeDaySlotMenu()">
                <flux:icon.x-mark class="size-5" />
            </button>
        </header>
        <div>
            <button type="button" @click="quickSlot.kind === 'date' ? $wire.openCreateModalForDateAndProfessional(quickSlot.date, quickSlot.professionalId) : $wire.openCreateModalForSlot(quickSlot.dateTime, quickSlot.professionalId); closeDaySlotMenu()">
                <flux:icon.calendar-days class="size-5" />
                <span>Agregar cita</span>
            </button>
            <button type="button" @click="quickSlot.kind === 'date' ? $wire.openCreateModalForDateAndProfessional(quickSlot.date, quickSlot.professionalId) : $wire.openCreateModalForSlot(quickSlot.dateTime, quickSlot.professionalId); closeDaySlotMenu()">
                <flux:icon.users class="size-5" />
                <span>Agregar cita grupal</span>
            </button>
            <button type="button" @click="$wire.openScheduleBlockModalForSlot(quickSlot.dateTime, quickSlot.professionalId); closeDaySlotMenu()">
                <flux:icon.calendar-days class="size-5" />
                <span>Agregar tiempo bloqueado</span>
            </button>
            <button x-show="quickSlot?.kind === 'date'" type="button" @click="$wire.openDayView(quickSlot.date); closeDaySlotMenu()">
                <flux:icon.rectangle-stack class="size-5" />
                <span>Vista diurna</span>
            </button>
            <button type="button" class="agenda-slot-menu__settings" @click="closeDaySlotMenu(); $dispatch('agenda-open-filters')">
                Configuración de acciones rápidas
            </button>
        </div>
    </aside>

    <aside
        x-show="preview"
        x-cloak
        class="agenda-hover-card"
        :class="preview ? `agenda-hover-card--${preview.status}` : ''"
        :style="`left: ${previewX}px; top: ${previewY}px`"
        aria-live="polite"
    >
        <header class="agenda-hover-card__header">
            <strong><span x-text="preview?.startsAt"></span> - <span x-text="preview?.endsAt"></span></strong>
            <span class="agenda-hover-card__status">
                <span x-text="preview?.statusLabel"></span>
                <template x-if="preview?.status === 'completed'"><flux:icon.tag class="size-5" /></template>
                <template x-if="preview?.status === 'no_show'"><flux:icon.eye-slash class="size-5" /></template>
                <template x-if="preview?.status === 'confirmed'"><flux:icon.hand-thumb-up class="size-5" /></template>
                <template x-if="preview?.status === 'arrived'"><flux:icon.map-pin class="size-5" /></template>
                <template x-if="preview?.status === 'in_progress'"><flux:icon.play class="size-5" /></template>
                <template x-if="! ['completed', 'no_show', 'confirmed', 'arrived', 'in_progress'].includes(preview?.status)"><flux:icon.calendar-days class="size-5" /></template>
            </span>
        </header>

        <div class="agenda-hover-card__body">
            <div class="agenda-hover-card__client">
                <span class="agenda-hover-card__avatar">
                    <svg x-show="preview?.isWalkIn" viewBox="0 0 24 24" aria-hidden="true">
                        <circle cx="13.5" cy="4" r="2"></circle>
                        <path d="m11.5 7-2.8 4.2 3.2 2.1-1.2 6.1M11.5 7l4 2.2 2.5-1.7M12 13.4l4 5.6M8.7 11.2 5 14"></path>
                    </svg>
                    <span x-show="! preview?.isWalkIn" x-text="preview?.initial"></span>
                </span>
                <div>
                    <strong x-text="preview?.clientName"></strong>
                    <p x-show="preview?.contact" x-text="preview?.contact"></p>
                    <span x-show="preview?.status === 'no_show'" class="agenda-hover-card__alert">1 persona no se presentó</span>
                    <button x-show="preview?.status === 'no_show'" type="button" tabindex="-1" class="agenda-hover-card__tag"><flux:icon.plus class="size-4" /> Agregar etiqueta</button>
                </div>
            </div>

            <div class="agenda-hover-card__service">
                <div>
                    <strong x-text="preview?.service"></strong>
                    <p><span x-text="preview?.duration"></span> · <span x-text="preview?.professional"></span></p>
                </div>
                <strong x-text="preview?.price"></strong>
            </div>

            <div x-show="preview?.status === 'completed'" class="agenda-hover-card__footer">
                <div><strong>Venta</strong><p x-text="preview?.paymentLabel"></p></div>
                <flux:icon.tag class="size-5" />
            </div>
            <div x-show="preview?.status === 'arrived'" class="agenda-hover-card__footer">
                <strong><span x-text="preview?.serviceCount"></span> servicio</strong>
                <flux:icon.calendar-days class="size-5" />
            </div>
        </div>
    </aside>

    @if ($this->selectedAppointment)
        @php
            $appointment = $this->selectedAppointment;
            $paidAmount = (float) $appointment->payments->where('status', 'paid')->sum('amount');
            $amountDue = max(0, (float) $appointment->price - $paidAmount);
            $statusLabels = [
                \App\Services\Agenda\AppointmentStatusCatalog::PENDING => 'Reservada',
                \App\Services\Agenda\AppointmentStatusCatalog::CONFIRMED => 'Confirmada',
                \App\Services\Agenda\AppointmentStatusCatalog::ARRIVED => 'Llegó',
                \App\Services\Agenda\AppointmentStatusCatalog::IN_PROGRESS => 'Iniciada',
                \App\Services\Agenda\AppointmentStatusCatalog::COMPLETED => 'Completada',
                \App\Services\Agenda\AppointmentStatusCatalog::NO_SHOW => 'No asistió',
                \App\Services\Agenda\AppointmentStatusCatalog::CANCELLED => 'Cancelada',
                \App\Services\Agenda\AppointmentStatusCatalog::RESCHEDULED => 'Reprogramada',
            ];
        @endphp

        <div class="agenda-detail-overlay" data-testid="appointment-detail" wire:key="appointment-detail-{{ $appointment->id }}" x-data="{ statusOpen: false, actionsOpen: false }" @keydown.escape.window="statusOpen || actionsOpen ? (statusOpen = false, actionsOpen = false) : $wire.closeDrawer()">
            <button type="button" class="agenda-detail-backdrop" aria-label="Cerrar detalle" wire:click="closeDrawer"></button>

            <aside class="agenda-detail-drawer">
                <div class="agenda-detail-rail">
                    <button type="button" aria-label="Cerrar" wire:click="closeDrawer"><flux:icon.x-mark class="size-6" /></button>
                    <button type="button" aria-label="Pantalla completa" wire:click="toggleFullscreen"><flux:icon.arrows-pointing-out class="size-5" /></button>
                    <button type="button" aria-label="Configuración"><flux:icon.cog-6-tooth class="size-5" /></button>
                </div>

                <section class="agenda-detail-client">
                    <div class="agenda-detail-client__profile">
                        <span class="agenda-detail-client__avatar">{{ mb_strtoupper(mb_substr($appointment->client->fullName(), 0, 1)) }}</span>
                        <h2>{{ $appointment->client->fullName() }}</h2>
                        <p>{{ $appointment->client->email ?: ($appointment->client->phone ?: 'Sin datos de contacto') }}</p>
                    </div>

                    <div class="agenda-detail-client__actions">
                        <div class="agenda-detail-actions-menu" @click.outside="actionsOpen = false">
                            <button type="button" @click="actionsOpen = ! actionsOpen">Acciones <flux:icon.chevron-down class="size-4" /></button>
                            <div x-show="actionsOpen" x-cloak x-transition class="agenda-detail-actions-menu__panel">
                                <button type="button" wire:click="openEditModal({{ $appointment->id }})" @click="actionsOpen = false">Editar cita</button>
                                <button type="button" wire:click="rescheduleSelected" @click="actionsOpen = false">Reprogramar una hora</button>
                            </div>
                        </div>
                        <button type="button" wire:click="viewSelectedClientProfile">Ver perfil</button>
                    </div>

                    <div class="agenda-detail-client__meta">
                        <p><flux:icon.user class="size-5" /> {{ $appointment->client->gender ?: 'Agregar género' }}</p>
                        <p><flux:icon.calendar-days class="size-5" /> {{ $appointment->client->birth_date?->translatedFormat('d M Y') ?? 'Agregar fecha de nacimiento' }}</p>
                        <p><flux:icon.plus-circle class="size-5" /> Creado {{ $appointment->client->created_at?->translatedFormat('d M Y') }}</p>
                    </div>
                </section>

                <section class="agenda-detail-booking">
                    <header class="agenda-detail-booking__header">
                        <div>
                            <h2>{{ ucfirst($appointment->starts_at->translatedFormat('D d M')) }} <flux:icon.chevron-down class="size-4" /></h2>
                            <p>{{ $appointment->starts_at->format('H:i') }} · No se repite</p>
                        </div>

                        <div class="agenda-detail-status" @click.outside="statusOpen = false">
                            <button type="button" @click="statusOpen = ! statusOpen" :aria-expanded="statusOpen">
                                {{ $statusLabels[$appointment->status->slug] ?? $appointment->status->name }}
                                <flux:icon.chevron-down class="size-4" />
                            </button>
                            <div x-show="statusOpen" x-cloak x-transition.origin.top.right class="agenda-detail-status__menu">
                                <button type="button" wire:click="changeStatusInline({{ $appointment->id }}, '{{ \App\Services\Agenda\AppointmentStatusCatalog::PENDING }}')" @click="statusOpen = false"><flux:icon.calendar-days class="size-5" /> Reservada @if ($appointment->status->slug === \App\Services\Agenda\AppointmentStatusCatalog::PENDING)<flux:icon.check class="size-5" />@endif</button>
                                <button type="button" wire:click="changeStatusInline({{ $appointment->id }}, '{{ \App\Services\Agenda\AppointmentStatusCatalog::CONFIRMED }}')" @click="statusOpen = false"><flux:icon.check class="size-5" /> Confirmada @if ($appointment->status->slug === \App\Services\Agenda\AppointmentStatusCatalog::CONFIRMED)<flux:icon.check class="size-5" />@endif</button>
                                <button type="button" wire:click="changeStatusInline({{ $appointment->id }}, '{{ \App\Services\Agenda\AppointmentStatusCatalog::ARRIVED }}')" @click="statusOpen = false"><flux:icon.map-pin class="size-5" /> Llegó @if ($appointment->status->slug === \App\Services\Agenda\AppointmentStatusCatalog::ARRIVED)<flux:icon.check class="size-5" />@endif</button>
                                <button type="button" wire:click="changeStatusInline({{ $appointment->id }}, '{{ \App\Services\Agenda\AppointmentStatusCatalog::IN_PROGRESS }}')" @click="statusOpen = false"><flux:icon.arrow-path class="size-5" /> Iniciada @if ($appointment->status->slug === \App\Services\Agenda\AppointmentStatusCatalog::IN_PROGRESS)<flux:icon.check class="size-5" />@endif</button>
                                <button type="button" wire:click="completeAppointment" @click="statusOpen = false"><flux:icon.check class="size-5" /> Completada @if ($appointment->status->slug === \App\Services\Agenda\AppointmentStatusCatalog::COMPLETED)<flux:icon.check class="size-5" />@endif</button>
                                <button type="button" class="is-danger" wire:click="markNoShow" @click="statusOpen = false"><flux:icon.eye class="size-5" /> No asistió @if ($appointment->status->slug === \App\Services\Agenda\AppointmentStatusCatalog::NO_SHOW)<flux:icon.check class="size-5" />@endif</button>
                                <button type="button" class="is-danger" wire:click="openCancellationConfirmation" @click="statusOpen = false"><flux:icon.x-mark class="size-5" /> Cancelar @if ($appointment->status->slug === \App\Services\Agenda\AppointmentStatusCatalog::CANCELLED)<flux:icon.check class="size-5" />@endif</button>
                            </div>
                        </div>
                    </header>

                    <div class="agenda-detail-booking__body">
                        <h3>Servicios</h3>
                        <article class="agenda-detail-service">
                            <div>
                                <strong>{{ $appointment->service->name }}</strong>
                                <p>{{ $appointment->starts_at->format('H:i') }} · {{ $this->serviceDurationLabel($appointment->duration_minutes) }} · {{ $appointment->professional?->fullName() ?? 'Cualquier miembro del equipo' }}</p>
                            </div>
                            <strong>PEN {{ number_format((float) $appointment->price, 0) }}</strong>
                        </article>
                        <button type="button" class="agenda-detail-add-service" wire:click="openEditModal({{ $appointment->id }})"><flux:icon.plus-circle class="size-5" /> Agregar servicio</button>

                        @if ($appointment->notes)
                            <div class="agenda-detail-note"><strong>Notas</strong><p>{{ $appointment->notes }}</p></div>
                        @endif
                    </div>

                    <footer class="agenda-detail-booking__footer">
                        <div class="agenda-detail-total">
                            <span>Total</span><span>PEN {{ number_format((float) $appointment->price, 0) }}</span>
                            <strong>Por pagar <flux:icon.chevron-right class="size-4" /></strong><strong>PEN {{ number_format($amountDue, 0) }}</strong>
                        </div>
                        <div class="agenda-detail-checkout">
                            <button type="button" class="agenda-detail-more" aria-label="Más opciones"><flux:icon.ellipsis-vertical class="size-5" /></button>
                            <button type="button" wire:click="checkoutSelectedAppointment">Checkout</button>
                        </div>
                    </footer>
                </section>
            </aside>
        </div>
    @endif

    @if ($cancellationPanelOpen && $this->selectedAppointment)
        @php
            $cancellationAppointment = $this->selectedAppointment;
            $cancellationReasons = [
                'none' => 'No se proporcionó motivo',
                'duplicate' => 'Cita duplicada',
                'appointment_made_by_mistake' => 'Cita creada por error',
                'client_not_available' => 'Cliente no disponible',
            ];
        @endphp

        <section
            class="agenda-cancellation-page"
            data-testid="cancellation-page"
            x-data="{ reasonOpen: false }"
            @keydown.escape.window="reasonOpen ? reasonOpen = false : $wire.closeCancellationConfirmation()"
        >
            <header class="agenda-cancellation-page__topbar">
                <button type="button" wire:click="closeCancellationConfirmation">Cerrar</button>
            </header>

            <main class="agenda-cancellation-page__main">
                <h1>¿Seguro que quieres cancelar?</h1>

                <div class="agenda-cancellation-layout">
                    <div class="agenda-cancellation-form">
                        <div class="agenda-cancellation-notice">
                            <flux:icon.information-circle class="size-5" />
                            <span>No se aplicó ninguna política a esta cita</span>
                        </div>

                        <label>Motivo de cancelación</label>
                        <div class="agenda-cancellation-select" @click.outside="reasonOpen = false">
                            <button type="button" @click="reasonOpen = ! reasonOpen" :aria-expanded="reasonOpen">
                                <span>{{ $cancellationReasons[$cancellationReason] ?? $cancellationReasons['appointment_made_by_mistake'] }}</span>
                                <flux:icon.chevron-down class="size-4" />
                            </button>

                            <div x-show="reasonOpen" x-cloak x-transition.origin.top class="agenda-cancellation-select__menu">
                                @foreach ($cancellationReasons as $reasonValue => $reasonLabel)
                                    <button
                                        type="button"
                                        wire:click="$set('cancellationReason', '{{ $reasonValue }}')"
                                        @click="reasonOpen = false"
                                    >
                                        <span>{{ $reasonLabel }}</span>
                                        @if ($cancellationReason === $reasonValue)
                                            <flux:icon.check class="size-5" />
                                        @endif
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <aside class="agenda-cancellation-card">
                        <h2>Detalles de cancelación</h2>
                        <div>
                            <span>Total de la cita</span>
                            <strong>PEN {{ number_format((float) $cancellationAppointment->price, 0) }}</strong>
                        </div>
                        <p>No se cobrará ninguna comisión</p>
                        <button type="button" wire:click="confirmCancellation" wire:loading.attr="disabled" wire:target="confirmCancellation">
                            <span wire:loading.remove wire:target="confirmCancellation">Cancelar cita</span>
                            <span wire:loading wire:target="confirmCancellation">Cancelando...</span>
                        </button>
                    </aside>
                </div>
            </main>
        </section>
    @endif

    <flux:modal name="schedule-block-form" class="w-full max-w-xl">
        <form wire:submit="saveScheduleBlock" class="space-y-6">
            <div>
                <flux:heading size="lg">Agregar tiempo bloqueado</flux:heading>
                <flux:text class="mt-1 text-sm text-zinc-500">Reserva un periodo no disponible en la agenda.</flux:text>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="blockStartsAt" label="Inicio *" type="datetime-local" />
                <flux:input wire:model="blockEndsAt" label="Fin *" type="datetime-local" />
                <flux:select wire:model="blockType" label="Tipo de bloqueo *" class="sm:col-span-2">
                    <option value="unavailable">No disponible</option>
                    <option value="personal">Asunto personal</option>
                    <option value="lunch_break">Descanso / almuerzo</option>
                    <option value="maintenance">Mantenimiento</option>
                </flux:select>
                <div class="sm:col-span-2">
                    <flux:textarea wire:model="blockReason" label="Motivo" rows="3" />
                </div>
                <div class="sm:col-span-2">
                    <flux:switch wire:model="blockAllDay" label="Todo el día" align="left" />
                </div>
            </div>

            <div class="flex justify-end gap-3 border-t border-zinc-700 pt-4">
                <flux:modal.close><flux:button type="button" variant="ghost">Cancelar</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary">Agregar bloqueo</flux:button>
            </div>
        </form>
    </flux:modal>

    @if ($appointmentPanelOpen)
        <div class="agenda-appointment-overlay" wire:key="appointment-panel" x-data @keydown.escape.window="$wire.closeModal()">
            <button type="button" class="agenda-appointment-backdrop" aria-label="Cerrar panel de cita" wire:click="closeModal"></button>

            <aside class="agenda-appointment-drawer" data-testid="appointment-panel">
                <div class="agenda-appointment-rail">
                    <button type="button" aria-label="Cerrar" wire:click="closeModal">
                        <flux:icon.x-mark class="size-6" />
                    </button>
                    <button type="button" aria-label="Pantalla completa" wire:click="toggleFullscreen">
                        <flux:icon.arrows-pointing-out class="size-5" />
                    </button>
                    <button type="button" aria-label="Ir a hoy" wire:click="today">
                        <flux:icon.cog-6-tooth class="size-5" />
                    </button>
                </div>

                <div class="agenda-appointment-stepbar">
                    <div class="agenda-step-icon">
                        <flux:icon.user-plus class="size-5" />
                    </div>
                    <strong>Agregar<br>cliente</strong>
                    <p>O déjelo vacío para clientes sin cita previa.</p>
                </div>

                <div class="agenda-appointment-content">
                    @if ($appointmentStep === 'picker')
                        <div class="agenda-service-step">
                            <h2>Seleccione un servicio</h2>

                            <label class="agenda-service-search">
                                <flux:icon.magnifying-glass class="size-5" />
                                <input
                                    type="search"
                                    wire:model.live.debounce.250ms="serviceSearch"
                                    placeholder="Buscar por nombre del servicio"
                                    aria-label="Buscar por nombre del servicio"
                                    autofocus
                                >
                            </label>

                            <div class="agenda-service-groups">
                                @forelse ($this->servicesCatalog->groupBy(fn ($service) => $service->category?->name ?? 'Otros') as $category => $services)
                                    <section class="agenda-service-group" wire:key="service-category-{{ md5($category) }}">
                                        <h3>
                                            {{ $category }}
                                            <span>{{ $services->count() }}</span>
                                        </h3>

                                        <div>
                                            @foreach ($services as $service)
                                                <button
                                                    type="button"
                                                    wire:key="appointment-service-{{ $service->id }}"
                                                    wire:click="selectAppointmentService({{ $service->id }})"
                                                >
                                                    <span>
                                                        <strong>{{ $service->name }}</strong>
                                                        <small>{{ $this->serviceDurationLabel($service->duration_minutes) }}</small>
                                                    </span>
                                                    <b>S/ {{ number_format((float) $service->price, 2) }}</b>
                                                </button>
                                            @endforeach
                                        </div>
                                    </section>
                                @empty
                                    <div class="agenda-service-empty">
                                        No encontramos servicios con “{{ $serviceSearch }}”.
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    @elseif ($appointmentStep === 'services')
                        <div class="agenda-wizard-stage">
                            <nav class="agenda-wizard-breadcrumb" aria-label="Progreso de la cita">
                                <strong>Servicios</strong>
                                <flux:icon.chevron-right class="size-4" />
                                <span>Tiempo</span>
                            </nav>

                            <h2>Servicios</h2>

                            <div class="agenda-selected-services">
                                @foreach ($this->selectedServices as $service)
                                    <article wire:key="selected-service-{{ $service->id }}">
                                        <div class="agenda-selected-service-info">
                                            <span>
                                                <strong>{{ $service->name }}</strong>
                                                <small>{{ $this->serviceDurationLabel($service->duration_minutes) }}</small>
                                            </span>
                                            <b>S/ {{ number_format((float) $service->price, 2) }}</b>
                                        </div>

                                        <div class="agenda-service-member-row">
                                            <label>
                                                <flux:icon.user class="size-4" />
                                                <select wire:model="selectedServiceProfessionals.{{ $service->id }}" aria-label="Profesional para {{ $service->name }}">
                                                    <option value="">Cualquier miembro del equipo</option>
                                                    @foreach ($this->professionalsCatalog as $professional)
                                                        <option value="{{ $professional->id }}">{{ $professional->fullName() }}</option>
                                                    @endforeach
                                                </select>
                                                <flux:icon.chevron-down class="size-4" />
                                            </label>
                                            <button type="button" wire:click="removeAppointmentService({{ $service->id }})" aria-label="Quitar {{ $service->name }}">
                                                <flux:icon.x-mark class="size-4" />
                                            </button>
                                        </div>
                                    </article>
                                @endforeach
                            </div>

                            <button type="button" class="agenda-add-service-button" wire:click="showServiceStep">
                                <flux:icon.plus-circle class="size-4" />
                                Agregar servicio
                            </button>

                            <div class="agenda-wizard-footer">
                                <div class="agenda-wizard-total">
                                    <span>Total</span>
                                    <b>S/ {{ number_format($this->selectedServicesTotal, 2) }}</b>
                                    <strong>Para pagar <flux:icon.chevron-right class="size-4" /></strong>
                                    <strong>S/ {{ number_format($this->selectedServicesTotal, 2) }}</strong>
                                </div>
                                <button type="button" wire:click="continueToAppointmentTime">Continuar</button>
                            </div>
                        </div>
                    @elseif ($appointmentStep === 'time')
                        <div class="agenda-wizard-stage agenda-time-stage">
                            <nav class="agenda-wizard-breadcrumb" aria-label="Progreso de la cita">
                                <button type="button" wire:click="showServicesSummary">Servicios</button>
                                <flux:icon.chevron-right class="size-4" />
                                <strong>Tiempo</strong>
                            </nav>

                            <h2>Seleccione una hora</h2>

                            <div class="agenda-time-team">
                                <flux:icon.user class="size-4" />
                                <span>Cualquier miembro del equipo</span>
                                <flux:icon.chevron-down class="size-4" />
                            </div>

                            <div class="agenda-date-heading">
                                <strong>{{ ucfirst(\Carbon\CarbonImmutable::parse($appointmentTimeDate)->translatedFormat('F')) }}</strong>
                            </div>

                            <div class="agenda-date-options">
                                @foreach ($this->appointmentDateOptions as $dateOption)
                                    <button
                                        type="button"
                                        wire:key="appointment-date-{{ $dateOption['date'] }}"
                                        wire:click="selectAppointmentDate('{{ $dateOption['date'] }}')"
                                        @class(['is-selected' => $dateOption['is_selected']])
                                    >
                                        <strong>{{ $dateOption['day'] }}</strong>
                                        <small>{{ $dateOption['weekday'] }}</small>
                                    </button>
                                @endforeach
                            </div>

                            <div class="agenda-slots-heading">
                                <strong>Horarios disponibles</strong>
                                <button type="button">
                                    <flux:icon.calendar-days class="size-4" />
                                    Seleccione del calendario
                                </button>
                            </div>

                            <div class="agenda-time-slots">
                                @forelse ($slotSearchResults as $slot)
                                    <button
                                        type="button"
                                        wire:key="appointment-slot-{{ md5($slot['starts_at']) }}"
                                        wire:click="selectAppointmentSlot('{{ $slot['starts_at'] }}', '{{ $slot['ends_at'] }}')"
                                        @class(['is-selected' => $selectedSlotStart === $slot['starts_at']])
                                    >
                                        {{ $slot['label'] }}
                                    </button>
                                @empty
                                    <div class="agenda-service-empty">No hay horarios disponibles para este día.</div>
                                @endforelse
                            </div>

                            <div class="agenda-wizard-footer">
                                <div class="agenda-wizard-total">
                                    <span>Total</span>
                                    <b>S/ {{ number_format($this->selectedServicesTotal, 2) }}</b>
                                    <strong>Para pagar <flux:icon.chevron-right class="size-4" /></strong>
                                    <strong>S/ {{ number_format($this->selectedServicesTotal, 2) }}</strong>
                                </div>
                                <button
                                    type="button"
                                    wire:click="continueToAppointmentDetails"
                                    @disabled($selectedSlotStart === '')
                                >
                                    Continuar
                                </button>
                            </div>
                        </div>
                    @elseif ($appointmentStep === 'summary')
                        <form
                            wire:submit="save"
                            class="agenda-booking-summary"
                            data-testid="appointment-summary"
                        >
                            <header class="agenda-summary-header">
                                <button type="button" wire:click="showAppointmentTime" aria-label="Volver a seleccionar hora">
                                    <h2>{{ ucfirst(\Carbon\CarbonImmutable::parse($form->starts_at)->translatedFormat('D j M')) }}</h2>
                                    <flux:icon.chevron-down class="size-4" />
                                </button>
                                <p>{{ \Carbon\CarbonImmutable::parse($form->starts_at)->format('H:i') }} · No se repite</p>
                            </header>

                            <div class="agenda-summary-body">
                                <h3>Servicios</h3>

                                <div class="agenda-summary-services">
                                    @foreach ($this->appointmentSummaryServices as $summaryService)
                                        <article wire:key="appointment-summary-service-{{ $summaryService['service']->id }}">
                                            <div>
                                                <strong>{{ $summaryService['service']->name }}</strong>
                                                <p>
                                                    {{ $summaryService['starts_at']->format('H:i') }}
                                                    · {{ $this->serviceDurationLabel($summaryService['service']->duration_minutes) }}
                                                    · {{ $summaryService['professional_name'] }}
                                                </p>
                                            </div>
                                            <b>S/ {{ number_format((float) $summaryService['service']->price, 2) }}</b>
                                        </article>
                                    @endforeach
                                </div>

                                <div class="agenda-summary-add-row">
                                    <button type="button" wire:click="showServiceStep" class="agenda-add-service-button">
                                        <flux:icon.plus-circle class="size-4" />
                                        Agregar servicio
                                    </button>
                                    <span>{{ $this->serviceDurationLabel($this->selectedServicesDuration) }}</span>
                                </div>
                            </div>

                            <footer class="agenda-summary-footer">
                                <div class="agenda-wizard-total">
                                    <span>Total</span>
                                    <b>S/ {{ number_format($this->selectedServicesTotal, 2) }}</b>
                                    <strong>Para pagar <flux:icon.chevron-right class="size-4" /></strong>
                                    <strong>S/ {{ number_format($this->selectedServicesTotal, 2) }}</strong>
                                </div>

                                <div class="agenda-summary-actions">
                                    <button type="button" class="agenda-summary-more" aria-label="Más opciones">
                                        <flux:icon.ellipsis-vertical class="size-5" />
                                    </button>
                                    <button type="button" class="agenda-summary-checkout" wire:click="checkout">
                                        Checkout
                                    </button>
                                    <button type="submit" class="agenda-summary-save" wire:loading.attr="disabled" wire:target="save">
                                        <span wire:loading.remove wire:target="save">Guardar</span>
                                        <span wire:loading wire:target="save">Guardando...</span>
                                    </button>
                                </div>
                            </footer>
                        </form>
                    @else
                        <form wire:submit="save" class="agenda-appointment-details">
                            <div class="agenda-details-header">
                                @if (! $form->appointmentId)
                                    <button type="button" wire:click="showServiceStep" aria-label="Volver a servicios">
                                        <flux:icon.chevron-left class="size-5" />
                                    </button>
                                @endif
                                <div>
                                    <span>{{ $form->appointmentId ? 'Editar cita' : 'Nueva cita' }}</span>
                                    <h2>{{ $form->title }}</h2>
                                </div>
                            </div>

                            <div class="agenda-details-form">
                                <flux:select wire:model="form.client_id" label="Cliente *">
                                    <option value="">Seleccionar cliente</option>
                                    @foreach ($this->clientsCatalog as $client)
                                        <option value="{{ $client->id }}">{{ $client->fullName() }}</option>
                                    @endforeach
                                </flux:select>
                                <flux:select wire:model="form.branch_id" label="Sucursal *">
                                    <option value="">Seleccionar sucursal</option>
                                    @foreach ($this->branches as $branch)
                                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                    @endforeach
                                </flux:select>
                                <flux:input wire:model="form.starts_at" label="Inicio *" type="datetime-local" />
                                <flux:input wire:model="form.ends_at" label="Fin *" type="datetime-local" />
                                <flux:select wire:model="form.professional_id" label="Profesional">
                                    <option value="">Sin asignar</option>
                                    @foreach ($this->professionalsCatalog as $professional)
                                        <option value="{{ $professional->id }}">{{ $professional->fullName() }}</option>
                                    @endforeach
                                </flux:select>
                                <flux:select wire:model="form.resource_id" label="Recurso">
                                    <option value="">Sin recurso</option>
                                    @foreach ($this->resourcesCatalog as $resource)
                                        <option value="{{ $resource->id }}">{{ $resource->name }}</option>
                                    @endforeach
                                </flux:select>
                                <flux:select wire:model="form.status_slug" label="Estado *">
                                    @foreach ($this->appointmentStatuses as $status)
                                        <option value="{{ $status->slug }}">{{ $status->name }}</option>
                                    @endforeach
                                </flux:select>
                                <flux:input wire:model="form.price" label="Precio *" type="number" step="0.01" min="0" />
                                <div class="agenda-details-notes">
                                    <flux:textarea wire:model="form.notes" label="Notas" rows="3" />
                                </div>
                            </div>

                            <div class="agenda-details-footer">
                                <button type="button" wire:click="closeModal">Cancelar</button>
                                <button type="submit">{{ $form->appointmentId ? 'Guardar cambios' : 'Crear cita' }}</button>
                            </div>
                        </form>
                    @endif
                </div>
            </aside>
        </div>
    @endif
</section>
