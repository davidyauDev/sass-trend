<?php

namespace App\Livewire\Agenda;

use App\Actions\Agenda\AddAppointmentNoteAction;
use App\Actions\Agenda\ChangeAppointmentStatusAction;
use App\Actions\Agenda\CreateAppointmentAction;
use App\Actions\Agenda\RescheduleAppointmentAction;
use App\Actions\Agenda\UpdateAppointmentAction;
use App\Livewire\Forms\AppointmentForm;
use App\Models\Appointment;
use App\Models\AppointmentHistory;
use App\Models\AppointmentNote;
use App\Models\AppointmentPayment;
use App\Models\AppointmentStatus;
use App\Models\Branch;
use App\Models\Resource;
use App\Models\ScheduleBlock;
use App\Models\Service;
use App\Models\User;
use App\Services\Agenda\AppointmentAvailabilityService;
use App\Services\Agenda\AppointmentStatusCatalog;
use Carbon\CarbonImmutable;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection as SupportCollection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Agenda')]
class Index extends Component
{
    public AppointmentForm $form;

    #[Url(as: 'view')]
    public string $viewMode = 'week';

    #[Url(as: 'date')]
    public string $selectedDate = '';

    #[Url(as: 'q')]
    public string $search = '';

    public ?int $branchFilterId = null;

    public ?int $professionalFilterId = null;

    public ?int $resourceFilterId = null;

    public bool $onlyAvailable = false;

    public bool $isFullscreen = false;

    public ?int $selectedAppointmentId = null;

    public string $noteDraft = '';

    public string $statusReason = '';

    public string $slotSearchDate = '';

    public ?int $slotSearchBranchId = null;

    public ?int $slotSearchProfessionalId = null;

    public ?int $slotSearchResourceId = null;

    public string $slotSearchDuration = '60';

    /** @var array<int, array{starts_at: string, ends_at: string, label: string, branch_name: string}> */
    public array $slotSearchResults = [];

    public function mount(): void
    {
        abort_unless(auth()->user()->can('viewAny', Appointment::class), 403);

        $this->selectedDate = now()->toDateString();
        $this->slotSearchDate = $this->selectedDate;
        $this->slotSearchBranchId = Branch::query()->orderBy('name')->value('id');
        $this->form->resetForm();
    }

    public function updatedViewMode(): void
    {
        if (! in_array($this->viewMode, ['day', 'week', 'month', 'list'], true)) {
            $this->viewMode = 'week';
        }
    }

    public function updatedSelectedDate(): void
    {
        if ($this->selectedDate === '') {
            $this->selectedDate = now()->toDateString();
        }

        $this->slotSearchDate = $this->selectedDate;
    }

    public function updatedBranchFilterId(): void
    {
        $this->slotSearchBranchId = $this->branchFilterId;
    }

    public function updatedFormServiceId(): void
    {
        if ($this->form->service_id === null) {
            return;
        }

        $service = Service::query()->find($this->form->service_id);

        if ($service === null) {
            return;
        }

        $this->form->fillFromService($service);

        if ($this->selectedDate !== '') {
            $startsAt = CarbonImmutable::parse($this->selectedDate.' 09:00');
            $this->form->starts_at = $startsAt->format('Y-m-d\TH:i');
            $this->form->ends_at = $startsAt->addMinutes($service->duration_minutes)->format('Y-m-d\TH:i');
        }
    }

    public function today(): void
    {
        $this->selectedDate = now()->toDateString();
    }

    public function previous(): void
    {
        $this->selectedDate = match ($this->viewMode) {
            'day' => CarbonImmutable::parse($this->selectedDate)->subDay()->toDateString(),
            'week' => CarbonImmutable::parse($this->selectedDate)->subWeek()->toDateString(),
            'month', 'list' => CarbonImmutable::parse($this->selectedDate)->subMonthNoOverflow()->toDateString(),
            default => now()->toDateString(),
        };
    }

