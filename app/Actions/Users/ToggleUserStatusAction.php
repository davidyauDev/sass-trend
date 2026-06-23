<?php

namespace App\Actions\Users;

use App\Models\User;
use App\Services\Users\UserManagementGuard;

final class ToggleUserStatusAction
{
    public function __construct(
        private readonly UserManagementGuard $guard,
    ) {}

    public function handle(User $actor, User $user): User
    {
        $newStatus = ! $user->is_active;

        $this->guard->ensureCanChangeStatus($actor, $user, $newStatus);

        $user->forceFill([
            'is_active' => $newStatus,
        ])->save();

        return $user;
    }
}
