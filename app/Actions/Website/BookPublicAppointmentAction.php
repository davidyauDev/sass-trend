<?php

namespace App\Actions\Website;

use App\Models\Appointment;
use App\Models\Client;
use App\Models\Location;
use App\Models\Professional;
use App\Models\Service;
use App\Services\Agenda\AppointmentHistoryService;
use App\Services\Agenda\AppointmentStatusCatalog;
use App\Services\Agenda\AppointmentStatusResolver;
use App\Services\Website\PublicBookingAvailabilityService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class BookPublicAppointmentAction
{
    public function __construct(
        private readonly PublicBookingAvailabilityService $availability,
        private readonly AppointmentHistoryService $history,
        private readonly AppointmentStatusResolver $statuses,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data): Appointment
    {
        return DB::transaction(function () use ($data): Appointment {
            $location = Location::query()
                ->with('schedules')
                ->findOrFail((int) $data['location_id']);
            $service = Service::query()
                ->with('schedules')
                ->findOrFail((int) $data['service_id']);
            $professional = Professional::query()
                ->with(['locations', 'services', 'user', 'schedules.breaks'])
                ->findOrFail((int) $data['professional_id']);

            $this->ensureBookableConfiguration($location, $service, $professional);

            $startsAt = CarbonImmutable::parse((string) $data['starts_at']);
            $availableSlotStarts = collect($this->availability->availableSlots($location, $service, $professional, $startsAt->startOfDay()))
                ->pluck('starts_at');

            if (! $availableSlotStarts->contains($startsAt->toDateTimeString())) {
                throw ValidationException::withMessages([
                    'starts_at' => 'El horario seleccionado ya no esta disponible.',
                ]);
            }

            $client = $this->upsertClient($data);
            $statusId = $this->statuses->resolveId(AppointmentStatusCatalog::PENDING);

            $appointment = Appointment::query()->create([
                'reference_code' => 'WEB-'.Str::upper(Str::random(8)),
                'branch_id' => $location->branch_id,
                'client_id' => $client->id,
                'service_id' => $service->id,
                'resource_id' => null,
                'professional_id' => $professional->user_id,
                'appointment_status_id' => $statusId,
                'title' => $service->name,
                'starts_at' => $startsAt,
                'ends_at' => $startsAt->addMinutes((int) $service->duration_minutes),
                'duration_minutes' => (int) $service->duration_minutes,
                'timezone' => $location->timezone ?? 'America/Lima',
                'price' => $service->price,
                'currency' => 'PEN',
                'notes' => $data['notes'] ?: null,
                'created_by' => null,
                'updated_by' => null,
            ]);

            $this->history->record(
                $appointment,
                'public_booking_created',
                'Reserva web creada',
                'La cita fue creada desde el sitio web publico.',
                [
                    'location_id' => $location->id,
                    'service_id' => $service->id,
                    'professional_id' => $professional->id,
                    'professional_user_id' => $professional->user_id,
                ],
            );

            return $appointment->load(['branch', 'client', 'service', 'professional', 'status']);
        });
    }

    private function ensureBookableConfiguration(Location $location, Service $service, Professional $professional): void
    {
        if (! $location->is_active || ! $location->accepts_online_bookings || $location->branch_id === null) {
            throw ValidationException::withMessages([
                'location_id' => 'El local seleccionado no esta disponible para reservas online.',
            ]);
        }

        if (! $service->is_active || ! $service->is_bookable_online) {
            throw ValidationException::withMessages([
                'service_id' => 'El servicio seleccionado no esta disponible para reservas online.',
            ]);
        }

        if (! $professional->is_active || ! $professional->accepts_online_bookings) {
            throw ValidationException::withMessages([
                'professional_id' => 'El profesional seleccionado no esta disponible.',
            ]);
        }

        if ($professional->user_id === null || ! $professional->user?->is_active) {
            throw ValidationException::withMessages([
                'professional_id' => 'El profesional seleccionado todavía no tiene acceso activo.',
            ]);
        }

        if (! $service->professionalProfiles()->whereKey($professional->id)->exists()) {
            throw ValidationException::withMessages([
                'professional_id' => 'El profesional no atiende este servicio.',
            ]);
        }

        if (! $professional->locations()->whereKey($location->id)->exists()) {
            throw ValidationException::withMessages([
                'professional_id' => 'El profesional no atiende en el local seleccionado.',
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function upsertClient(array $data): Client
    {
        $email = trim((string) ($data['email'] ?? ''));
        $phone = trim((string) ($data['phone'] ?? ''));
        $firstName = trim((string) $data['first_name']);
        $lastName = trim((string) $data['last_name']);

        $client = null;

        if ($email !== '') {
            $client = Client::query()->where('email', $email)->first();
        }

        if ($client === null && $phone !== '') {
            $client = Client::query()
                ->where('phone', $phone)
                ->where('first_name', $firstName)
                ->where('last_name', $lastName)
                ->first();
        }

        if ($client === null) {
            return Client::query()->create([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email !== '' ? $email : null,
                'phone' => $phone !== '' ? $phone : null,
            ]);
        }

        $client->update([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email !== '' ? $email : $client->email,
            'phone' => $phone !== '' ? $phone : $client->phone,
        ]);

        return $client;
    }
}
