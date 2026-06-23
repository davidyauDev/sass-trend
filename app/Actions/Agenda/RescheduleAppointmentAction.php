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

final class RescheduleAppointmentAction
{
    public function __construct(
        private readonly AppointmentAvailabilityService $availability,
        private readonly AppointmentHistoryService $history,
    ) {}

    /**
     * @param  array{starts_at:string, ends_at:string, branch_id:int, professional_id:?int, resource_id:?int}  $data
     */
    public function handle(User $actor, Appointment $appointment, array $data): Appointment
    {
        return DB::transaction(function () use ($actor, $appointment, $data): Appointment {
            $startsAt = CarbonImmutable::parse($data['starts_at']);
            $endsAt = CarbonImmutable::parse($data['ends_at']);

            $this->availability->ensureAvailability(
                $startsAt,
                $endsAt,
                $appointment->id,
                $data['branch_id'],
                $data['professional_id'],
                $data['resource_id'],
            );

            $rescheduledStatusId = AppointmentStatus::query()
                ->where('slug', AppointmentStatusCatalog::RESCHEDULED)
                ->value('id');

            $appointment->update([
                'branch_id' => $data['branch_id'],
                'professional_id' => $data['professional_id'],
                'resource_id' => $data['resource_id'],
                'appointment_status_id' => $rescheduledStatusId,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'updated_by' => $actor->id,
            ]);

            $this->history->record(
                $appointment,
                'rescheduled',
                'Appointment rescheduled',
                'The appointment was moved to a new time.',
                ['starts_at' => $startsAt->toDateTimeString(), 'ends_at' => $endsAt->toDateTimeString()],
                $actor,
            );

            return $appointment->load(['branch', 'client', 'service', 'resource', 'professional', 'status', 'notes', 'payments', 'histories']);
        });
    }
}
