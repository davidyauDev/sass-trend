<?php

namespace App\Livewire\SitioWeb;

use App\Actions\Website\BookPublicAppointmentAction;
use App\Models\Appointment;
use App\Models\Location;
use App\Models\Professional;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\WebsiteSetting;
use App\Services\Website\PublicBookingAvailabilityService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Reservas online')]
class Booking extends Component
{
    public ?int $location_id = null;

    public ?int $service_id = null;

    public ?int $professional_id = null;

    public string $selected_date = '';

    public string $selected_starts_at = '';

    public string $first_name = '';

    public string $last_name = '';

    public string $email = '';

    public string $phone = '';

    public string $notes = '';

    public ?int $confirmedAppointmentId = null;

    public int $bookingStep = 1;

    public string $tenantSlug = '';

    public function mount(?Tenant $tenant = null): void
    {
        $currentTenantSlug = tenant('slug');
        $this->tenantSlug = $tenant !== null
            ? $tenant->slug
            : (is_string($currentTenantSlug) ? $currentTenantSlug : '');
        $this->selected_date = now()->toDateString();
        $this->location_id = $this->settings()->primary_location_id
            ?? $this->locations()->first()?->id;
    }

    public function updatedLocationId(): void
    {
        $this->service_id = null;
        $this->professional_id = null;
        $this->selected_starts_at = '';
        $this->confirmedAppointmentId = null;
    }

    public function updatedServiceId(): void
    {
        $this->professional_id = null;
        $this->selected_starts_at = '';
        $this->confirmedAppointmentId = null;
    }

    public function updatedProfessionalId(): void
    {
        $this->selected_starts_at = '';
        $this->confirmedAppointmentId = null;
    }

    public function updatedSelectedDate(): void
    {
        $this->selected_starts_at = '';
        $this->confirmedAppointmentId = null;
    }

    public function selectSlot(string $startsAt): void
    {
        $this->selected_starts_at = $startsAt;
        $this->confirmedAppointmentId = null;
    }

    public function selectBookingService(int $serviceId): void
    {
        $service = $this->services()->firstWhere('id', $serviceId);

        if (! $service instanceof Service) {
            return;
        }

        $this->service_id = $service->id;
        $this->professional_id = null;
        $this->selected_starts_at = '';
        $this->confirmedAppointmentId = null;
        $this->resetValidation();
    }

    public function selectBookingProfessional(int $professionalId): void
    {
        $professional = $this->professionals()->firstWhere('id', $professionalId);

        if (! $professional instanceof Professional) {
            return;
        }

        $this->professional_id = $professional->id;
        $this->selected_starts_at = '';
        $this->confirmedAppointmentId = null;
        $this->resetValidation();
    }

    public function selectBookingDate(string $date): void
    {
        $selectedDate = CarbonImmutable::parse($date)->startOfDay();

        if ($selectedDate->isBefore(now()->startOfDay())) {
            return;
        }

        $this->selected_date = $selectedDate->toDateString();
        $this->selected_starts_at = '';
        $this->confirmedAppointmentId = null;
        $this->resetValidation('starts_at');
    }

    public function continueBooking(): void
    {
        $this->resetValidation();

        if ($this->bookingStep === 1) {
            if ($this->location_id === null || ! $this->services()->contains('id', $this->service_id)) {
                $this->addError('service_id', 'Selecciona un servicio disponible para continuar.');

                return;
            }

            $this->bookingStep = 2;

            return;
        }

        if ($this->bookingStep === 2) {
            if (! $this->professionals()->contains('id', $this->professional_id)) {
                $this->addError('professional_id', 'Selecciona un profesional para continuar.');

                return;
            }

            $this->bookingStep = 3;

            return;
        }

        if ($this->bookingStep === 3) {
            if ($this->selected_starts_at === '') {
                $this->addError('starts_at', 'Selecciona un horario para continuar.');

                return;
            }

            $this->bookingStep = 4;
        }
    }

    public function previousBookingStep(): void
    {
        $this->bookingStep = max(1, $this->bookingStep - 1);
        $this->resetValidation();
    }

    public function resetBookingFlow(): void
    {
        $this->reset([
            'service_id',
            'professional_id',
            'selected_starts_at',
            'first_name',
            'last_name',
            'email',
            'phone',
            'notes',
            'confirmedAppointmentId',
        ]);
        $this->bookingStep = 1;
        $this->selected_date = now()->toDateString();
        $this->location_id = $this->settings()->primary_location_id
            ?? $this->locations()->first()?->id;
        $this->resetValidation();
    }

    public function chooseService(int $serviceId): void
    {
        $service = $this->profileServices()->firstWhere('id', $serviceId);
        $location = $this->profileLocation();

        if (! $service instanceof Service) {
            return;
        }

        if ($location instanceof Location) {
            $this->location_id = $location->id;
        }

        $this->service_id = $service->id;
        $this->professional_id = null;
        $this->selected_starts_at = '';
        $this->confirmedAppointmentId = null;
        $this->bookingStep = 1;

        $this->dispatch('open-booking');
    }

