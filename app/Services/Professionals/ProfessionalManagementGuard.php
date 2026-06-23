<?php

namespace App\Services\Professionals;

use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

final class ProfessionalManagementGuard
{
    public function ensureCanManage(User $actor): void
    {
        if ($actor->is_active && $actor->isAdministrator()) {
            return;
        }

        throw new AuthorizationException('Solo administradores pueden gestionar profesionales.');
    }
}
