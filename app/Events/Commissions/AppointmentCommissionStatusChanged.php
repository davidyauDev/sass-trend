<?php

namespace App\Events\Commissions;

use App\Models\Appointment;
use App\Models\User;

final readonly class AppointmentCommissionStatusChanged
{
    public function __construct(
        public Appointment $appointment,
        public User $actor,
        public string $statusSlug,
        public ?string $reason = null,
    ) {}
}
