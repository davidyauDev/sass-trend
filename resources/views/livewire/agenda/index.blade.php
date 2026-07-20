<section
    @class(['agenda-page', 'fixed inset-0 z-50' => $isFullscreen])
    x-data="agendaAppointmentPreview({
        panelReady: @js($appointmentPanelLoaded),
        panelOpen: @js($appointmentPanelOpen),
    })"
    wire:init="preloadAppointmentPanel"
    @appointment-panel-preloaded.window="appointmentPanelReady()"
    @appointment-panel-opened.window="appointmentPanelOpened()"
    @appointment-panel-closed.window="appointmentPanelClosed()"
    wire:poll.30s="pollAgenda"
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

            <button
                type="button"
                class="agenda-primary-button"
                @click="openAppointmentPanel(() => $wire.openCreateModal())"
                x-bind:disabled="appointmentOpening"
            >
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
                                    <button type="button" @click="quickOpen = false; openAppointmentPanel(() => $wire.openCreateModalForDate('{{ $cell['key'] }}'))">
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
                                        @mouseenter="showAppointmentPreview($event, @js($this->appointmentPreviewData($appointment)))"
                                        @mouseleave="scheduleAppointmentPreviewHide()"
                                        @click.stop="openAppointmentDetail(() => $wire.openDrawer({{ $appointment->id }}))"
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
                                        @if ($appointment->latestNote)
                                            <flux:icon.chat-bubble-oval-left class="agenda-event-note-icon size-4" />
                                        @endif
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
                    @mouseenter="showAppointmentPreview($event, @js($this->appointmentPreviewData($appointment)))"
                    @mouseleave="scheduleAppointmentPreviewHide()"
                    @click="openAppointmentDetail(() => $wire.openDrawer({{ $appointment->id }}))"
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
                                    @mouseenter="showAppointmentPreview($event, @js($this->appointmentPreviewData($appointment)))"
                                    @mouseleave="scheduleAppointmentPreviewHide()"
                                    @click="openAppointmentDetail(() => $wire.openDrawer({{ $appointment->id }}))"
                                >
                                    <span><strong>{{ $appointment->starts_at->format('H:i') }} - {{ $appointment->ends_at->format('H:i') }} {{ $appointment->client->fullName() }}</strong><small>{{ $appointment->service->name }}</small></span>
                                    @switch($appointment->status->slug)
                                        @case(\App\Services\Agenda\AppointmentStatusCatalog::COMPLETED)<flux:icon.tag class="size-4" />@break
                                        @case(\App\Services\Agenda\AppointmentStatusCatalog::NO_SHOW)<flux:icon.eye-slash class="size-4" />@break
                                        @case(\App\Services\Agenda\AppointmentStatusCatalog::CONFIRMED)<flux:icon.hand-thumb-up class="size-4" />@break
                                        @case(\App\Services\Agenda\AppointmentStatusCatalog::ARRIVED)<flux:icon.map-pin class="size-4" />@break
                                        @case(\App\Services\Agenda\AppointmentStatusCatalog::IN_PROGRESS)<flux:icon.play class="size-4" />@break
                                    @endswitch
                                    @if ($appointment->latestNote)
                                        <flux:icon.chat-bubble-oval-left class="agenda-event-note-icon size-4" />
                                    @endif
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
                                            @mouseenter="showAppointmentPreview($event, @js($this->appointmentPreviewData($appointment)))"
                                            @mouseleave="scheduleAppointmentPreviewHide()"
                                            @click="openAppointmentDetail(() => $wire.openDrawer({{ $appointment->id }}))"
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
                                            @if ($appointment->latestNote)
                                                <flux:icon.chat-bubble-oval-left class="agenda-event-note-icon size-4" />
                                            @endif
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
            <button type="button" @click="openAppointmentPanel(() => quickSlot.kind === 'date' ? $wire.openCreateModalForDateAndProfessional(quickSlot.date, quickSlot.professionalId) : $wire.openCreateModalForSlot(quickSlot.dateTime, quickSlot.professionalId)); closeDaySlotMenu()">
                <flux:icon.calendar-days class="size-5" />
                <span>Agregar cita</span>
            </button>
            <button type="button" @click="openAppointmentPanel(() => quickSlot.kind === 'date' ? $wire.openCreateModalForDateAndProfessional(quickSlot.date, quickSlot.professionalId) : $wire.openCreateModalForSlot(quickSlot.dateTime, quickSlot.professionalId)); closeDaySlotMenu()">
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

            <p x-show="preview?.note" x-text="preview?.note" class="agenda-hover-card__note"></p>

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

    <div
        x-show="detailOpening"
        x-cloak
        class="agenda-detail-overlay agenda-detail-overlay--loading"
        aria-live="polite"
        aria-label="Cargando detalle de la cita"
    >
        <div class="agenda-detail-backdrop"></div>

        <aside class="agenda-detail-drawer" aria-busy="true">
            <div class="agenda-detail-rail">
                <span class="agenda-appointment-skeleton agenda-appointment-skeleton--circle"></span>
            </div>

            <section class="agenda-detail-client agenda-detail-loading-client">
                <span class="agenda-appointment-skeleton agenda-appointment-skeleton--detail-avatar"></span>
                <span class="agenda-appointment-skeleton agenda-appointment-skeleton--detail-name"></span>
                <span class="agenda-appointment-skeleton agenda-appointment-skeleton--detail-copy"></span>
            </section>

            <section class="agenda-detail-booking agenda-detail-loading-booking">
                <span class="agenda-appointment-skeleton agenda-appointment-skeleton--detail-heading"></span>
                <span class="agenda-appointment-skeleton agenda-appointment-skeleton--detail-card"></span>
                <span class="agenda-appointment-skeleton agenda-appointment-skeleton--detail-card"></span>
            </section>
        </aside>
    </div>

    @if ($this->selectedAppointment)
        @php
            $appointment = $this->selectedAppointment;
            $paidAmount = (float) $appointment->payments->where('status', 'paid')->sum('amount');
            $amountDue = max(0, (float) $appointment->price - $paidAmount);
            $isNoShow = $appointment->status->slug === \App\Services\Agenda\AppointmentStatusCatalog::NO_SHOW;
            $isConfirmed = $appointment->status->slug === \App\Services\Agenda\AppointmentStatusCatalog::CONFIRMED;
            $hasArrived = $appointment->status->slug === \App\Services\Agenda\AppointmentStatusCatalog::ARRIVED;
            $isInProgress = $appointment->status->slug === \App\Services\Agenda\AppointmentStatusCatalog::IN_PROGRESS;
            $displayNote = $appointment->latestNote?->note ?? $appointment->getAttribute('notes');
            $quickSaleServices = collect();
            $checkoutServiceCategories = collect();
            $checkoutProductCategories = collect();

            if ($checkoutCatalogLoaded) {
                $quickSaleServices = $this->servicesCatalog
                    ->reject(fn (array $service): bool => $service['id'] === $appointment->service_id)
                    ->prepend([
                        'id' => $appointment->service_id,
                        'name' => $appointment->service->name,
                        'duration_minutes' => $appointment->duration_minutes,
                        'price' => (float) $appointment->price,
                        'category_name' => 'Servicios',
                    ])
                    ->take(3);
                $checkoutServiceCategories = $this->servicesCatalog->groupBy('category_name');
                $checkoutProductCategories = $this->checkoutProductsCatalog->groupBy('category_name');
            }
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

        <div
            class="agenda-detail-overlay"
            x-data="{
                statusOpen: false,
                actionsOpen: false,
                quickActionsOpen: false,
                noteModalOpen: false,
                checkoutPanelOpen: false,
                checkoutCatalogLoaded: @js($checkoutCatalogLoaded),
                checkoutClosing: false,
                customTipModalOpen: false,
                cashModalOpen: false,
                cashInput: '0',
                cashPaymentAmount: 0,
                customTipMode: 'amount',
                customTipInput: '0',
                selectedTip: 'none',
                checkoutCartItems: [],
                editItemModalOpen: false,
                editingCartItemKey: null,
                editItemPrice: '0',
                editItemQuantity: 1,
                checkoutStep: 'tip',
                cartCatalogView: 'home',
                catalogCategory: null,
                cartSearch: '',
                catalogSearch: '',
                closing: false,
                async openCheckoutPanel() {
                    this.selectedTip = 'none';
                    this.checkoutCartItems = [];
                    this.editItemModalOpen = false;
                    this.editingCartItemKey = null;
                    this.cashModalOpen = false;
                    this.cashInput = '0';
                    this.cashPaymentAmount = 0;
                    this.checkoutStep = 'tip';
                    this.cartCatalogView = 'home';
                    this.catalogCategory = null;
                    this.cartSearch = '';
                    this.catalogSearch = '';
                    this.checkoutClosing = false;
                    this.checkoutPanelOpen = true;

                    if (! this.checkoutCatalogLoaded) {
                        await this.$wire.loadCheckoutCatalog();
                        this.checkoutCatalogLoaded = true;
                    }
                },
                openCartCatalog(catalog) {
                    this.catalogSearch = '';
                    this.catalogCategory = null;
                    this.cartCatalogView = catalog;
                },
                openCatalogCategory(category) {
                    this.catalogSearch = '';
                    this.catalogCategory = category;
                },
                backFromCartCatalog() {
                    if (this.catalogCategory !== null) {
                        this.catalogCategory = null;
                        this.catalogSearch = '';

                        return;
                    }

                    this.cartCatalogView = 'home';
                },
                addCheckoutItem(item) {
                    this.checkoutCartItems.push({
                        ...item,
                        key: `${item.type}-${item.id}-${Date.now()}-${Math.random()}`,
                        quantity: 1,
                        price: Number(item.price),
                    });
                    this.cartCatalogView = 'home';
                    this.catalogCategory = null;
                    this.catalogSearch = '';
                },
                removeCheckoutItem(key) {
                    this.checkoutCartItems = this.checkoutCartItems.filter((item) => item.key !== key);
                },
                openCheckoutItemEditor(item) {
                    this.editingCartItemKey = item.key;
                    this.editItemPrice = Number(item.price).toFixed(2);
                    this.editItemQuantity = Number(item.quantity);
                    this.editItemModalOpen = true;
                },
                changeEditItemQuantity(change) {
                    this.editItemQuantity = Math.max(1, Number(this.editItemQuantity) + Number(change));
                },
                applyCheckoutItemEdit() {
                    const item = this.checkoutCartItems.find((entry) => entry.key === this.editingCartItemKey);

                    if (! item) {
                        return;
                    }

                    item.price = Math.max(0, Number(this.editItemPrice) || 0);
                    item.quantity = Math.max(1, Number(this.editItemQuantity) || 1);
                    this.checkoutCartItems = [...this.checkoutCartItems];
                    this.editItemModalOpen = false;
                },
                removeEditingCheckoutItem() {
                    this.removeCheckoutItem(this.editingCartItemKey);
                    this.editItemModalOpen = false;
                },
                cartItemsSubtotal() {
                    return this.checkoutCartItems.reduce(
                        (total, item) => total + (Number(item.price) * Number(item.quantity)),
                        0,
                    );
                },
                checkoutSubtotal(base) {
                    return Number(base) + this.cartItemsSubtotal();
                },
                closeCheckoutPanel() {
                    if (this.checkoutClosing) {
                        return;
                    }

                    this.checkoutClosing = true;
                    window.setTimeout(() => {
                        this.checkoutPanelOpen = false;
                        this.checkoutClosing = false;
                    }, 300);
                },
                openCustomTipModal() {
                    if (this.selectedTip !== 'custom') {
                        this.customTipMode = 'amount';
                        this.customTipInput = '0';
                    }

                    this.customTipModalOpen = true;
                },
                appendCustomTip(value) {
                    if (value === '.' && this.customTipInput.includes('.')) {
                        return;
                    }

                    if (this.customTipInput === '0' && value !== '.') {
                        this.customTipInput = value;

                        return;
                    }

                    const decimalPart = this.customTipInput.split('.')[1] ?? '';
                    if (this.customTipInput.includes('.') && decimalPart.length >= 2) {
                        return;
                    }

                    this.customTipInput += value;
                },
                deleteCustomTipDigit() {
                    this.customTipInput = this.customTipInput.length > 1
                        ? this.customTipInput.slice(0, -1)
                        : '0';
                },
                customTipAmount(subtotal) {
                    const value = Number(this.customTipInput) || 0;

                    return this.customTipMode === 'percent'
                        ? Number(subtotal) * Math.min(value, 100) / 100
                        : value;
                },
                customTipPercentage(subtotal) {
                    if (Number(subtotal) <= 0) {
                        return 0;
                    }

                    return this.customTipMode === 'percent'
                        ? Math.min(Number(this.customTipInput) || 0, 100)
                        : this.customTipAmount(subtotal) * 100 / Number(subtotal);
                },
                customTipPercentageLabel(subtotal) {
                    const percentage = this.customTipPercentage(subtotal);
                    const decimals = Number.isInteger(percentage) ? 0 : 1;

                    return `${percentage.toFixed(decimals)}% tip`;
                },
                confirmCustomTip() {
                    if (Number(this.customTipInput) <= 0) {
                        return;
                    }

                    this.selectedTip = 'custom';
                    this.customTipModalOpen = false;
                },
                openCashModal(total) {
                    this.cashInput = String(Math.ceil(Number(total)));
                    this.cashModalOpen = true;
                },
                appendCashDigit(value) {
                    if (value === '.' && this.cashInput.includes('.')) {
                        return;
                    }

                    if (this.cashInput === '0' && value !== '.') {
                        this.cashInput = value;

                        return;
                    }

                    const decimalPart = this.cashInput.split('.')[1] ?? '';
                    if (this.cashInput.includes('.') && decimalPart.length >= 2) {
                        return;
                    }

                    this.cashInput += value;
                },
                deleteCashDigit() {
                    this.cashInput = this.cashInput.length > 1 ? this.cashInput.slice(0, -1) : '0';
                },
                cashSuggestion(total, increment = 0) {
                    return Math.ceil(Number(total) / 5) * 5 + Number(increment);
                },
                cashLeftToPay(total) {
                    return Math.max(Number(total) - (Number(this.cashInput) || 0), 0);
                },
                cashChange(total, payment = null) {
                    const received = payment === null ? Number(this.cashInput) || 0 : Number(payment);

                    return Math.max(received - Number(total), 0);
                },
                confirmCashPayment() {
                    if (Number(this.cashInput) <= 0) {
                        return;
                    }

                    this.cashPaymentAmount = Number(this.cashInput);
                    this.cashModalOpen = false;
                },
                tipAmount(subtotal) {
                    const rates = { none: 0, ten: 0.10, eighteen: 0.18, twentyFive: 0.25, custom: 0 };

                    return this.selectedTip === 'custom'
                        ? this.customTipAmount(subtotal)
                        : Number(subtotal) * (rates[this.selectedTip] ?? 0);
                },
                percentageAmount(subtotal, rate) {
                    return Number(subtotal) * Number(rate);
                },
                checkoutTotal(subtotal) {
                    const checkoutSubtotal = this.checkoutSubtotal(subtotal);

                    return checkoutSubtotal + this.tipAmount(checkoutSubtotal);
                },
                money(amount) {
                    return `PEN ${Number(amount).toFixed(2)}`;
                },
                closeDetail() {
                    if (this.closing) {
                        return;
                    }

                    this.closing = true;
                    window.setTimeout(() => this.$wire.closeDrawer(), 300);
                },
            }"
            x-bind:class="{ 'is-closing': closing }"
            @appointment-note-added.window="noteModalOpen = false; quickActionsOpen = false"
            data-testid="appointment-detail"
            wire:key="appointment-detail-{{ $appointment->id }}"
            @keydown.escape.window="cashModalOpen
                ? (cashModalOpen = false)
                : editItemModalOpen
                ? (editItemModalOpen = false)
                : customTipModalOpen
                ? (customTipModalOpen = false)
                : checkoutPanelOpen
                ? closeCheckoutPanel()
                : noteModalOpen
                ? (noteModalOpen = false)
                : statusOpen || actionsOpen || quickActionsOpen
                ? (statusOpen = false, actionsOpen = false, quickActionsOpen = false)
                : closeDetail()"
        >
            <button type="button" class="agenda-detail-backdrop" aria-label="Cerrar detalle" @click="closeDetail()"></button>

            <aside class="agenda-detail-drawer">
                <div class="agenda-detail-rail">
                    <button type="button" aria-label="Cerrar" x-bind:disabled="closing" @click="closeDetail()"><flux:icon.x-mark class="size-6" /></button>
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
                    <header @class([
                        'agenda-detail-booking__header',
                        'agenda-detail-booking__header--confirmed' => $isConfirmed,
                        'agenda-detail-booking__header--arrived' => $hasArrived,
                        'agenda-detail-booking__header--in-progress' => $isInProgress,
                        'agenda-detail-booking__header--no-show' => $isNoShow,
                    ])>
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
                        @unless ($isNoShow)
                            <button type="button" class="agenda-detail-add-service" wire:click="openEditModal({{ $appointment->id }})"><flux:icon.plus-circle class="size-5" /> Agregar servicio</button>
                        @endunless

                        @if ($displayNote)
                            <div class="agenda-detail-note">
                                <strong>Notas</strong>
                                <p>{{ $displayNote }}</p>
                            </div>
                        @endif
                    </div>

                    <footer class="agenda-detail-booking__footer">
                        <div class="agenda-detail-total">
                            <span>Total</span><span>PEN {{ number_format((float) $appointment->price, 0) }}</span>
                            <strong>Por pagar <flux:icon.chevron-right class="size-4" /></strong><strong>PEN {{ number_format($amountDue, 0) }}</strong>
                        </div>
                        <div class="agenda-detail-checkout">
                            <div class="agenda-detail-quick-actions" @click.outside="quickActionsOpen = false">
                                <button
                                    type="button"
                                    class="agenda-detail-more"
                                    aria-label="Más opciones"
                                    x-bind:aria-expanded="quickActionsOpen"
                                    @click="quickActionsOpen = ! quickActionsOpen"
                                >
                                    <flux:icon.ellipsis-vertical class="size-5" />
                                </button>

                                <div
                                    x-show="quickActionsOpen"
                                    x-cloak
                                    x-transition.opacity.scale.origin.bottom.left
                                    class="agenda-detail-quick-actions__menu"
                                >
                                    <strong>Acciones rápidas</strong>
                                    <button type="button" @click="quickActionsOpen = false; noteModalOpen = true"><flux:icon.clipboard-document-list class="size-4" /> Añade una nota</button>
                                    <button type="button"><flux:icon.document-text class="size-4" /> Agregar un formulario</button>
                                    <span class="agenda-detail-quick-actions__divider"></span>
                                    <button type="button">Ver actividad de citas</button>
                                    <button type="button">Establecer como repetitivo</button>
                                    <button type="button">Agregar a la cita grupal</button>
                                    <button type="button">Reservar de nuevo</button>
                                    <span class="agenda-detail-quick-actions__divider"></span>
                                    <button type="button" wire:click="openEditModal({{ $appointment->id }})" @click="quickActionsOpen = false">Reprogramar</button>
                                    <button type="button" class="is-danger" wire:click="markNoShow" @click="quickActionsOpen = false">No se presentó</button>
                                    <button type="button" class="is-danger" wire:click="openCancellationConfirmation" @click="quickActionsOpen = false">Cancelar</button>
                                </div>
                            </div>
                            @if ($isNoShow)
                                <button type="button" class="agenda-detail-done" x-bind:disabled="closing" @click="closeDetail()">Done</button>
                            @else
                                <button type="button" @click="openCheckoutPanel()">Checkout</button>
                            @endif
                        </div>
                    </footer>
                </section>
            </aside>

            <div x-show="noteModalOpen" x-cloak class="agenda-note-modal" role="dialog" aria-modal="true" aria-labelledby="agenda-note-modal-title">
                <button type="button" class="agenda-note-modal__backdrop" aria-label="Cerrar nota" @click="noteModalOpen = false; $wire.set('noteDraft', '')"></button>

                <form class="agenda-note-modal__dialog" wire:submit="addNote">
                    <header>
                        <h2 id="agenda-note-modal-title">Añade una nota</h2>
                        <button type="button" aria-label="Cerrar" @click="noteModalOpen = false; $wire.set('noteDraft', '')">
                            <flux:icon.x-mark class="size-5" />
                        </button>
                    </header>

                    <textarea
                        wire:model="noteDraft"
                        required
                        maxlength="2000"
                        placeholder="Introduzca aquí cualquier instrucción especial o detalle sobre la cita."
                    ></textarea>
                    @error('noteDraft') <p class="agenda-note-modal__error">{{ $message }}</p> @enderror
                    <p class="agenda-note-modal__help">Esta nota solo será visible para los miembros de tu equipo.</p>

                    <footer>
                        <button type="submit">Ahorrar</button>
                    </footer>
                </form>
            </div>

            <template x-if="checkoutPanelOpen">
                <div
                    class="agenda-checkout-overlay"
                    x-bind:class="{ 'is-closing': checkoutClosing }"
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="agenda-checkout-title"
                >
                <button type="button" class="agenda-checkout-backdrop" aria-label="Cerrar checkout" @click="closeCheckoutPanel()"></button>

                <aside class="agenda-checkout-rail">
                    <button type="button" aria-label="Cerrar checkout" x-bind:disabled="checkoutClosing" @click="closeCheckoutPanel()">
                        <flux:icon.x-mark class="size-6" />
                    </button>
                </aside>

                <section class="agenda-checkout-drawer">
                    <main class="agenda-checkout-main">
                        <nav
                            x-show="checkoutStep !== 'cart' || cartCatalogView === 'home'"
                            class="agenda-checkout-breadcrumb"
                            aria-label="Checkout progress"
                        >
                            <button type="button" x-bind:class="{ 'is-active': checkoutStep === 'cart' }" @click="checkoutStep = 'cart'">Cart</button>
                            <flux:icon.chevron-right class="size-4" />
                            <button type="button" x-bind:class="{ 'is-active': checkoutStep === 'tip' }" @click="checkoutStep = 'tip'">Tip</button>
                            <flux:icon.chevron-right class="size-4" />
                            <button type="button" x-bind:class="{ 'is-active': checkoutStep === 'payment' }" @click="checkoutStep = 'payment'">Payment</button>
                        </nav>

                        <div x-show="checkoutStep === 'tip'">
                            <h2 id="agenda-checkout-title">Select tip</h2>
                            <p class="agenda-checkout-intro">Select an amount for {{ $appointment->professional?->fullName() ?? 'your professional' }}</p>

                            <div class="agenda-checkout-tips">
                                <button type="button" x-bind:class="{ 'is-selected': selectedTip === 'none' }" @click="selectedTip = 'none'">
                                    <strong>No tip</strong>
                                </button>
                                <button type="button" x-bind:class="{ 'is-selected': selectedTip === 'ten' }" @click="selectedTip = 'ten'">
                                    <strong>10%</strong>
                                    <span x-text="money(percentageAmount(checkoutSubtotal({{ (float) $appointment->price }}), 0.10))"></span>
                                </button>
                                <button type="button" x-bind:class="{ 'is-selected': selectedTip === 'eighteen' }" @click="selectedTip = 'eighteen'">
                                    <strong>18%</strong>
                                    <span x-text="money(percentageAmount(checkoutSubtotal({{ (float) $appointment->price }}), 0.18))"></span>
                                </button>
                                <button type="button" x-bind:class="{ 'is-selected': selectedTip === 'twentyFive' }" @click="selectedTip = 'twentyFive'">
                                    <strong>25%</strong>
                                    <span x-text="money(percentageAmount(checkoutSubtotal({{ (float) $appointment->price }}), 0.25))"></span>
                                </button>
                                <button type="button" x-bind:class="{ 'is-selected': selectedTip === 'custom' }" @click="openCustomTipModal()">
                                    <flux:icon.plus-circle class="size-5" />
                                    <strong>Custom tip</strong>
                                </button>
                            </div>
                        </div>

                        <div x-show="checkoutStep === 'cart'" x-cloak class="agenda-checkout-cart">
                            <div x-show="cartCatalogView === 'home'">
                                <h2>Add to cart</h2>

                                <label class="agenda-checkout-search">
                                    <flux:icon.magnifying-glass class="size-5" />
                                    <input type="search" x-model="cartSearch" placeholder="Search">
                                </label>

                                <div class="agenda-checkout-categories">
                                    <button type="button"><flux:icon.calendar-days class="size-5" /><span>Appointments</span></button>
                                    <button type="button" @click="openCartCatalog('services')"><flux:icon.scissors class="size-5" /><span>Services</span></button>
                                    <button type="button" @click="openCartCatalog('products')"><flux:icon.archive-box class="size-5" /><span>Products</span></button>
                                    <button type="button"><flux:icon.rectangle-stack class="size-5" /><span>Packages</span></button>
                                    <button type="button"><flux:icon.users class="size-5" /><span>Memberships</span></button>
                                    <button type="button"><flux:icon.gift class="size-5" /><span>Gift cards</span></button>
                                </div>

                                <div class="agenda-checkout-quick-sale__heading"><strong>Quick sale</strong><button type="button">Edit</button></div>
                                <div class="agenda-checkout-quick-sale">
                                    @foreach ($quickSaleServices as $quickService)
                                        <button
                                            type="button"
                                            x-show="cartSearch === '' || String(@js(mb_strtolower($quickService['name']))).includes(cartSearch.toLowerCase())"
                                        >
                                            <strong>{{ $quickService['name'] }}</strong>
                                            <span>PEN {{ number_format((float) $quickService['price'], 0) }}</span>
                                        </button>
                                    @endforeach
                                </div>
                            </div>

                            <section x-show="cartCatalogView === 'services'" x-cloak class="agenda-checkout-catalog-detail">
                                <header><button type="button" aria-label="Volver" @click="backFromCartCatalog()"><flux:icon.arrow-left class="size-5" /></button><h2 x-text="catalogCategory ?? 'Services'"></h2></header>
                                <label class="agenda-checkout-search">
                                    <flux:icon.magnifying-glass class="size-5" />
                                    <input type="search" x-model="catalogSearch" placeholder="Search">
                                </label>
                                <div x-show="catalogCategory === null" class="agenda-checkout-catalog-groups">
                                    @forelse ($checkoutServiceCategories as $categoryName => $categoryServices)
                                        <button
                                            type="button"
                                            x-show="catalogSearch === '' || String(@js(mb_strtolower($categoryName))).includes(catalogSearch.toLowerCase())"
                                            @click="openCatalogCategory(@js($categoryName))"
                                        >
                                            <span>{{ $categoryName }} <small>{{ $categoryServices->count() }}</small></span>
                                            <flux:icon.chevron-right class="size-5" />
                                        </button>
                                    @empty
                                        <p>No services available.</p>
                                    @endforelse
                                </div>

                                @foreach ($checkoutServiceCategories as $categoryName => $categoryServices)
                                    <div x-show="catalogCategory === @js($categoryName)" x-cloak class="agenda-checkout-catalog-items">
                                        @foreach ($categoryServices as $catalogService)
                                            <button
                                                type="button"
                                                x-show="catalogSearch === '' || String(@js(mb_strtolower($catalogService['name']))).includes(catalogSearch.toLowerCase())"
                                                @click="addCheckoutItem(@js([
                                                    'id' => $catalogService['id'],
                                                    'type' => 'service',
                                                    'name' => $catalogService['name'],
                                                    'detail' => $this->serviceDurationLabel($catalogService['duration_minutes']),
                                                    'price' => (float) $catalogService['price'],
                                                ]))"
                                            >
                                                <div><strong>{{ $catalogService['name'] }}</strong><span>{{ $this->serviceDurationLabel($catalogService['duration_minutes']) }}</span></div>
                                                <strong>PEN {{ number_format((float) $catalogService['price'], 0) }}</strong>
                                            </button>
                                        @endforeach
                                    </div>
                                @endforeach
                            </section>

                            <section x-show="cartCatalogView === 'products'" x-cloak class="agenda-checkout-catalog-detail">
                                <header><button type="button" aria-label="Volver" @click="backFromCartCatalog()"><flux:icon.arrow-left class="size-5" /></button><h2 x-text="catalogCategory ?? 'Products'"></h2></header>
                                <label class="agenda-checkout-search">
                                    <flux:icon.magnifying-glass class="size-5" />
                                    <input type="search" x-model="catalogSearch" placeholder="Search">
                                </label>
                                <div x-show="catalogCategory === null" class="agenda-checkout-catalog-groups">
                                    @forelse ($checkoutProductCategories as $categoryName => $categoryProducts)
                                        <button
                                            type="button"
                                            x-show="catalogSearch === '' || String(@js(mb_strtolower($categoryName))).includes(catalogSearch.toLowerCase())"
                                            @click="openCatalogCategory(@js($categoryName))"
                                        >
                                            <span>{{ $categoryName }} <small>{{ $categoryProducts->count() }}</small></span>
                                            <flux:icon.chevron-right class="size-5" />
                                        </button>
                                    @empty
                                        <p>No products available.</p>
                                    @endforelse
                                </div>

                                @foreach ($checkoutProductCategories as $categoryName => $categoryProducts)
                                    <div x-show="catalogCategory === @js($categoryName)" x-cloak class="agenda-checkout-catalog-items">
                                        @foreach ($categoryProducts as $catalogProduct)
                                            <button
                                                type="button"
                                                x-show="catalogSearch === '' || String(@js(mb_strtolower($catalogProduct['name']))).includes(catalogSearch.toLowerCase())"
                                                @click="addCheckoutItem(@js([
                                                    'id' => $catalogProduct['id'],
                                                    'type' => 'product',
                                                    'name' => $catalogProduct['name'],
                                                    'detail' => 'Stock: '.number_format((float) $catalogProduct['stock'], 0),
                                                    'price' => (float) $catalogProduct['price'],
                                                ]))"
                                            >
                                                <div><strong>{{ $catalogProduct['name'] }}</strong><span>Stock: {{ number_format((float) $catalogProduct['stock'], 0) }}</span></div>
                                                <strong>PEN {{ number_format((float) $catalogProduct['price'], 0) }}</strong>
                                            </button>
                                        @endforeach
                                    </div>
                                @endforeach
                            </section>
                        </div>

                        <section x-show="checkoutStep === 'payment'" x-cloak class="agenda-checkout-payment">
                            <h2>Select payment</h2>
                            <div class="agenda-checkout-payment-options">
                                <button type="button" @click="openCashModal(checkoutTotal({{ (float) $appointment->price }}))"><flux:icon.banknotes class="size-6" /><span>Cash</span></button>
                                <button type="button"><flux:icon.gift class="size-6" /><span>Gift card</span></button>
                                <button type="button"><flux:icon.arrows-right-left class="size-6" /><span>Split payment</span></button>
                                <button type="button"><flux:icon.currency-dollar class="size-6" /><span>Other</span></button>
                            </div>
                        </section>
                    </main>

                    <aside class="agenda-checkout-summary">
                        <div class="agenda-checkout-client">
                            <div>
                                <strong>{{ $appointment->client->fullName() }}</strong>
                                <span>{{ $appointment->client->email ?: 'Leave empty for walk-ins' }}</span>
                            </div>
                            <span class="agenda-checkout-client__icon"><flux:icon.user-plus class="size-6" /></span>
                        </div>

                        <article class="agenda-checkout-service">
                            <div>
                                <strong>{{ $appointment->service->name }}</strong>
                                <span>{{ $this->serviceDurationLabel($appointment->duration_minutes) }} · {{ $appointment->professional?->fullName() ?? 'Cualquier miembro del equipo' }}</span>
                            </div>
                            <strong>PEN {{ number_format((float) $appointment->price, 0) }}</strong>
                        </article>

                        <template x-for="item in checkoutCartItems" :key="item.key">
                            <article class="agenda-checkout-service agenda-checkout-cart-item">
                                <div>
                                    <strong x-text="item.name"></strong>
                                    <span><span x-show="item.quantity > 1" x-text="`${item.quantity} × `"></span><span x-text="item.detail"></span></span>
                                </div>
                                <strong class="agenda-checkout-cart-item__price" x-text="money(item.price * item.quantity)"></strong>
                                <div class="agenda-checkout-cart-item__actions">
                                    <button type="button" aria-label="Editar elemento" @click="openCheckoutItemEditor(item)"><flux:icon.pencil-square class="size-4" /></button>
                                    <button type="button" aria-label="Eliminar elemento" @click="removeCheckoutItem(item.key)"><flux:icon.trash class="size-4" /></button>
                                </div>
                            </article>
                        </template>

                        <button type="button" class="agenda-checkout-add" @click="checkoutStep = 'cart'"><flux:icon.shopping-cart class="size-4" /> Add to cart</button>

                        <footer class="agenda-checkout-summary__footer">
                            <div><span>Total</span><span x-text="money(checkoutTotal({{ (float) $appointment->price }}))"></span></div>
                            <div x-show="cashPaymentAmount > 0" x-cloak><span>Payments</span><span x-text="`− ${money(cashPaymentAmount)}`"></span></div>
                            <div x-show="cashPaymentAmount <= 0"><strong>To pay <flux:icon.chevron-right class="size-4" /></strong><strong x-text="money(checkoutTotal({{ (float) $appointment->price }}))"></strong></div>
                            <div x-show="cashPaymentAmount > 0" x-cloak><strong>Change <flux:icon.chevron-right class="size-4" /></strong><strong x-text="money(cashChange(checkoutTotal({{ (float) $appointment->price }}), cashPaymentAmount))"></strong></div>
                            <div class="agenda-checkout-footer-actions">
                                <button type="button" class="agenda-detail-more" aria-label="Más opciones"><flux:icon.ellipsis-vertical class="size-5" /></button>
                                <button x-show="checkoutStep !== 'payment'" type="button" class="agenda-checkout-continue" @click="checkoutStep = 'payment'; cartCatalogView = 'home'; catalogCategory = null">Continue to payment</button>
                                <button x-show="checkoutStep === 'payment' && cashPaymentAmount <= 0" x-cloak type="button" class="agenda-checkout-save-unpaid">Save unpaid</button>
                                <button x-show="checkoutStep === 'payment' && cashPaymentAmount > 0" x-cloak type="button" class="agenda-checkout-pay-now">Pay now</button>
                            </div>
                        </footer>
                    </aside>
                </section>

                <div x-show="customTipModalOpen" x-cloak class="agenda-custom-tip-modal" role="dialog" aria-modal="true" aria-labelledby="agenda-custom-tip-title">
                    <button type="button" class="agenda-custom-tip-modal__backdrop" aria-label="Cerrar propina personalizada" @click="customTipModalOpen = false"></button>

                    <section class="agenda-custom-tip-modal__dialog">
                        <header>
                            <h3 id="agenda-custom-tip-title">Add a tip</h3>
                            <button type="button" aria-label="Cerrar" @click="customTipModalOpen = false">
                                <flux:icon.x-mark class="size-5" />
                            </button>
                        </header>

                        <div class="agenda-custom-tip-display">
                            <template x-if="customTipMode === 'amount'">
                                <div><span>PEN</span><strong x-text="customTipInput"></strong></div>
                            </template>
                            <template x-if="customTipMode === 'percent'">
                                <div><strong x-text="customTipInput"></strong><span>%</span></div>
                            </template>
                        </div>

                        <div class="agenda-custom-tip-mode" role="group" aria-label="Tipo de propina">
                            <button type="button" x-bind:class="{ 'is-selected': customTipMode === 'amount' }" @click="customTipMode = 'amount'"><flux:icon.circle-stack class="size-4" /></button>
                            <button type="button" x-bind:class="{ 'is-selected': customTipMode === 'percent' }" @click="customTipMode = 'percent'">%</button>
                        </div>

                        <div class="agenda-custom-tip-keypad">
                            <button type="button" @click="appendCustomTip('1')">1</button>
                            <button type="button" @click="appendCustomTip('2')">2</button>
                            <button type="button" @click="appendCustomTip('3')">3</button>
                            <button type="button" @click="appendCustomTip('4')">4</button>
                            <button type="button" @click="appendCustomTip('5')">5</button>
                            <button type="button" @click="appendCustomTip('6')">6</button>
                            <button type="button" @click="appendCustomTip('7')">7</button>
                            <button type="button" @click="appendCustomTip('8')">8</button>
                            <button type="button" @click="appendCustomTip('9')">9</button>
                            <button type="button" @click="appendCustomTip('.')">.</button>
                            <button type="button" @click="appendCustomTip('0')">0</button>
                            <button type="button" aria-label="Borrar último dígito" @click="deleteCustomTipDigit()"><flux:icon.backspace class="size-5" /></button>
                        </div>

                        <footer>
                            <strong x-text="customTipPercentageLabel(checkoutSubtotal({{ (float) $appointment->price }}))"></strong>
                            <button type="button" x-bind:disabled="Number(customTipInput) <= 0" @click="confirmCustomTip()">Add</button>
                        </footer>
                    </section>
                </div>

                <div x-show="cashModalOpen" x-cloak class="agenda-cash-modal" role="dialog" aria-modal="true" aria-labelledby="agenda-cash-title">
                    <button type="button" class="agenda-cash-modal__backdrop" aria-label="Cerrar importe en efectivo" @click="cashModalOpen = false"></button>

                    <section class="agenda-cash-modal__dialog">
                        <header>
                            <h3 id="agenda-cash-title">Add cash amount</h3>
                            <button type="button" aria-label="Cerrar" @click="cashModalOpen = false"><flux:icon.x-mark class="size-5" /></button>
                        </header>

                        <div class="agenda-cash-display"><span>PEN</span><strong x-text="cashInput"></strong></div>

                        <div class="agenda-cash-suggestions">
                            <template x-for="increment in [0, 5, 10, 20, 50]" :key="increment">
                                <button type="button" @click="cashInput = String(cashSuggestion(checkoutTotal({{ (float) $appointment->price }}), increment))" x-text="`PEN ${cashSuggestion(checkoutTotal({{ (float) $appointment->price }}), increment)}`"></button>
                            </template>
                        </div>

                        <div class="agenda-cash-keypad">
                            <button type="button" @click="appendCashDigit('1')">1</button>
                            <button type="button" @click="appendCashDigit('2')">2</button>
                            <button type="button" @click="appendCashDigit('3')">3</button>
                            <button type="button" @click="appendCashDigit('4')">4</button>
                            <button type="button" @click="appendCashDigit('5')">5</button>
                            <button type="button" @click="appendCashDigit('6')">6</button>
                            <button type="button" @click="appendCashDigit('7')">7</button>
                            <button type="button" @click="appendCashDigit('8')">8</button>
                            <button type="button" @click="appendCashDigit('9')">9</button>
                            <button type="button" @click="appendCashDigit('.')">.</button>
                            <button type="button" @click="appendCashDigit('0')">0</button>
                            <button type="button" aria-label="Borrar último dígito" @click="deleteCashDigit()"><flux:icon.backspace class="size-5" /></button>
                        </div>

                        <p class="agenda-cash-received">Cash received by <span>·</span> <strong>{{ $appointment->professional?->fullName() ?? 'Cualquier miembro del equipo' }}</strong></p>

                        <footer>
                            <strong>Left to pay <span>·</span> <span x-text="money(cashLeftToPay(checkoutTotal({{ (float) $appointment->price }})))"></span></strong>
                            <button type="button" x-bind:disabled="Number(cashInput) <= 0" @click="confirmCashPayment()">Add</button>
                        </footer>
                    </section>
                </div>

                <div x-show="editItemModalOpen" x-cloak class="agenda-cart-edit-modal" role="dialog" aria-modal="true" aria-labelledby="agenda-cart-edit-title">
                    <button type="button" class="agenda-cart-edit-modal__backdrop" aria-label="Cerrar edición" @click="editItemModalOpen = false"></button>

                    <section class="agenda-cart-edit-modal__dialog">
                        <header>
                            <h3 id="agenda-cart-edit-title" x-text="`Edit ${checkoutCartItems.find((item) => item.key === editingCartItemKey)?.name ?? 'item'}`"></h3>
                            <button type="button" aria-label="Cerrar" @click="editItemModalOpen = false"><flux:icon.x-mark class="size-5" /></button>
                        </header>

                        <div class="agenda-cart-edit-modal__fields">
                            <label><strong>Price</strong><span class="agenda-cart-edit-price"><span>PEN</span><input type="number" min="0" step="0.01" x-model="editItemPrice"></span></label>
                            <label><strong>Quantity</strong><span class="agenda-cart-edit-quantity"><input type="number" min="1" step="1" x-model.number="editItemQuantity"><span><button type="button" @click="changeEditItemQuantity(-1)">−</button><button type="button" @click="changeEditItemQuantity(1)">＋</button></span></span></label>
                            <label class="agenda-cart-edit-modal__wide"><strong>Discounts</strong><select disabled><option>None available</option></select></label>
                            <label class="agenda-cart-edit-modal__wide"><strong>Team member</strong><select><option>{{ $appointment->professional?->fullName() ?? 'Cualquier miembro del equipo' }}</option></select></label>
                        </div>

                        <footer>
                            <div><span>Item total</span><strong x-text="money((Number(editItemPrice) || 0) * (Number(editItemQuantity) || 1))"></strong></div>
                            <div><button type="button" class="agenda-cart-edit-delete" aria-label="Eliminar elemento" @click="removeEditingCheckoutItem()"><flux:icon.trash class="size-5" /></button><button type="button" class="agenda-cart-edit-apply" @click="applyCheckoutItemEdit()">Apply</button></div>
                        </footer>
                    </section>
                </div>
                </div>
            </template>
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

    <div
        x-show="appointmentOpening"
        x-cloak
        class="agenda-appointment-overlay agenda-appointment-overlay--loading"
        aria-live="polite"
        aria-label="Preparando nueva cita"
    >
        <div class="agenda-appointment-backdrop"></div>

        <aside class="agenda-appointment-drawer" aria-busy="true">
            <div class="agenda-appointment-rail">
                <span class="agenda-appointment-skeleton agenda-appointment-skeleton--circle"></span>
            </div>

            <div class="agenda-appointment-stepbar">
                <span class="agenda-appointment-skeleton agenda-appointment-skeleton--step-icon"></span>
                <span class="agenda-appointment-skeleton agenda-appointment-skeleton--step-title"></span>
                <span class="agenda-appointment-skeleton agenda-appointment-skeleton--step-copy"></span>
            </div>

            <div class="agenda-appointment-loading">
                <span class="agenda-appointment-skeleton agenda-appointment-skeleton--heading"></span>
                <span class="agenda-appointment-skeleton agenda-appointment-skeleton--search"></span>

                <div class="agenda-appointment-loading__groups">
                    @foreach ([3, 2, 4] as $rows)
                        <section class="agenda-appointment-loading__group">
                            <div class="agenda-appointment-loading__category">
                                <span class="agenda-appointment-skeleton agenda-appointment-skeleton--category"></span>
                                <span class="agenda-appointment-skeleton agenda-appointment-skeleton--count"></span>
                            </div>

                            <div class="agenda-appointment-loading__services">
                                @for ($row = 0; $row < $rows; $row++)
                                    <div class="agenda-appointment-loading__row">
                                        <div>
                                            <span class="agenda-appointment-skeleton agenda-appointment-skeleton--service"></span>
                                            <span class="agenda-appointment-skeleton agenda-appointment-skeleton--duration"></span>
                                        </div>
                                        <span class="agenda-appointment-skeleton agenda-appointment-skeleton--price"></span>
                                    </div>
                                @endfor
                            </div>
                        </section>
                    @endforeach
                </div>
            </div>
        </aside>
    </div>

    @if ($appointmentPanelLoaded)
        <div
            class="agenda-appointment-overlay agenda-appointment-overlay--ready"
            x-bind:class="{
                'is-opening': appointmentOpeningTransition,
                'is-closing': appointmentClosing,
            }"
            x-show="appointmentVisible"
            x-cloak
            wire:key="appointment-panel-persistent"
            @keydown.escape.window="appointmentVisible && (appointmentExitConfirmationOpen
                ? cancelAppointmentExit()
                : requestAppointmentClose(() => $wire.closeModal()))"
        >
            <button type="button" class="agenda-appointment-backdrop" aria-label="Cerrar panel de cita" @click="requestAppointmentClose(() => $wire.closeModal())"></button>

            <aside class="agenda-appointment-drawer" data-testid="appointment-panel">
                <div class="agenda-appointment-rail" wire:ignore>
                    <button type="button" aria-label="Cerrar" @click="requestAppointmentClose(() => $wire.closeModal())" x-bind:disabled="appointmentClosing">
                        <flux:icon.x-mark class="size-6" />
                    </button>
                    <button type="button" aria-label="Pantalla completa" wire:click="toggleFullscreen">
                        <flux:icon.arrows-pointing-out class="size-5" />
                    </button>
                    <button type="button" aria-label="Ir a hoy" wire:click="today">
                        <flux:icon.cog-6-tooth class="size-5" />
                    </button>
                </div>

                <div class="agenda-appointment-stepbar" wire:ignore>
                    <div class="agenda-step-icon">
                        <flux:icon.user-plus class="size-5" />
                    </div>
                    <strong>Agregar<br>cliente</strong>
                    <p>O déjelo vacío para clientes sin cita previa.</p>
                </div>

                <div
                    @class([
                        'agenda-appointment-content',
                        'agenda-appointment-content--fixed-footer' => $appointmentStep === 'time',
                    ])
                    wire:key="appointment-dynamic-content"
                    @input="appointmentDirty = true"
                    @change="appointmentDirty = true"
                >
                    <div
                        class="agenda-appointment-stage"
                        wire:key="appointment-stage-{{ $appointmentStep }}"
                        wire:loading.class="is-changing"
                        wire:target="showServiceStep,showServicesSummary,continueToAppointmentTime,selectAppointmentDate,continueToAppointmentDetails,showAppointmentTime"
                    >
                        @if ($appointmentStep === 'picker')
                        @php
                            $serviceSearchTerms = $this->servicesCatalog
                                ->map(fn (array $service): string => mb_strtolower($service['name'].' '.$service['category_name']))
                                ->values();
                        @endphp
                        <div
                            class="agenda-service-step"
                            x-data="{
                                serviceSearch: '',
                                matches(value) {
                                    return this.serviceSearch === '' || String(value).includes(this.serviceSearch.toLowerCase());
                                },
                                hasMatches() {
                                    return @js($serviceSearchTerms)->some((value) => this.matches(value));
                                },
                            }"
                        >
                            <h2>Seleccione un servicio</h2>

                            <label class="agenda-service-search">
                                <flux:icon.magnifying-glass class="size-5" />
                                <input
                                    type="search"
                                    x-model.debounce.100ms="serviceSearch"
                                    placeholder="Buscar por nombre del servicio"
                                    aria-label="Buscar por nombre del servicio"
                                    autofocus
                                >
                            </label>

                            <div class="agenda-service-groups">
                                @forelse ($this->servicesCatalog->groupBy(fn (array $service): string => $service['category_name']) as $category => $services)
                                    <section
                                        class="agenda-service-group"
                                        wire:key="service-category-{{ md5($category) }}"
                                        x-show="matches(@js(mb_strtolower($category.' '.$services->pluck('name')->implode(' '))))"
                                    >
                                        <h3>
                                            {{ $category }}
                                            <span>{{ $services->count() }}</span>
                                        </h3>

                                        <div>
                                            @foreach ($services as $service)
                                                <button
                                                    type="button"
                                                    wire:key="appointment-service-{{ $service['id'] }}"
                                                    x-show="matches(@js(mb_strtolower($service['name'].' '.$service['category_name'])))"
                                                    @click="selectAppointmentService(
                                                        {{ $service['id'] }},
                                                        () => $wire.selectAppointmentService({{ $service['id'] }})
                                                    )"
                                                    x-bind:disabled="appointmentServiceSelecting"
                                                    x-bind:class="{ 'is-selecting': appointmentServiceSelectingId === {{ $service['id'] }} }"
                                                >
                                                    <span>
                                                        <strong>{{ $service['name'] }}</strong>
                                                        <small>{{ $this->serviceDurationLabel($service['duration_minutes']) }}</small>
                                                    </span>
                                                    <b>S/ {{ number_format($service['price'], 2) }}</b>
                                                </button>
                                            @endforeach
                                        </div>
                                    </section>
                                @empty
                                    <div class="agenda-service-empty">No hay servicios disponibles.</div>
                                @endforelse
                                @if ($this->servicesCatalog->isNotEmpty())
                                    <div class="agenda-service-empty" x-show="! hasMatches()" x-cloak>
                                        No encontramos servicios con “<span x-text="serviceSearch"></span>”.
                                    </div>
                                @endif
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
                                    @php
                                        $selectedProfessionalId = $selectedServiceProfessionals[$service->id] ?? null;
                                        $selectedProfessional = $selectedProfessionalId !== null
                                            ? $this->professionalsCatalog->firstWhere('id', $selectedProfessionalId)
                                            : null;
                                    @endphp
                                    <article wire:key="selected-service-{{ $service->id }}">
                                        <div class="agenda-selected-service-info">
                                            <span>
                                                <strong>{{ $service->name }}</strong>
                                                <small>{{ $this->serviceDurationLabel($service->duration_minutes) }}</small>
                                            </span>
                                            <div class="agenda-selected-service-actions">
                                                <button type="button" wire:click="showServiceStep" aria-label="Editar {{ $service->name }}">
                                                    <flux:icon.pencil class="size-4" />
                                                </button>
                                                <button type="button" wire:click="removeAppointmentService({{ $service->id }})" @click="appointmentDirty = true" aria-label="Quitar {{ $service->name }}">
                                                    <flux:icon.trash class="size-4" />
                                                </button>
                                            </div>
                                        </div>

                                        <div class="agenda-member-select" x-data="{ open: false }" @click.outside="open = false">
                                            <button
                                                type="button"
                                                class="agenda-member-select__trigger"
                                                @click="open = !open"
                                                x-bind:aria-expanded="open"
                                            >
                                                <span class="agenda-member-avatar">
                                                    @if ($selectedProfessional?->photoUrl())
                                                        <img src="{{ $selectedProfessional->photoUrl() }}" alt="">
                                                    @elseif ($selectedProfessional)
                                                        {{ $selectedProfessional->initials() }}
                                                    @else
                                                        <flux:icon.user class="size-4" />
                                                    @endif
                                                </span>
                                                <strong>{{ $selectedProfessional?->fullName() ?? 'Cualquier miembro del equipo' }}</strong>
                                                <flux:icon.chevron-down class="size-4 transition-transform" x-bind:class="{ 'rotate-180': open }" />
                                            </button>

                                            <div x-show="open" x-cloak x-transition.origin.top class="agenda-member-select__menu">
                                                <button
                                                    type="button"
                                                    wire:click="$set('selectedServiceProfessionals.{{ $service->id }}', null)"
                                                    @click="open = false; appointmentDirty = true"
                                                    class="agenda-member-select__option"
                                                >
                                                    <span class="agenda-member-avatar"><flux:icon.user class="size-4" /></span>
                                                    <span>Cualquier miembro del equipo</span>
                                                    @if ($selectedProfessionalId === null)
                                                        <flux:icon.check class="size-5" />
                                                    @endif
                                                </button>

                                                @foreach ($this->professionalsCatalog as $professional)
                                                    <button
                                                        type="button"
                                                        wire:click="$set('selectedServiceProfessionals.{{ $service->id }}', {{ $professional->id }})"
                                                        @click="open = false; appointmentDirty = true"
                                                        class="agenda-member-select__option"
                                                    >
                                                        <span class="agenda-member-avatar">
                                                            @if ($professional->photoUrl())
                                                                <img src="{{ $professional->photoUrl() }}" alt="">
                                                            @else
                                                                {{ $professional->initials() }}
                                                            @endif
                                                        </span>
                                                        <span>{{ $professional->fullName() }}</span>
                                                        @if ((int) $selectedProfessionalId === $professional->id)
                                                            <flux:icon.check class="size-5" />
                                                        @endif
                                                    </button>
                                                @endforeach
                                            </div>
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
                        <div
                            class="agenda-wizard-stage agenda-time-stage"
                            wire:key="appointment-time-selection-{{ $appointmentTimeDate }}"
                            x-data="{
                                selectedSlotStart: $wire.entangle('selectedSlotStart'),
                                selectedSlotEnd: $wire.entangle('selectedSlotEnd'),
                            }"
                        >
                            <nav class="agenda-wizard-breadcrumb" aria-label="Progreso de la cita">
                                <button type="button" wire:click="showServicesSummary">Servicios</button>
                                <flux:icon.chevron-right class="size-4" />
                                <strong>Tiempo</strong>
                            </nav>

                            <h2>Seleccione una hora</h2>

                            <div class="agenda-time-controls">
                                <div class="agenda-time-team">
                                    <flux:icon.user class="size-4" />
                                    <span>Cualquier miembro del equipo</span>
                                    <flux:icon.chevron-down class="size-4" />
                                </div>
                                <button type="button" class="agenda-time-calendar-button" aria-label="Abrir calendario">
                                    <flux:icon.calendar-days class="size-5" />
                                </button>
                            </div>

                            <div class="agenda-date-heading">
                                <strong>{{ ucfirst(\Carbon\CarbonImmutable::parse($appointmentTimeDate)->translatedFormat('F')) }}</strong>
                                <div>
                                    <button type="button" wire:click="shiftAppointmentDateWindow(-7)" aria-label="Fechas anteriores">
                                        <flux:icon.chevron-left class="size-4" />
                                    </button>
                                    <button type="button" wire:click="shiftAppointmentDateWindow(7)" aria-label="Fechas siguientes">
                                        <flux:icon.chevron-right class="size-4" />
                                    </button>
                                </div>
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
                                    Elegir del calendario
                                </button>
                            </div>

                            <div class="agenda-time-slots">
                                @forelse ($slotSearchResults as $slot)
                                    <button
                                        type="button"
                                        wire:key="appointment-slot-{{ md5($slot['starts_at']) }}"
                                        @click="selectedSlotStart = @js($slot['starts_at']); selectedSlotEnd = @js($slot['ends_at'])"
                                        x-bind:class="{ 'is-selected': selectedSlotStart === @js($slot['starts_at']) }"
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
                                    x-bind:disabled="selectedSlotStart === ''"
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
                                <button type="button" @click="requestAppointmentClose(() => $wire.closeModal())" x-bind:disabled="appointmentClosing">Cancelar</button>
                                <button type="submit">{{ $form->appointmentId ? 'Guardar cambios' : 'Crear cita' }}</button>
                            </div>
                        </form>
                        @endif
                    </div>
                </div>
            </aside>
        </div>

        <div
            x-show="appointmentExitConfirmationOpen"
            x-cloak
            x-transition.opacity.duration.180ms
            class="agenda-exit-confirmation"
            role="alertdialog"
            aria-modal="true"
            aria-labelledby="agenda-exit-confirmation-title"
            aria-describedby="agenda-exit-confirmation-description"
        >
            <div class="agenda-exit-confirmation__backdrop"></div>

            <section class="agenda-exit-confirmation__dialog" @click.outside="cancelAppointmentExit()">
                <header>
                    <h2 id="agenda-exit-confirmation-title">Tienes cambios sin guardar</h2>
                    <button type="button" aria-label="Cerrar confirmación" @click="cancelAppointmentExit()">
                        <flux:icon.x-mark class="size-5" />
                    </button>
                </header>

                <p id="agenda-exit-confirmation-description">
                    Si cierras la cita ahora, se perderán los cambios. ¿Deseas salir?
                </p>

                <footer>
                    <button type="button" @click="cancelAppointmentExit()">Volver</button>
                    <button type="button" @click="confirmAppointmentExit()">Sí, salir</button>
                </footer>
            </section>
        </div>
    @endif
</section>
