<?php

namespace App\Policies;

use App\Models\Appointment;
use App\Models\User;

class AppointmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active && $user->hasPermission('appointments.view');
    }

    public function view(User $user, Appointment $appointment): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->is_active && $user->hasPermission('appointments.create');
    }

    public function update(User $user, Appointment $appointment): bool
    {
        return $user->is_active && $user->hasPermission('appointments.update');
    }

    public function delete(User $user, Appointment $appointment): bool
    {
        return $user->is_active && $user->hasPermission('appointments.delete');
    }

    public function reschedule(User $user, Appointment $appointment): bool
    {
        return $user->is_active && $user->hasPermission('appointments.reschedule');
    }

    public function cancel(User $user, Appointment $appointment): bool
    {
        return $user->is_active && $user->hasPermission('appointments.cancel');
    }

    public function complete(User $user, Appointment $appointment): bool
    {
        return $user->is_active && $user->hasPermission('appointments.complete');
    }

    public function markNoShow(User $user, Appointment $appointment): bool
    {
        return $user->is_active && $user->hasPermission('appointments.no_show');
    }

    public function changeStatus(User $user, Appointment $appointment): bool
    {
        return $user->is_active && $user->hasPermission('appointments.change_status');
    }
}
