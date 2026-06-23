<?php

namespace App\Actions\Agenda;

use App\Models\Appointment;
use App\Models\AppointmentNote;
use App\Models\User;

final class AddAppointmentNoteAction
{
    public function handle(User $actor, Appointment $appointment, string $note, bool $isInternal = true): AppointmentNote
    {
        return AppointmentNote::query()->create([
            'appointment_id' => $appointment->id,
            'user_id' => $actor->id,
            'note' => $note,
            'is_internal' => $isInternal,
        ]);
    }
}
