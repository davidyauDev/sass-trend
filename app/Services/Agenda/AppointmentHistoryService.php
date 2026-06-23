<?php

namespace App\Services\Agenda;

use App\Models\Appointment;
use App\Models\AppointmentHistory;
use App\Models\User;

final class AppointmentHistoryService
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function record(
        Appointment $appointment,
        string $action,
        string $title,
        ?string $description = null,
        array $payload = [],
        ?User $user = null,
    ): AppointmentHistory {
        return AppointmentHistory::query()->create([
            'appointment_id' => $appointment->id,
            'user_id' => $user?->id,
            'action' => $action,
            'title' => $title,
            'description' => $description,
            'payload' => $payload,
        ]);
    }
}
