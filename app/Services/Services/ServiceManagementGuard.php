<?php

namespace App\Services\Services;

use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

final class ServiceManagementGuard
{
    public function ensureCanManage(User $actor): void
    {
        if ($actor->isAdministrator()) {
            return;
        }

        throw new AuthorizationException('Solo administradores pueden gestionar servicios.');
    }
}
