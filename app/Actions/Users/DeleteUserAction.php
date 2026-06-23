<?php

namespace App\Actions\Users;

use App\Models\User;
use App\Services\Users\UserManagementGuard;
use Illuminate\Support\Facades\Storage;

final class DeleteUserAction
{
    public function __construct(
        private readonly UserManagementGuard $guard,
    ) {}

    public function handle(User $actor, User $user): void
    {
        $this->guard->ensureCanDelete($actor, $user);

        if ($user->photo_path !== null) {
            Storage::disk('public')->delete($user->photo_path);
        }

        $user->delete();
    }
}