    public function next(): void
    {
        $this->selectedDate = match ($this->viewMode) {
            'day' => CarbonImmutable::parse($this->selectedDate)->addDay()->toDateString(),
            'week' => CarbonImmutable::parse($this->selectedDate)->addWeek()->toDateString(),
            'month', 'list' => CarbonImmutable::parse($this->selectedDate)->addMonthNoOverflow()->toDateString(),
            default => now()->toDateString(),
        };
    }

    public function toggleFullscreen(): void
    {
        $this->isFullscreen = ! $this->isFullscreen;
    }

    public function openCreateModal(): void
    {
        $this->form->resetForm();
        $this->form->branch_id = $this->branchFilterId ?? Branch::query()->orderBy('name')->value('id');
        $this->form->professional_id = $this->professionalFilterId;
        $this->form->resource_id = $this->resourceFilterId;
        $this->form->status_slug = AppointmentStatusCatalog::PENDING;
        $this->prefillFormStartAndEnd();
        $this->resetValidation();
        $this->resetErrorBag();

        $this->modal('appointment-form')->show();
    }

    public function openEditModal(int $appointmentId): void
    {
        $appointment = Appointment::query()
            ->with(['status'])
            ->findOrFail($appointmentId);

        $this->authorize('update', $appointment);

        $this->form->fillFromAppointment($appointment);
        $this->resetValidation();
        $this->resetErrorBag();

        $this->modal('appointment-form')->show();
    }

    public function closeModal(): void
    {
        $this->form->resetForm();
        $this->resetValidation();
        $this->resetErrorBag();
    }

    public function openDrawer(int $appointmentId): void
    {
        $this->selectedAppointmentId = $appointmentId;
        $this->noteDraft = '';
        $this->statusReason = '';
    }

    public function closeDrawer(): void
    {
        $this->selectedAppointmentId = null;
        $this->noteDraft = '';
        $this->statusReason = '';
    }

    public function save(CreateAppointmentAction $createAppointment, UpdateAppointmentAction $updateAppointment): void
    {
        $this->form->withAvailabilityValidation()->validate();

        $payload = $this->form->payload();
        $actor = $this->authUser();
        $isEditing = $this->form->appointmentId !== null;

        if ($isEditing) {
            $appointment = Appointment::query()->with('status')->findOrFail($this->form->appointmentId);
            $this->authorize('update', $appointment);
            $appointment = $updateAppointment->handle($actor, $appointment, $payload);
        } else {
            $this->authorize('create', Appointment::class);
            $appointment = $createAppointment->handle($actor, $payload);
        }

        $this->selectedAppointmentId = $appointment->id;
        $this->closeModal();
        $this->modal('appointment-form')->close();

        Flux::toast(
            variant: 'success',
            text: $isEditing ? 'Cita actualizada correctamente.' : 'Cita creada correctamente.',
        );
    }

    public function moveAppointment(int $appointmentId, string $startsAt, RescheduleAppointmentAction $rescheduleAppointment): void
    {
        $appointment = Appointment::query()->with('status')->findOrFail($appointmentId);
        $this->authorize('reschedule', $appointment);

        $starts = CarbonImmutable::parse($startsAt);
        $ends = $starts->addMinutes($appointment->duration_minutes);

        $appointment = $rescheduleAppointment->handle($this->authUser(), $appointment, [
            'starts_at' => $starts->toDateTimeString(),
            'ends_at' => $ends->toDateTimeString(),
            'branch_id' => $appointment->branch_id,
            'professional_id' => $appointment->professional_id,
            'resource_id' => $appointment->resource_id,
        ]);

        $this->selectedAppointmentId = $appointment->id;

        Flux::toast(variant: 'success', text: 'Cita reprogramada mediante arrastrar y soltar.');
    }

    public function changeStatusInline(int $appointmentId, string $statusSlug, ChangeAppointmentStatusAction $changeStatus): void
    {
        $appointment = Appointment::query()->with('status')->findOrFail($appointmentId);
        $this->authorize('changeStatus', $appointment);

        $this->selectedAppointmentId = $appointment->id;
        $changeStatus->handle($this->authUser(), $appointment, $statusSlug);

        Flux::toast(variant: 'success', text: 'Estado actualizado.');
    }

