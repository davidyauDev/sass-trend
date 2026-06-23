<?php

namespace App\Actions\Agenda;

use App\Events\Commissions\AppointmentCommissionStatusChanged;
use App\Models\Appointment;
use App\Models\AppointmentStatus;
use App\Models\User;
use App\Services\Agenda\AppointmentHistoryService;
use App\Services\Agenda\AppointmentStatusCatalog;
use Illuminate\Support\Carbon;

final class ChangeAppointmentStatusAction
{
    public function __construct(
        private readonly AppointmentHistoryService $history,
    ) {}

    public function handle(User $actor, Appointment $appointment, string $statusSlug, ?string $reason = null): Appointment
    {
        $status = AppointmentStatus::query()
            ->where('slug', $statusSlug)
            ->firstOrFail();

        $updates = [
            'appointment_status_id' => $status->id,
            'updated_by' => $actor->id,
        ];

        if ($statusSlug === AppointmentStatusCatalog::COMPLETED) {
            $updates['completed_at'] = Carbon::now();
        }

        if ($statusSlug === AppointmentStatusCatalog::CANCELLED) {
            $updates['cancelled_at'] = Carbon::now();
            $updates['cancellation_reason'] = $reason;
        }

        if ($statusSlug === AppointmentStatusCatalog::NO_SHOW) {
            $updates['no_show_at'] = Carbon::now();
        }

        $appointment->update($updates);

        $this->history->record(
            $appointment,
            "status:{$statusSlug}",
            'Status changed',
            $reason,
            ['status' => $statusSlug],
            $actor,
        );

        event(new AppointmentCommissionStatusChanged($appointment->fresh(), $actor, $statusSlug, $reason));

        return $appointment->load(['branch', 'client', 'service', 'resource', 'professional', 'status', 'notes', 'payments', 'histories']);
    }
}
