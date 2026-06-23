<?php

namespace App\Actions\Professionals;

use App\Models\ProfessionalGroup;
use App\Models\User;
use App\Services\Professionals\ProfessionalManagementGuard;

final class DeleteProfessionalGroupAction
{
    public function __construct(
        private readonly ProfessionalManagementGuard $guard,
    ) {}

    public function handle(User $actor, ProfessionalGroup $group): void
    {
        $this->guard->ensureCanManage($actor);

        $group->delete();
    }
}