    public function completeAppointment(ChangeAppointmentStatusAction $changeStatus): void
    {
        $this->applyStatusToSelected(AppointmentStatusCatalog::COMPLETED, $changeStatus);
    }

    public function markNoShow(ChangeAppointmentStatusAction $changeStatus): void
    {
        $this->applyStatusToSelected(AppointmentStatusCatalog::NO_SHOW, $changeStatus);
    }

    public function cancelAppointment(ChangeAppointmentStatusAction $changeStatus): void
    {
        $this->applyStatusToSelected(AppointmentStatusCatalog::CANCELLED, $changeStatus, $this->statusReason !== '' ? $this->statusReason : 'Cancelled from agenda.');
    }

    public function rescheduleSelected(RescheduleAppointmentAction $rescheduleAppointment): void
    {
        $appointment = $this->selectedAppointment();

        if ($appointment === null) {
            return;
        }

        $this->authorize('reschedule', $appointment);

        $starts = CarbonImmutable::parse($appointment->starts_at)->addHour();
        $ends = $starts->addMinutes($appointment->duration_minutes);

        $appointment = $rescheduleAppointment->handle($this->authUser(), $appointment, [
            'starts_at' => $starts->toDateTimeString(),
            'ends_at' => $ends->toDateTimeString(),
            'branch_id' => $appointment->branch_id,
            'professional_id' => $appointment->professional_id,
            'resource_id' => $appointment->resource_id,
        ]);

        $this->selectedAppointmentId = $appointment->id;
        Flux::toast(variant: 'success', text: 'Cita reprogramada.');
    }

    public function addNote(AddAppointmentNoteAction $addAppointmentNote): void
    {
        $appointment = $this->selectedAppointment();

        if ($appointment === null || trim($this->noteDraft) === '') {
            return;
        }

        $addAppointmentNote->handle($this->authUser(), $appointment, $this->noteDraft, true);
        $this->noteDraft = '';
        Flux::toast(variant: 'success', text: 'Nota agregada al historial.');
    }

    public function searchAvailableSlots(AppointmentAvailabilityService $availability): void
    {
        if ($this->slotSearchDate === '' || $this->slotSearchDuration === '') {
            $this->slotSearchResults = [];

            return;
        }

        $slots = $availability->searchSlots(
            CarbonImmutable::parse($this->slotSearchDate),
            (int) $this->slotSearchDuration,
            $this->slotSearchBranchId,
            $this->slotSearchProfessionalId,
            $this->slotSearchResourceId,
        );

        $branchName = $this->slotSearchBranchId !== null
            ? (string) (Branch::query()->whereKey($this->slotSearchBranchId)->value('name') ?? 'Branch')
            : 'All branches';

        $this->slotSearchResults = collect($slots)
            ->map(fn (array $slot): array => [
                'starts_at' => $slot['starts_at'],
                'ends_at' => $slot['ends_at'],
                'label' => CarbonImmutable::parse($slot['starts_at'])->format('H:i').' - '.CarbonImmutable::parse($slot['ends_at'])->format('H:i'),
                'branch_name' => $branchName,
            ])
            ->values()
            ->all();

        Flux::toast(
            variant: 'success',
            text: count($this->slotSearchResults) > 0
                ? 'Se encontraron slots disponibles.'
                : 'No se encontraron slots disponibles.',
        );
    }

