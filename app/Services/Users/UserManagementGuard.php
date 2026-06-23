<?php

namespace App\Services\Users;

use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

final class UserManagementGuard
{
    public function ensureCanManageUsers(User $actor): void
    {
        if ($actor->isAdministratorGeneral() && $actor->is_active) {
            return;
        }

        throw new AuthorizationException('Solo un Administrador General activo puede gestionar usuarios.');
    }

    public function ensureCanDelete(User $actor, User $target): void
    {
        $this->ensureCanManageUsers($actor);

        if ($actor->is($target)) {
            throw ValidationException::withMessages([
                'deletion' => 'No puedes eliminar tu propio usuario.',
            ]);
        }

        if ($target->is_primary_admin) {
            throw ValidationException::withMessages([
                'deletion' => 'No se puede eliminar al Administrador General principal.',
            ]);
        }

        if ($target->isAdministratorGeneral() && $this->activeGeneralAdministratorsCount() <= 1) {
            throw ValidationException::withMessages([
                'deletion' => 'Debe existir al menos un Administrador General activo.',
            ]);
        }
    }

    public function ensureCanChangeStatus(User $actor, User $target, bool $newStatus): void
    {
        $this->ensureCanManageUsers($actor);

        if ($actor->is($target) && ! $newStatus) {
            throw ValidationException::withMessages([
                'status' => 'No puedes desactivar tu propio usuario.',
            ]);
        }

        if (
            ! $newStatus
            && $target->isAdministratorGeneral()
            && $target->is_active
            && $this->activeGeneralAdministratorsCount() <= 1
        ) {
            throw ValidationException::withMessages([
                'status' => 'Debe existir al menos un Administrador General activo.',
            ]);
        }
    }

    public function ensureRoleTransition(User $actor, ?User $target, int $newRoleId, bool $newStatus): void
    {
        $this->ensureCanManageUsers($actor);

        if ($target === null) {
            return;
        }

        $newRoleSlug = Role::query()->find($newRoleId)?->slug;

        if ($target->is_primary_admin && $newRoleSlug !== UserRoleCatalog::GENERAL_ADMIN) {
            throw ValidationException::withMessages([
                'form.role_id' => 'El Administrador General principal debe conservar ese rol.',
            ]);
        }

        if (
            $target->isAdministratorGeneral()
            && (
                $newRoleSlug !== UserRoleCatalog::GENERAL_ADMIN
                || ! $newStatus
            )
            && $this->activeGeneralAdministratorsCount(excluding: $target) <= 0
        ) {
            throw ValidationException::withMessages([
                'form.role_id' => 'Debe existir al menos un Administrador General activo.',
            ]);
        }
    }

    private function activeGeneralAdministratorsCount(?User $excluding = null): int
    {
        return User::query()
            ->whereHas('role', fn ($query) => $query->where('slug', UserRoleCatalog::GENERAL_ADMIN))
            ->where('is_active', true)
            ->when($excluding, fn ($query) => $query->whereKeyNot($excluding->id))
            ->count();
    }
}
