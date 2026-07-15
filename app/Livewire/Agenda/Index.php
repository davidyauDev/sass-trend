<?php

namespace App\Livewire\Agenda;

use App\Actions\Agenda\AddAppointmentNoteAction;
use App\Actions\Agenda\ChangeAppointmentStatusAction;
use App\Actions\Agenda\CreateAppointmentAction;
use App\Actions\Agenda\CreateScheduleBlockAction;
use App\Actions\Agenda\RescheduleAppointmentAction;
use App\Actions\Agenda\UpdateAppointmentAction;
use App\Livewire\Forms\AppointmentForm;
use App\Models\Appointment;
use App\Models\AppointmentHistory;
use App\Models\AppointmentNote;
use App\Models\AppointmentPayment;
use App\Models\AppointmentStatus;
use App\Models\Branch;
use App\Models\Client;
use App\Models\Resource;
use App\Models\ScheduleBlock;
use App\Models\Service;
use App\Models\User;
use App\Models\WaitlistEntry;
use App\Services\Agenda\AppointmentAvailabilityService;
use App\Services\Agenda\AppointmentStatusCatalog;
use App\Services\Agenda\AppointmentStatusResolver;
use Carbon\CarbonImmutable;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection as SupportCollection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Agenda')]
class Index extends Component
{
    public AppointmentForm $form;

    #[Url(as: 'view')]
    public string $viewMode = 'month';

    #[Url(as: 'date')]
    public string $selectedDate = '';

    #[Url(as: 'q')]
    public string $search = '';

    public ?int $branchFilterId = null;

    /** @var list<int> */
    public array $professionalFilterIds = [];

    public ?int $resourceFilterId = null;

    public bool $onlyAvailable = false;

    public bool $isFullscreen = false;

    public bool $appointmentPanelOpen = false;

    public bool $appointmentStartedFromCalendarSlot = false;

    public ?int $waitlistEntryPendingBookingId = null;

    public string $appointmentStep = 'picker';

    public string $serviceSearch = '';

    /** @var list<int> */
    public array $selectedServiceIds = [];

    /** @var array<int, int|null> */
    public array $selectedServiceProfessionals = [];

    public string $appointmentTimeDate = '';

    public string $selectedSlotStart = '';

    public string $selectedSlotEnd = '';

    public ?int $selectedAppointmentId = null;

    public string $noteDraft = '';

    public string $statusReason = '';

    public bool $cancellationPanelOpen = false;

    public string $cancellationReason = 'appointment_made_by_mistake';

    public string $slotSearchDate = '';

    public ?int $slotSearchBranchId = null;

    public ?int $slotSearchProfessionalId = null;

    public ?int $slotSearchResourceId = null;

    public string $slotSearchDuration = '60';

    public string $blockStartsAt = '';

    public string $blockEndsAt = '';

    public string $blockType = 'unavailable';

    public string $blockReason = '';

    public bool $blockAllDay = false;

    public ?int $blockProfessionalId = null;

    /** @var array<int, array{starts_at: string, ends_at: string, label: string, branch_name: string}> */
    public array $slotSearchResults = [];

    public function mount(AppointmentStatusResolver $statuses): void
    {
        abort_unless(auth()->user()->can('viewAny', Appointment::class), 403);

        $statuses->ensureAll();

        $this->selectedDate = now()->toDateString();
        $this->slotSearchDate = $this->selectedDate;
        $this->slotSearchBranchId = Branch::query()->orderBy('name')->value('id');
        $this->professionalFilterIds = $this->allProfessionalIds();
        $this->form->resetForm();
    }

    public function updatedViewMode(): void
    {
        if (! in_array($this->viewMode, ['day', 'three_days', 'week', 'month', 'list'], true)) {
            $this->viewMode = 'month';
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
            'three_days' => CarbonImmutable::parse($this->selectedDate)->subDays(3)->toDateString(),
            'week' => CarbonImmutable::parse($this->selectedDate)->subWeek()->toDateString(),
            'month', 'list' => CarbonImmutable::parse($this->selectedDate)->subMonthNoOverflow()->toDateString(),
            default => now()->toDateString(),
        };
    }