    public function openSlotResult(string $startsAt, string $endsAt): void
    {
        $this->form->resetForm();
        $this->form->branch_id = $this->slotSearchBranchId ?? $this->branchFilterId ?? Branch::query()->orderBy('name')->value('id');
        $this->form->starts_at = CarbonImmutable::parse($startsAt)->format('Y-m-d\TH:i');
        $this->form->ends_at = CarbonImmutable::parse($endsAt)->format('Y-m-d\TH:i');
        $this->form->status_slug = AppointmentStatusCatalog::PENDING;
        $this->prefillFormStartAndEnd();
        $this->resetValidation();
        $this->resetErrorBag();

        $this->modal('appointment-form')->show();
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->branchFilterId = null;
        $this->professionalFilterId = null;
        $this->resourceFilterId = null;
        $this->onlyAvailable = false;
        $this->viewMode = 'week';
        $this->selectedDate = now()->toDateString();
    }

    /**
     * @return SupportCollection<int, Branch>
     */
    #[Computed]
    public function branches(): SupportCollection
    {
        return Branch::query()->orderBy('name')->get();
    }

    /**
     * @return SupportCollection<int, User>
     */
    #[Computed]
    public function professionalsCatalog(): SupportCollection
    {
        return User::query()
            ->with(['role', 'professionalProfile'])
            ->where('is_active', true)
            ->whereHas('professionalProfile', fn (Builder $query): Builder => $query->where('is_active', true))
            ->orderBy('name')
            ->get();
    }

    /**
     * @return SupportCollection<int, resource>
     */
    #[Computed]
    public function resourcesCatalog(): SupportCollection
    {
        return Resource::query()
            ->with('branch')
            ->when(
                $this->branchFilterId !== null,
                fn (Builder $query): Builder => $query->where(function (Builder $resourceQuery): void {
                    $resourceQuery->whereNull('branch_id')->orWhere('branch_id', $this->branchFilterId);
                }),
            )
            ->where('is_active', true)
            ->orderBy('type')
            ->orderBy('name')
            ->get();
    }

