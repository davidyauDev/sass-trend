<?php

namespace App\Actions\Tenants;

use App\Models\Tenant;
use App\Models\User;

final class UpdateTenantStatusAction
{
    public function activate(User $actor, Tenant $tenant): Tenant
    {
        abort_unless($actor->isAdministratorGeneral() && $actor->is_active, 403);

        $tenant->forceFill([
            'status' => Tenant::STATUS_ACTIVE,
            'suspended_at' => null,
        ])->save();

        return $tenant->refresh();
    }

    public function suspend(User $actor, Tenant $tenant): Tenant
    {
        abort_unless($actor->isAdministratorGeneral() && $actor->is_active, 403);

        $tenant->forceFill([
            'status' => Tenant::STATUS_SUSPENDED,
            'suspended_at' => now(),
        ])->save();

        return $tenant->refresh();
    }
}