    public function submit(BookPublicAppointmentAction $bookPublicAppointment): void
    {
        if ($this->selected_starts_at === '') {
            $this->addError('starts_at', 'Selecciona un horario antes de confirmar la reserva.');

            return;
        }

        $validated = $this->validate();

        $appointment = $bookPublicAppointment->handle([
            ...$validated,
            'starts_at' => $this->selected_starts_at,
        ]);

        $this->confirmedAppointmentId = $appointment->id;
        $this->reset([
            'location_id',
            'service_id',
            'professional_id',
            'selected_starts_at',
            'first_name',
            'last_name',
            'email',
            'phone',
            'notes',
        ]);
        $this->selected_date = now()->toDateString();
        $this->bookingStep = 4;
    }

    #[Computed]
    public function settings(): WebsiteSetting
    {
        return WebsiteSetting::current()->loadMissing('primaryLocation.schedules');
    }

    #[Computed]
    public function profileLocation(): ?Location
    {
        return $this->settings()->primaryLocation
            ?? $this->locations()->first();
    }

    /**
     * @return Collection<int, Professional>
     */
    #[Computed]
    public function profileProfessionals(): Collection
    {
        $location = $this->profileLocation();

        $query = Professional::query()
            ->where('is_active', true)
            ->orderBy('public_name');

        if (! $location instanceof Location) {
            return $query->get();
        }

        $locationTeam = (clone $query)
            ->whereHas('locations', fn (Builder $locationQuery): Builder => $locationQuery->whereKey($location->id))
            ->get();

        return $locationTeam->isNotEmpty() ? $locationTeam : $query->get();
    }

    /**
     * Services shown in the public profile are broader than the bookable
     * availability query, which still validates staff and location links.
     *
     * @return Collection<int, Service>
     */
    #[Computed]
    public function profileServices(): Collection
    {
        return Service::query()
            ->with('category')
            ->where('is_active', true)
            ->where('is_bookable_online', true)
            ->orderBy('service_category_id')
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, Location>
     */
    #[Computed]
    public function locations(): Collection
    {
        $query = Location::query()
            ->with(['schedules', 'branch'])
            ->where('is_active', true)
            ->where('accepts_online_bookings', true)
            ->whereNotNull('address');

        if ($this->settings()->primary_location_id !== null) {
            $query->orderByRaw('CASE WHEN id = ? THEN 0 ELSE 1 END', [$this->settings()->primary_location_id]);
        }

        return $query->orderBy('name')->get();
    }

    /**
     * @return Collection<int, Service>
     */
    #[Computed]
    public function services(): Collection
    {
        if ($this->location_id === null) {
            return collect();
        }

        return Service::query()
            ->with(['category', 'schedules'])
            ->where('is_active', true)
            ->where('is_bookable_online', true)
            ->whereHas('professionalProfiles', function (Builder $query): void {
                $query
                    ->where('is_active', true)
                    ->where('accepts_online_bookings', true)
                    ->where('has_system_access', true)
                    ->whereNotNull('user_id')
                    ->whereHas('locations', fn (Builder $locationQuery): Builder => $locationQuery->whereKey($this->location_id));
            })
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, Professional>
     */
    #[Computed]
    public function professionals(): Collection
    {
        if ($this->location_id === null || $this->service_id === null) {
            return collect();
        }

        return Professional::query()
            ->with(['locations', 'user'])
            ->where('is_active', true)
            ->where('accepts_online_bookings', true)
            ->where('has_system_access', true)
            ->whereNotNull('user_id')
            ->whereHas('locations', fn (Builder $query): Builder => $query->whereKey($this->location_id))
            ->whereHas('services', fn (Builder $query): Builder => $query->whereKey($this->service_id))
            ->orderBy('public_name')
            ->get();
    }

    /**
     * @return list<array{starts_at: string, ends_at: string, label: string}>
     */
    #[Computed]
    public function availableSlots(): array
    {
        if ($this->location_id === null || $this->service_id === null || $this->professional_id === null || $this->selected_date === '') {
            return [];
        }

        $location = $this->locations()->firstWhere('id', $this->location_id);
        $service = $this->services()->firstWhere('id', $this->service_id);
        $professional = $this->professionals()->firstWhere('id', $this->professional_id);

        if (! $location instanceof Location || ! $service instanceof Service || ! $professional instanceof Professional) {
            return [];
        }

        return app(PublicBookingAvailabilityService::class)->availableSlots(
            $location,
            $service,
            $professional,
            CarbonImmutable::parse($this->selected_date),
        );
    }

    #[Computed]
    public function confirmedAppointment(): ?Appointment
    {
        if ($this->confirmedAppointmentId === null) {
            return null;
        }

        return Appointment::query()
            ->with(['branch', 'client', 'service', 'professional', 'status'])
            ->find($this->confirmedAppointmentId);
    }

    public function render(): View
    {
        abort_unless($this->settings()->is_active, 404);

        return view('livewire.sitio-web.booking')
            ->layout('layouts.public');
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'location_id' => ['required', 'integer', 'exists:locations,id'],
            'service_id' => ['required', 'integer', 'exists:services,id'],
            'professional_id' => ['required', 'integer', 'exists:professionals,id'],
            'selected_date' => ['required', 'date', 'after_or_equal:today'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