    /**
     * @return SupportCollection<int, AppointmentStatus>
     */
    #[Computed]
    public function appointmentStatuses(): SupportCollection
    {
        return AppointmentStatus::query()
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * @return SupportCollection<int, Appointment>
     */
    #[Computed]
    public function appointments(): SupportCollection
    {
        [$start, $end] = $this->rangeBounds();

        return Appointment::query()
            ->with(['branch', 'client', 'service', 'resource', 'professional', 'status', 'payments', 'notes', 'histories'])
            ->search($this->search)
            ->when($this->branchFilterId !== null, fn (Builder $query): Builder => $query->where('branch_id', $this->branchFilterId))
            ->when($this->professionalFilterId !== null, fn (Builder $query): Builder => $query->where('professional_id', $this->professionalFilterId))
            ->when($this->resourceFilterId !== null, fn (Builder $query): Builder => $query->where('resource_id', $this->resourceFilterId))
            ->when($this->onlyAvailable, fn (Builder $query): Builder => $query->whereHas('status', fn (Builder $statusQuery): Builder => $statusQuery->where('is_terminal', false)))
            ->whereBetween('starts_at', [$start->toDateTimeString(), $end->toDateTimeString()])
            ->orderBy('starts_at')
            ->get();
    }

    /**
     * @return array<string, mixed>
     */
    #[Computed]
    public function dashboardStats(): array
    {
        $today = CarbonImmutable::now()->startOfDay();
        $todayAppointments = $this->appointments()->filter(fn (Appointment $appointment): bool => $appointment->starts_at->isSameDay($today));
        $confirmed = $this->appointments()->filter(fn (Appointment $appointment): bool => $appointment->status->slug === AppointmentStatusCatalog::CONFIRMED);
        $cancelled = $this->appointments()->filter(fn (Appointment $appointment): bool => $appointment->status->slug === AppointmentStatusCatalog::CANCELLED);
        $revenueToday = AppointmentPayment::query()
            ->where('status', 'paid')
            ->whereDate('paid_at', $today)
            ->sum('amount');
        $rangeMinutes = max(1, $this->rangeBounds()[0]->diffInMinutes($this->rangeBounds()[1]));
        $bookedMinutes = $this->appointments()->sum('duration_minutes');

        return [
            'appointments_today' => $todayAppointments->count(),
            'confirmed_appointments' => $confirmed->count(),
            'cancelled_appointments' => $cancelled->count(),
            'revenue_today' => (float) $revenueToday,
            'occupancy_percentage' => min(100, round(($bookedMinutes / $rangeMinutes) * 100, 1)),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    #[Computed]
    public function rangeDays(): array
    {
        [$start, $end] = $this->rangeBounds();
        $days = [];

        for ($cursor = $start->startOfDay(); $cursor->lessThanOrEqualTo($end->startOfDay()); $cursor = $cursor->addDay()) {
            $days[] = [
                'date' => $cursor,
                'key' => $cursor->toDateString(),
                'label' => $cursor->translatedFormat('D d M'),
                'short_label' => $cursor->translatedFormat('D'),
                'is_today' => $cursor->isToday(),
                'is_selected' => $cursor->isSameDay(CarbonImmutable::parse($this->selectedDate)),
                'appointments' => $this->appointments()->filter(fn (Appointment $appointment): bool => $appointment->starts_at->isSameDay($cursor))->values(),
                'blocks' => $this->scheduleBlocksForDate($cursor),
            ];
        }

        return $days;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    #[Computed]
    public function monthGrid(): array
    {
        $anchor = CarbonImmutable::parse($this->selectedDate)->startOfMonth()->startOfWeek(CarbonImmutable::MONDAY);
        $grid = [];

        for ($cursor = $anchor; count($grid) < 42; $cursor = $cursor->addDay()) {
            $grid[] = [
                'date' => $cursor,
                'key' => $cursor->toDateString(),
                'day' => $cursor->day,
                'is_in_month' => $cursor->month === CarbonImmutable::parse($this->selectedDate)->month,
                'is_today' => $cursor->isToday(),
                'is_selected' => $cursor->isSameDay(CarbonImmutable::parse($this->selectedDate)),
                'appointments' => $this->appointments()->filter(fn (Appointment $appointment): bool => $appointment->starts_at->isSameDay($cursor))->values(),
                'blocks' => $this->scheduleBlocksForDate($cursor),
            ];
        }

        return $grid;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    #[Computed]
    public function miniCalendar(): array
    {
        $anchor = CarbonImmutable::parse($this->selectedDate)->startOfMonth()->startOfWeek(CarbonImmutable::MONDAY);
        $grid = [];

        for ($cursor = $anchor; count($grid) < 42; $cursor = $cursor->addDay()) {
            $grid[] = [
                'date' => $cursor,
                'key' => $cursor->toDateString(),
                'day' => $cursor->day,
                'is_in_month' => $cursor->month === CarbonImmutable::parse($this->selectedDate)->month,
                'is_today' => $cursor->isToday(),
                'is_selected' => $cursor->isSameDay(CarbonImmutable::parse($this->selectedDate)),
                'count' => $this->appointments()->filter(fn (Appointment $appointment): bool => $appointment->starts_at->isSameDay($cursor))->count(),
            ];
        }

        return $grid;
    }

    #[Computed]
    public function selectedAppointment(): ?Appointment
    {
        if ($this->selectedAppointmentId === null) {
            return null;
        }

        return Appointment::query()
            ->with(['branch', 'client', 'service', 'resource', 'professional', 'status', 'payments', 'notes.user', 'histories.user'])
            ->find($this->selectedAppointmentId);
    }

    /**
     * @return SupportCollection<int, array<string, mixed>>
     */
    /**
     * @return SupportCollection<int, array<string, mixed>>
     */
    #[Computed]
    public function timelineEntries(): SupportCollection
    {
        $appointment = $this->selectedAppointment();

        if ($appointment === null) {
            return collect();
        }

        $historyEntries = $appointment->histories()
            ->with('user')
            ->get()
            ->map(fn (AppointmentHistory $history): array => $this->formatHistoryEntry($history))
            ->all();

        $noteEntries = $appointment->notes()
            ->with('user')
            ->get()
            ->map(fn (AppointmentNote $note): array => $this->formatNoteEntry($note))
            ->all();

        return collect(array_merge($historyEntries, $noteEntries))
            ->sortByDesc('created_at')
            ->values();
    }

    private function applyStatusToSelected(string $statusSlug, ChangeAppointmentStatusAction $changeStatus, ?string $reason = null): void
    {
        $appointment = $this->selectedAppointment();

        if ($appointment === null) {
            return;
        }

        if ($statusSlug === AppointmentStatusCatalog::CANCELLED) {
            $this->authorize('cancel', $appointment);
        } elseif ($statusSlug === AppointmentStatusCatalog::NO_SHOW) {
            $this->authorize('markNoShow', $appointment);
        } elseif ($statusSlug === AppointmentStatusCatalog::COMPLETED) {
            $this->authorize('complete', $appointment);
        } else {
            $this->authorize('changeStatus', $appointment);
        }

        $changeStatus->handle($this->authUser(), $appointment, $statusSlug, $reason);
        $this->selectedAppointmentId = $appointment->id;

        Flux::toast(variant: 'success', text: 'Estado actualizado correctamente.');
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private function rangeBounds(): array
    {
        $date = CarbonImmutable::parse($this->selectedDate);

        return match ($this->viewMode) {
            'day' => [$date->startOfDay(), $date->endOfDay()],
            'week' => [$date->startOfWeek(CarbonImmutable::MONDAY), $date->endOfWeek(CarbonImmutable::SUNDAY)],
            'month', 'list' => [$date->startOfMonth()->startOfDay(), $date->endOfMonth()->endOfDay()],
            default => [$date->startOfWeek(CarbonImmutable::MONDAY), $date->endOfWeek(CarbonImmutable::SUNDAY)],
        };
    }

    /**
     * @return array<int, array{id: int, label: string, reason: string, starts_at: CarbonImmutable, ends_at: CarbonImmutable, resource: string|null}>
     */
    private function scheduleBlocksForDate(CarbonImmutable $date): array
    {
        return ScheduleBlock::query()
            ->with(['branch', 'resource'])
            ->whereDate('starts_at', $date->toDateString())
            ->when($this->branchFilterId !== null, fn (Builder $query): Builder => $query->where('branch_id', $this->branchFilterId))
            ->get()
            ->map(fn (ScheduleBlock $block): array => [
                'id' => $block->id,
                'label' => $block->block_type,
                'reason' => $block->reason ?? '',
                'starts_at' => $block->starts_at,
                'ends_at' => $block->ends_at,
                'resource' => $block->resource?->name,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function formatHistoryEntry(AppointmentHistory $history): array
    {
        return [
            'type' => 'history',
            'title' => $history->title,
            'description' => $history->description,
            'created_at' => $history->created_at,
            'user' => $history->user?->fullName() ?? 'System',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatNoteEntry(AppointmentNote $note): array
    {
        return [
            'type' => 'note',
            'title' => $note->is_internal ? 'Internal note' : 'Client note',
            'description' => $note->note,
            'created_at' => $note->created_at,
            'user' => $note->user?->fullName() ?? 'System',
        ];
    }

    private function prefillFormStartAndEnd(): void
    {
        if ($this->form->starts_at !== '' && $this->form->ends_at !== '') {
            return;
        }

        if ($this->form->service_id !== null) {
            $service = Service::query()->find($this->form->service_id);

            if ($service !== null) {
                $this->form->fillFromService($service);

                return;
            }
        }

        $startsAt = CarbonImmutable::parse($this->selectedDate.' 09:00');
        $this->form->starts_at = $startsAt->format('Y-m-d\TH:i');
        $this->form->ends_at = $startsAt->addMinutes(60)->format('Y-m-d\TH:i');
    }

    private function authUser(): User
    {
        $user = auth()->user();

        abort_unless($user instanceof User, 403);

        return $user;
    }

    public function render(): View
    {
        return view('livewire.agenda.index')->layout('layouts.app');
    }
}
