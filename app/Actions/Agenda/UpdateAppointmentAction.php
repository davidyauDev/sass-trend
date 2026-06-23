<?php

namespace App\Actions\Agenda;

use App\Models\Appointment;
use App\Models\AppointmentStatus;
use App\Models\User;
use App\Services\Agenda\AppointmentAvailabilityService;
use App\Services\Agenda\AppointmentHistoryService;
use App\Services\Agenda\AppointmentStatusCatalog;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class UpdateAppointmentAction
{
    public function __construct(
        private readonly AppointmentAvailabilityService $availability,
        private readonly AppointmentHistoryService $history,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(User $actor, Appointment $appointment, array $data): Appointment
    {
        return DB::transaction(function () use ($actor, $appointment, $data): Appointment {
            $startsAt = CarbonImmutable::parse((string) $data['starts_at']);
            $endsAt = CarbonImmutable::parse((string) $data['ends_at']);

            $this->availability->ensureAvailability(
                $startsAt,
                $endsAt,
                $appointment->id,
                (int) $data['branch_id'],
                $data['professional_id'] !== null ? (int) $data['professional_id'] : null,
                $data['resource_id'] !== null ? (int) $data['resource_id'] : null,
            );

            $statusId = AppointmentStatus::query()
                ->where('slug', $data['status_slug'] ?? $appointment->status->slug ?? AppointmentStatusCatalog::PENDING)
                ->value('id');

            $appointment->update([
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
                'updated_by' => $actor->id,
            ]);

            $this->history->record(
                $appointment,
                'updated',
                'Appointment updated',
                'The appointment data changed.',
                ['appointment' => $appointment->reference_code],
                $actor,
            );

            return $appointment->load(['branch', 'client', 'service', 'resource', 'professional', 'status', 'notes', 'payments', 'histories']);
        });
    }
}
