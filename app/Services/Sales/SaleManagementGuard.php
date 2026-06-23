<?php

namespace App\Services\Sales;

use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

final class SaleManagementGuard
{
    public function ensureCanView(User $actor): void
    {
        if ($actor->is_active && ($actor->isAdministrator() || $actor->hasPermission('sales.view'))) {
            return;
        }

        throw new AuthorizationException('No tienes permisos para ver ventas.');
    }

    public function ensureCanCreate(User $actor): void
    {
        if ($actor->is_active && ($actor->isAdministrator() || $actor->hasPermission('sales.create'))) {
            return;
        }

        throw new AuthorizationException('No tienes permisos para crear ventas.');
    }

    public function ensureCanDelete(User $actor): void
    {
        if ($actor->is_active && ($actor->isAdministrator() || $actor->hasPermission('sales.create'))) {
            return;
        }

        throw new AuthorizationException('No tienes permisos para eliminar ventas.');
    }
}
