<?php

namespace App\Actions\Services;

use App\Models\Service;
use App\Models\User;
use App\Services\Services\ServiceManagementGuard;

final class ToggleServiceStatusAction
{
    public function __construct(
        private readonly ServiceManagementGuard $guard,
    ) {}

    public function handle(User $actor, Service $service): Service
    {
        $this->guard->ensureCanManage($actor);

        $service->forceFill([
            'is_active' => ! $service->is_active,
        ])->save();

        return $service;
    }
}
