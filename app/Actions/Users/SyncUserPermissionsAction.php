<?php

namespace App\Actions\Users;

use App\Models\Role;
use App\Models\User;
use App\Models\UserPermission;
use Illuminate\Support\Carbon;

final class SyncUserPermissionsAction
{
    /**
     * @param  list<int>  $permissionIds
     */
    public function handle(User $user, Role $role, array $permissionIds): void
    {
        $timestamp = Carbon::now();
        $selectedIds = collect($permissionIds)
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->values();
        $baseIds = $role->permissions()->pluck('permissions.id')->map(fn (mixed $id): int => (int) $id)->values();
        $grantedIds = $selectedIds->diff($baseIds)->values();
        $deniedIds = $baseIds->diff($selectedIds)->values();

        UserPermission::query()
            ->whereBelongsTo($user)
            ->delete();

        if ($grantedIds->isEmpty() && $deniedIds->isEmpty()) {
            return;
        }

        UserPermission::query()->insert(
            $grantedIds
                ->map(fn (int $permissionId): array => [
                    'user_id' => $user->id,
                    'permission_id' => $permissionId,
                    'allowed' => true,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ])
                ->merge($deniedIds->map(fn (int $permissionId): array => [
                    'user_id' => $user->id,
                    'permission_id' => $permissionId,
                    'allowed' => false,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ]))
                ->values()
                ->all(),
        );
    }
}