    public function next(): void
    {
        $this->selectedDate = match ($this->viewMode) {
            'day' => CarbonImmutable::parse($this->selectedDate)->addDay()->toDateString(),
            'three_days' => CarbonImmutable::parse($this->selectedDate)->addDays(3)->toDateString(),
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
        $this->appointmentStartedFromCalendarSlot = false;
        $this->form->resetForm();
        $this->form->branch_id = $this->branchFilterId ?? Branch::query()->orderBy('name')->value('id');
        $this->form->professional_id = $this->selectedProfessionalFilterId();
        $this->form->resource_id = $this->resourceFilterId;
        $this->form->status_slug = AppointmentStatusCatalog::PENDING;
        $this->prefillFormStartAndEnd();
        $this->appointmentStep = 'picker';
        $this->serviceSearch = '';
        $this->selectedServiceIds = [];
        $this->selectedServiceProfessionals = [];
        $this->appointmentTimeDate = $this->selectedDate;
        $this->selectedSlotStart = '';
        $this->selectedSlotEnd = '';
        $this->appointmentPanelOpen = true;
        $this->resetValidation();
        $this->resetErrorBag();
    }

    public function openCreateModalForDate(string $date): void
    {
        $this->selectedDate = CarbonImmutable::parse($date)->toDateString();
        $this->openCreateModal();
    }

    public function openCreateModalForSlot(string $startsAt, int $professionalId): void
    {
        $slot = CarbonImmutable::parse($startsAt);

        $this->selectedDate = $slot->toDateString();
        $this->openCreateModal();
        $this->form->professional_id = $professionalId;
        $this->form->starts_at = $slot->format('Y-m-d\TH:i');
        $this->form->ends_at = $slot->addHour()->format('Y-m-d\TH:i');
        $this->appointmentTimeDate = $slot->toDateString();
        $this->selectedSlotStart = $this->form->starts_at;
        $this->selectedSlotEnd = $this->form->ends_at;
        $this->appointmentStartedFromCalendarSlot = true;
    }

    public function openScheduleBlockModalForDate(string $date): void
    {
        $this->authorize('create', ScheduleBlock::class);

        $day = CarbonImmutable::parse($date);
        $this->blockStartsAt = $day->setTime(9, 0)->format('Y-m-d\TH:i');
        $this->blockEndsAt = $day->setTime(18, 0)->format('Y-m-d\TH:i');
        $this->blockType = 'unavailable';
        $this->blockReason = '';
        $this->blockAllDay = false;
        $this->blockProfessionalId = $this->selectedProfessionalFilterId();
        $this->resetValidation();

        $this->modal('schedule-block-form')->show();
    }

    public function openScheduleBlockModalForSlot(string $startsAt, int $professionalId): void
    {
        $this->authorize('create', ScheduleBlock::class);

        $slot = CarbonImmutable::parse($startsAt);
        $this->selectedDate = $slot->toDateString();
        $this->blockProfessionalId = $professionalId;
        $this->blockStartsAt = $slot->format('Y-m-d\TH:i');
        $this->blockEndsAt = $slot->addHour()->format('Y-m-d\TH:i');
        $this->blockType = 'unavailable';
        $this->blockReason = '';
        $this->blockAllDay = false;
        $this->resetValidation();

        $this->modal('schedule-block-form')->show();
    }

    public function saveScheduleBlock(CreateScheduleBlockAction $createScheduleBlock): void
    {
        $this->authorize('create', ScheduleBlock::class);

        $validated = $this->validate([
            'blockStartsAt' => ['required', 'date'],
            'blockEndsAt' => ['required', 'date', 'after:blockStartsAt'],
            'blockType' => ['required', 'string', 'max:40'],
            'blockReason' => ['nullable', 'string', 'max:1000'],
            'blockAllDay' => ['boolean'],
        ]);

        $createScheduleBlock->handle($this->authUser(), [
            'branch_id' => $this->branchFilterId,
            'resource_id' => $this->resourceFilterId,
            'user_id' => $this->blockProfessionalId,
            'starts_at' => $validated['blockStartsAt'],
            'ends_at' => $validated['blockEndsAt'],
            'block_type' => $validated['blockType'],
            'reason' => $validated['blockReason'] !== '' ? $validated['blockReason'] : null,
            'is_all_day' => $validated['blockAllDay'],
            'recurrence_rule' => null,
        ]);

        $this->modal('schedule-block-form')->close();
        Flux::toast(variant: 'success', text: 'Tiempo bloqueado agregado correctamente.');
    }

    public function openDayView(string $date): void
    {
        $this->selectedDate = CarbonImmutable::parse($date)->toDateString();
        $this->viewMode = 'day';
    }

    public function openEditModal(int $appointmentId): void
    {
        $appointment = Appointment::query()
            ->with(['status'])
            ->findOrFail($appointmentId);

        $this->authorize('update', $appointment);

        $this->form->fillFromAppointment($appointment);
        $this->selectedServiceIds = [$appointment->service_id];
        $this->selectedServiceProfessionals = [$appointment->service_id => $appointment->professional_id];
        $this->appointmentStep = 'details';
        $this->serviceSearch = '';
        $this->appointmentPanelOpen = true;
        $this->resetValidation();
        $this->resetErrorBag();
    }

    public function closeModal(): void
    {
        $this->appointmentPanelOpen = false;
        $this->appointmentStartedFromCalendarSlot = false;
        $this->appointmentStep = 'picker';
        $this->serviceSearch = '';
        $this->selectedServiceIds = [];
        $this->selectedServiceProfessionals = [];
        $this->selectedSlotStart = '';
        $this->selectedSlotEnd = '';
        $this->waitlistEntryPendingBookingId = null;
        $this->form->resetForm();
        $this->resetValidation();
        $this->resetErrorBag();
    }

    public function openDrawer(int $appointmentId): void
    {
        $this->selectedAppointmentId = $appointmentId;
        $this->appointmentPanelOpen = false;
        $this->noteDraft = '';
        $this->statusReason = '';
    }

    #[On('agenda-book-waitlist')]
    public function openWaitlistBooking(int $entryId): void
    {
        $this->authorize('create', Appointment::class);

        $entry = WaitlistEntry::query()
            ->with('service')
            ->where('status', WaitlistEntry::STATUS_WAITING)
            ->findOrFail($entryId);
        $startsAt = CarbonImmutable::parse($entry->desired_date->toDateString().' '.$entry->available_from);

        $this->form->resetForm();
        $this->form->branch_id = $entry->branch_id;
        $this->form->client_id = $entry->client_id;
        $this->form->fillFromService($entry->service);
        $this->form->professional_id = $entry->professional_id;
        $this->form->starts_at = $startsAt->format('Y-m-d\TH:i');
        $this->form->ends_at = $startsAt->addMinutes($entry->service->duration_minutes)->format('Y-m-d\TH:i');
        $this->form->notes = $entry->notes ?? '';
        $this->selectedServiceIds = [$entry->service_id];
        $this->selectedServiceProfessionals = [$entry->service_id => $entry->professional_id];
        $this->appointmentTimeDate = $entry->desired_date->toDateString();
        $this->appointmentStep = 'details';
        $this->waitlistEntryPendingBookingId = $entry->id;
        $this->appointmentPanelOpen = true;
        $this->resetValidation();
        $this->resetErrorBag();
    }

    public function closeDrawer(): void
    {
        $this->selectedAppointmentId = null;
        $this->noteDraft = '';
        $this->statusReason = '';
    }

    public function save(CreateAppointmentAction $createAppointment, UpdateAppointmentAction $updateAppointment): void
    {
        $isEditing = $this->form->appointmentId !== null;

        if (! $isEditing) {
            $this->authorize('create', Appointment::class);
            $this->form->client_id ??= $this->walkInClientId();
        }

        $this->form->withAvailabilityValidation()->validate();

        $payload = $this->form->payload();
        $actor = $this->authUser();
        $serviceCount = count($this->selectedServiceIds);

        if ($isEditing) {
            $appointment = Appointment::query()->with('status')->findOrFail($this->form->appointmentId);
            $this->authorize('update', $appointment);
            $appointment = $updateAppointment->handle($actor, $appointment, $payload);
        } else {
            $appointment = null;
            $startsAt = CarbonImmutable::parse($payload['starts_at']);

            foreach ($this->selectedServices() as $service) {
                $endsAt = $startsAt->addMinutes($service->duration_minutes);
                $appointment = $createAppointment->handle($actor, array_merge($payload, [
                    'service_id' => $service->id,
                    'professional_id' => $this->selectedServiceProfessionals[$service->id] ?? null,
                    'title' => $service->name,
                    'starts_at' => $startsAt->toDateTimeString(),
                    'ends_at' => $endsAt->toDateTimeString(),
                    'duration_minutes' => $service->duration_minutes,
                    'price' => (float) $service->price,
                ]));
                $startsAt = $endsAt;
            }

            abort_if($appointment === null, 422, 'Debe seleccionar al menos un servicio.');
        }

        if (! $isEditing && $this->waitlistEntryPendingBookingId !== null) {
            WaitlistEntry::query()
                ->whereKey($this->waitlistEntryPendingBookingId)
                ->where('status', WaitlistEntry::STATUS_WAITING)
                ->update([
                    'status' => WaitlistEntry::STATUS_BOOKED,
                    'appointment_id' => $appointment->id,
                    'booked_at' => now(),
                ]);
            $this->dispatch('waitlist-updated');
        }

        $this->selectedDate = CarbonImmutable::parse($payload['starts_at'])->toDateString();
        $this->selectedAppointmentId = $appointment->id;
        $this->appointmentPanelOpen = false;
        $this->closeModal();

        Flux::toast(
            variant: 'success',
            text: $isEditing
                ? 'Cita actualizada correctamente.'
                : ($serviceCount > 1 ? 'Servicios agendados correctamente.' : 'Cita creada correctamente.'),
        );
    }

    public function checkout(CreateAppointmentAction $createAppointment, UpdateAppointmentAction $updateAppointment): void
    {
        $this->save($createAppointment, $updateAppointment);
        $this->redirectRoute('sales.index', navigate: true);
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
        unset($this->selectedAppointment);

        Flux::toast(variant: 'success', text: 'Estado actualizado.');
    }

    public function checkoutSelectedAppointment(): void
    {
        if ($this->selectedAppointmentId === null) {
            return;
        }

        $this->redirectRoute('sales.index', ['appointment' => $this->selectedAppointmentId], navigate: true);
    }

    public function viewSelectedClientProfile(): void
    {
        $appointment = $this->selectedAppointment();

        if ($appointment === null) {
            return;
        }

        $this->redirectRoute('clientes.index', ['q' => $appointment->client->email ?: $appointment->client->fullName()], navigate: true);
    }

    public function completeAppointment(ChangeAppointmentStatusAction $changeStatus): void
    {
        $this->applyStatusToSelected(AppointmentStatusCatalog::COMPLETED, $changeStatus);
    }

    public function markNoShow(ChangeAppointmentStatusAction $changeStatus): void
    {
        $this->applyStatusToSelected(AppointmentStatusCatalog::NO_SHOW, $changeStatus);
    }

    public function openCancellationConfirmation(): void
    {
        $appointment = $this->selectedAppointment();

        if ($appointment === null) {
            return;
        }

        $this->authorize('cancel', $appointment);
        $this->cancellationReason = 'appointment_made_by_mistake';
        $this->cancellationPanelOpen = true;
    }

    public function closeCancellationConfirmation(): void
    {
        $this->cancellationPanelOpen = false;
    }

    public function confirmCancellation(ChangeAppointmentStatusAction $changeStatus): void
    {
        $reason = match ($this->cancellationReason) {
            'none' => 'No se proporcionó ningún motivo.',
            'duplicate' => 'Cita duplicada.',
            'appointment_made_by_mistake' => 'Cita creada por error.',
            'client_not_available' => 'Cliente no disponible.',
            default => 'Cita creada por error.',
        };

        $this->applyStatusToSelected(AppointmentStatusCatalog::CANCELLED, $changeStatus, $reason);
        $this->cancellationPanelOpen = false;
        $this->selectedAppointmentId = null;
        unset($this->selectedAppointment, $this->appointments);
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
        $this->appointmentStep = 'picker';
        $this->serviceSearch = '';
        $this->selectedServiceIds = [];
        $this->selectedServiceProfessionals = [];
        $this->appointmentTimeDate = CarbonImmutable::parse($startsAt)->toDateString();
        $this->selectedSlotStart = '';
        $this->selectedSlotEnd = '';
        $this->appointmentPanelOpen = true;
        $this->resetValidation();
        $this->resetErrorBag();
    }

    public function selectAppointmentService(int $serviceId): void
    {
        $service = Service::query()->where('is_active', true)->findOrFail($serviceId);
        $calendarProfessionalId = $this->appointmentStartedFromCalendarSlot
            ? $this->form->professional_id
            : null;

        if (! in_array($serviceId, $this->selectedServiceIds, true)) {
            $this->selectedServiceIds[] = $serviceId;
            $this->selectedServiceProfessionals[$serviceId] = $calendarProfessionalId
                ?? $this->selectedProfessionalFilterId();
        }

        if ($this->form->service_id === null) {
            $this->form->fillFromService($service);
        }

        if ($this->appointmentStartedFromCalendarSlot) {
            $this->prepareCalendarSlotSummary();

            return;
        }

        $this->appointmentStep = 'services';
    }

    public function showServiceStep(): void
    {
        $this->appointmentStep = 'picker';
    }

    public function showServicesSummary(): void
    {
        $this->appointmentStep = 'services';
    }

    public function removeAppointmentService(int $serviceId): void
    {
        $this->selectedServiceIds = array_values(array_filter(
            $this->selectedServiceIds,
            fn (int $selectedId): bool => $selectedId !== $serviceId,
        ));
        unset($this->selectedServiceProfessionals[$serviceId]);

        if ($this->selectedServiceIds === []) {
            $this->appointmentStep = 'picker';
        }
    }

    public function continueToAppointmentTime(AppointmentAvailabilityService $availability): void
    {
        if ($this->selectedServiceIds === []) {
            return;
        }

        $date = CarbonImmutable::parse($this->appointmentTimeDate !== '' ? $this->appointmentTimeDate : $this->selectedDate);

        if ($date->isBefore(CarbonImmutable::now()->startOfDay())) {
            $date = CarbonImmutable::now()->startOfDay();
        }

        $this->appointmentTimeDate = $date->toDateString();
        $this->loadAppointmentTimeSlots($availability);
        $this->appointmentStep = 'time';
    }

    public function selectAppointmentDate(string $date, AppointmentAvailabilityService $availability): void
    {
        $this->appointmentTimeDate = CarbonImmutable::parse($date)->toDateString();
        $this->selectedSlotStart = '';
        $this->selectedSlotEnd = '';
        $this->loadAppointmentTimeSlots($availability);
    }

    public function selectAppointmentSlot(string $startsAt, string $endsAt): void
    {
        $this->selectedSlotStart = $startsAt;
        $this->selectedSlotEnd = $endsAt;
    }

    public function continueToAppointmentDetails(): void
    {
        if ($this->selectedSlotStart === '' || $this->selectedSlotEnd === '') {
            return;
        }

        $services = $this->selectedServices();
        $firstService = $services->first();

        if (! $firstService instanceof Service) {
            return;
        }

        $this->form->service_id = $firstService->id;
        $this->form->title = $services->pluck('name')->implode(' + ');
        $this->form->duration_minutes = (string) $this->selectedServicesDuration();
        $this->form->price = (string) $this->selectedServicesTotal();
        $this->form->starts_at = CarbonImmutable::parse($this->selectedSlotStart)->format('Y-m-d\TH:i');
        $this->form->ends_at = CarbonImmutable::parse($this->selectedSlotEnd)->format('Y-m-d\TH:i');
        $this->form->professional_id = $this->selectedServiceProfessionals[$firstService->id] ?? null;
        $this->appointmentStep = 'summary';
    }

    public function showAppointmentTime(): void
    {
        $this->appointmentStep = 'time';
    }

    public function serviceDurationLabel(int $minutes): string
    {
        $hours = intdiv($minutes, 60);
        $remainingMinutes = $minutes % 60;

        if ($hours === 0) {
            return $remainingMinutes.' minutos';
        }

        $label = $hours.' '.($hours === 1 ? 'hora' : 'horas');

        return $remainingMinutes > 0 ? $label.' '.$remainingMinutes.' minutos' : $label;
    }

    /**
     * @return array<string, bool|int|string>
     */
    public function appointmentPreviewData(Appointment $appointment): array
    {
        $clientName = $appointment->client->fullName();
        $price = (float) $appointment->price;
        $paid = (float) $appointment->payments->where('status', 'paid')->sum('amount');
        $statusSlug = $appointment->status->slug;

        return [
            'id' => $appointment->id,
            'startsAt' => $appointment->starts_at->format('H:i'),
            'endsAt' => $appointment->ends_at->format('H:i'),
            'clientName' => str_contains(mb_strtolower($clientName), 'sin cita previa') ? 'Sin cita previa' : $clientName,
            'contact' => $appointment->client->email ?: ($appointment->client->phone ?: ''),
            'initial' => mb_strtoupper(mb_substr($clientName, 0, 1)),
            'isWalkIn' => str_contains(mb_strtolower($clientName), 'sin cita previa'),
            'service' => $appointment->service->name,
            'duration' => $this->serviceDurationLabel($appointment->duration_minutes),
            'professional' => $appointment->professional?->fullName() ?? 'Cualquier miembro del equipo',
            'price' => 'PEN '.number_format($price, 0),
            'paymentLabel' => 'PEN '.number_format($price, 0).($paid >= $price ? ' Totalmente pagado' : ' por pagar'),
            'status' => $statusSlug,
            'statusLabel' => match ($statusSlug) {
                AppointmentStatusCatalog::COMPLETED => 'Terminado',
                AppointmentStatusCatalog::NO_SHOW => 'No se presentó',
                AppointmentStatusCatalog::CONFIRMED => 'Confirmado',
                AppointmentStatusCatalog::ARRIVED => 'Llegó',
                AppointmentStatusCatalog::IN_PROGRESS => 'Comenzó',
                AppointmentStatusCatalog::CANCELLED => 'Cancelado',
                default => 'Reservado',
            },
            'serviceCount' => 1,
        ];
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->branchFilterId = null;
        $this->professionalFilterIds = $this->allProfessionalIds();
        $this->resourceFilterId = null;
        $this->onlyAvailable = false;
        $this->viewMode = 'month';
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
     * @return SupportCollection<int, Service>
     */
    #[Computed]
    public function servicesCatalog(): SupportCollection
    {
        return Service::query()
            ->with('category')
            ->where('is_active', true)
            ->search(trim($this->serviceSearch))
            ->orderBy('service_category_id')
            ->orderBy('name')
            ->get();
    }

    /**
     * @return SupportCollection<int, Service>
     */
    #[Computed]
    public function selectedServices(): SupportCollection
    {
        $services = Service::query()
            ->with('category')
            ->whereIn('id', $this->selectedServiceIds)
            ->get()
            ->keyBy('id');
        $selected = [];

        foreach ($this->selectedServiceIds as $serviceId) {
            $service = $services->get($serviceId);

            if ($service instanceof Service) {
                $selected[] = $service;
            }
        }

        return collect($selected);
    }

    #[Computed]
    public function selectedServicesDuration(): int
    {
        return (int) $this->selectedServices()->sum('duration_minutes');
    }

    #[Computed]
    public function selectedServicesTotal(): float
    {
        return (float) $this->selectedServices()->sum(fn (Service $service): float => (float) $service->price);
    }

    /**
     * @return list<array{service: Service, starts_at: CarbonImmutable, ends_at: CarbonImmutable, professional_name: string}>
     */
    #[Computed]
    public function appointmentSummaryServices(): array
    {
        if ($this->form->starts_at === '') {
            return [];
        }

        $professionalIds = collect($this->selectedServiceProfessionals)
            ->filter(fn (?int $professionalId): bool => $professionalId !== null)
            ->values();
        $professionals = User::query()->whereKey($professionalIds)->get()->keyBy('id');
        $startsAt = CarbonImmutable::parse($this->form->starts_at);
        $summary = [];

        foreach ($this->selectedServices() as $service) {
            $endsAt = $startsAt->addMinutes($service->duration_minutes);
            $professionalId = $this->selectedServiceProfessionals[$service->id] ?? null;
            $professional = $professionalId !== null ? $professionals->get($professionalId) : null;
            $summary[] = [
                'service' => $service,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'professional_name' => $professional instanceof User
                    ? $professional->fullName()
                    : 'Cualquier miembro del equipo',
            ];
            $startsAt = $endsAt;
        }

        return $summary;
    }

    /**
     * @return list<array{date: string, day: string, weekday: string, is_selected: bool}>
     */
    #[Computed]
    public function appointmentDateOptions(): array
    {
        $start = CarbonImmutable::parse($this->selectedDate);

        if ($start->isBefore(CarbonImmutable::now()->startOfDay())) {
            $start = CarbonImmutable::now()->startOfDay();
        }
        $options = [];

        for ($offset = 0; $offset < 7; $offset++) {
            $date = $start->addDays($offset);
            $options[] = [
                'date' => $date->toDateString(),
                'day' => $date->format('j'),
                'weekday' => ucfirst($date->translatedFormat('D')),
                'is_selected' => $date->isSameDay(CarbonImmutable::parse($this->appointmentTimeDate)),
            ];
        }

        return $options;
    }

    /**
     * @return SupportCollection<int, Client>
     */
    #[Computed]
    public function clientsCatalog(): SupportCollection
    {
        return Client::query()->orderBy('first_name')->orderBy('last_name')->get();
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
            ->whereHas('status', fn (Builder $query): Builder => $query->where('slug', '!=', AppointmentStatusCatalog::CANCELLED))
            ->search($this->search)
            ->when($this->branchFilterId !== null, fn (Builder $query): Builder => $query->where('branch_id', $this->branchFilterId))
            ->when(
                $this->professionalFilterIds !== $this->allProfessionalIds(),
                fn (Builder $query): Builder => $query->whereIn('professional_id', $this->professionalFilterIds),
            )
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
     * @return SupportCollection<int, User>
     */
    #[Computed]
    public function scheduleProfessionals(): SupportCollection
    {
        return $this->professionalsCatalog()
            ->when(
                $this->professionalFilterIds !== $this->allProfessionalIds(),
                fn (SupportCollection $professionals): SupportCollection => $professionals
                    ->whereIn('id', $this->professionalFilterIds),
            )
            ->values();
    }

    #[Computed]
    public function periodLabel(): string
    {
        $date = CarbonImmutable::parse($this->selectedDate);

        if ($this->viewMode === 'day') {
            return ucfirst($date->translatedFormat('l, j \d\e F'));
        }

        if (in_array($this->viewMode, ['three_days', 'week'], true)) {
            [$start, $end] = $this->rangeBounds();

            if ($start->isSameMonth($end)) {
                return 'Del '.$start->format('j').' al '.$end->translatedFormat('j \d\e F \d\e Y');
            }

            return 'Del '.$start->translatedFormat('j \d\e F').' al '.$end->translatedFormat('j \d\e F \d\e Y');
        }

        return ucfirst($date->translatedFormat('F \d\e Y'));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    #[Computed]
    public function monthGrid(): array
    {
        return $this->buildMonthGrid(CarbonImmutable::parse($this->selectedDate)->startOfMonth());
    }

    /**
     * @return array<int, array{offset: int, key: string, grid: array<int, array<string, mixed>>}>
     */
    #[Computed]
    public function monthSlides(): array
    {
        $selectedMonth = CarbonImmutable::parse($this->selectedDate)->startOfMonth();
        $slides = [];

        foreach ([-1, 0, 1] as $offset) {
            $month = $selectedMonth->addMonths($offset);
            $slides[] = [
                'offset' => $offset,
                'key' => $month->format('Y-m'),
                'grid' => $this->buildMonthGrid($month, $offset === 0),
            ];
        }

        return $slides;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildMonthGrid(CarbonImmutable $month, bool $includeEntries = true): array
    {
        $anchor = $month->startOfWeek(CarbonImmutable::SUNDAY);
        $gridEnd = $month->endOfMonth()->endOfWeek(CarbonImmutable::SATURDAY);
        $selectedDate = CarbonImmutable::parse($this->selectedDate);
        $grid = [];

        for ($cursor = $anchor; $cursor->lessThanOrEqualTo($gridEnd); $cursor = $cursor->addDay()) {
            $grid[] = [
                'date' => $cursor,
                'key' => $cursor->toDateString(),
                'day' => $cursor->day,
                'is_in_month' => $cursor->isSameMonth($month),
                'is_unavailable' => $cursor->lessThan(CarbonImmutable::now()->startOfWeek(CarbonImmutable::MONDAY)),
                'is_today' => $cursor->isToday(),
                'is_selected' => $cursor->isSameDay($selectedDate),
                'appointments' => $includeEntries
                    ? $this->appointments()->filter(fn (Appointment $appointment): bool => $appointment->starts_at->isSameDay($cursor))->values()
                    : collect(),
                'blocks' => $includeEntries ? $this->scheduleBlocksForDate($cursor) : [],
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

    private function prepareCalendarSlotSummary(): void
    {
        if ($this->selectedSlotStart === '') {
            return;
        }

        $services = $this->selectedServices();
        $firstService = $services->first();

        if (! $firstService instanceof Service) {
            return;
        }

        $startsAt = CarbonImmutable::parse($this->selectedSlotStart);
        $endsAt = $startsAt->addMinutes($this->selectedServicesDuration());
        $professionalId = $this->selectedServiceProfessionals[$firstService->id]
            ?? $this->form->professional_id;

        $this->selectedSlotEnd = $endsAt->format('Y-m-d\TH:i');
        $this->form->service_id = $firstService->id;
        $this->form->professional_id = $professionalId;
        $this->form->title = $services->pluck('name')->implode(' + ');
        $this->form->starts_at = $startsAt->format('Y-m-d\TH:i');
        $this->form->ends_at = $this->selectedSlotEnd;
        $this->form->duration_minutes = (string) $this->selectedServicesDuration();
        $this->form->price = (string) $this->selectedServicesTotal();
        $this->appointmentStep = 'summary';
    }

    private function loadAppointmentTimeSlots(AppointmentAvailabilityService $availability): void
    {
        $slots = $availability->searchSlots(
            CarbonImmutable::parse($this->appointmentTimeDate),
            max(15, $this->selectedServicesDuration()),
            $this->form->branch_id,
            null,
            $this->form->resource_id,
            15,
        );

        $this->slotSearchResults = collect($slots)
            ->map(fn (array $slot): array => [
                'starts_at' => $slot['starts_at'],
                'ends_at' => $slot['ends_at'],
                'label' => CarbonImmutable::parse($slot['starts_at'])->format('H:i'),
                'branch_name' => '',
            ])
            ->values()
            ->all();
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
        unset($this->selectedAppointment);

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
            'three_days' => [$date->startOfDay(), $date->addDays(2)->endOfDay()],
            'week' => [$date->startOfWeek(CarbonImmutable::SUNDAY), $date->endOfWeek(CarbonImmutable::SATURDAY)],
            'month', 'list' => [$date->startOfMonth()->startOfDay(), $date->endOfMonth()->endOfDay()],
            default => [$date->startOfWeek(CarbonImmutable::MONDAY), $date->endOfWeek(CarbonImmutable::SUNDAY)],
        };
    }

    /**
     * @return list<int>
     */
    private function allProfessionalIds(): array
    {
        return array_values($this->professionalsCatalog()
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all());
    }

    private function selectedProfessionalFilterId(): ?int
    {
        return count($this->professionalFilterIds) === 1
            ? (int) $this->professionalFilterIds[0]
            : null;
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

    private function walkInClientId(): int
    {
        return Client::query()->firstOrCreate([
            'first_name' => 'Cliente',
            'last_name' => 'sin cita previa',
        ])->id;
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
