<?php

namespace App\Actions\Users;

use App\Models\Role;
use App\Models\User;
use App\Services\Users\UserManagementGuard;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

final class UpdateUserAction
{
    public function __construct(
        private readonly UserManagementGuard $guard,
        private readonly AssignUserLocationsAction $assignUserLocations,
        private readonly SyncUserPermissionsAction $syncUserPermissions,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(User $actor, User $user, array $data): User
    {
        $this->guard->ensureRoleTransition($actor, $user, $data['role_id'], $data['is_active']);

        return DB::transaction(function () use ($user, $data): User {
            $photoPath = $user->photo_path;

            if (($data['photo'] ?? null) instanceof UploadedFile) {
                $newPhotoPath = $data['photo']->store('users', 'public');

                if ($photoPath !== null) {
                    Storage::disk('public')->delete($photoPath);
                }

                $photoPath = $newPhotoPath;
            }

            $user->update([
                'name' => trim("{$data['first_name']} {$data['last_name']}"),
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'photo_path' => $photoPath,
                'role_id' => $data['role_id'],
                'is_active' => $data['is_active'],
            ]);

            if ($data['password'] !== null) {
                $user->update([
                    'password' => $data['password'],
                ]);
            }

            $this->assignUserLocations->handle($user, $data['location_ids']);
            $role = Role::query()->whereKey($data['role_id'])->firstOrFail();
            $this->syncUserPermissions->handle($user, $role, $data['permission_ids']);

            return $user->load(['locations', 'permissions.permission', 'role.permissions']);
        });
    }
}
