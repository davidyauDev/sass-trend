<?php

namespace App\Actions\Agenda;

use App\Models\Appointment;
use App\Models\User;
use App\Services\Agenda\AppointmentAvailabilityService;
use App\Services\Agenda\AppointmentHistoryService;
use App\Services\Agenda\AppointmentStatusCatalog;
use App\Services\Agenda\AppointmentStatusResolver;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class CreateAppointmentAction
{
    public function __construct(
        private readonly AppointmentAvailabilityService $availability,
        private readonly AppointmentHistoryService $history,
        private readonly AppointmentStatusResolver $statuses,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(User $actor, array $data): Appointment
    {
        return DB::transaction(function () use ($actor, $data): Appointment {
            $startsAt = CarbonImmutable::parse((string) $data['starts_at']);
            $endsAt = CarbonImmutable::parse((string) $data['ends_at']);

            $this->availability->ensureAvailability(
                $startsAt,
                $endsAt,
                null,
                (int) $data['branch_id'],
                $data['professional_id'] !== null ? (int) $data['professional_id'] : null,
                $data['resource_id'] !== null ? (int) $data['resource_id'] : null,
            );

            $statusId = $this->statuses->resolveId((string) ($data['status_slug'] ?? AppointmentStatusCatalog::PENDING));

            $appointment = Appointment::query()->create([
                'reference_code' => 'APT-'.Str::upper(Str::random(8)),
                'branch_id' => $data['branch_id'],
                'client_id' => $data['client_id'],
                'service_id' => $data['service_id'],
                'resource_id' => $data['resource_id'],
                'professional_id' => $data['professional_id'],
                'appointment_status_id' => $statusId,
                'title' => $data['title'],
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'duration_minutes' => $data['duration_minutes'],
                'timezone' => $data['timezone'],
                'price' => $data['price'],
                'currency' => $data['currency'],
                'notes' => $data['notes'],
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            $this->history->record(
                $appointment,
                'created',
                'Appointment created',
                'The appointment was created successfully.',
                ['appointment' => $appointment->reference_code],
                $actor,
            );

            return $appointment->load(['branch', 'client', 'service', 'resource', 'professional', 'status', 'notes', 'payments', 'histories']);
        });
    }
}
