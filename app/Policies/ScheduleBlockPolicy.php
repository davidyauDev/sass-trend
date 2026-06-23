<?php

namespace App\Policies;

use App\Models\ScheduleBlock;
use App\Models\User;

class ScheduleBlockPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active && $user->hasPermission('schedule_blocks.view');
    }

    public function view(User $user, ScheduleBlock $scheduleBlock): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->is_active && $user->hasPermission('schedule_blocks.create');
    }

    public function update(User $user, ScheduleBlock $scheduleBlock): bool
    {
        return $user->is_active && $user->hasPermission('schedule_blocks.update');
    }

    public function delete(User $user, ScheduleBlock $scheduleBlock): bool
    {
        return $user->is_active && $user->hasPermission('schedule_blocks.delete');
    }
}
