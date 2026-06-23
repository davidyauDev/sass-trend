<?php

namespace App\Actions\Users;

use App\Models\Role;
use App\Models\User;
use App\Services\Users\UserManagementGuard;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

final class CreateUserAction
{
    public function __construct(
        private readonly UserManagementGuard $guard,
        private readonly AssignUserLocationsAction $assignUserLocations,
        private readonly SyncUserPermissionsAction $syncUserPermissions,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(User $actor, array $data): User
    {
        $this->guard->ensureCanManageUsers($actor);

        return DB::transaction(function () use ($data): User {
            $photoPath = ($data['photo'] ?? null) instanceof UploadedFile
                ? $data['photo']->store('users', 'public')
                : null;

            $user = User::create([
                'name' => trim("{$data['first_name']} {$data['last_name']}"),
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'photo_path' => $photoPath,
                'role_id' => $data['role_id'],
                'is_active' => $data['is_active'],
                'is_primary_admin' => false,
                'invited_at' => null,
                'password' => Hash::make($data['password']),
            ]);

            $this->assignUserLocations->handle($user, $data['location_ids']);
            $role = Role::query()->whereKey($data['role_id'])->firstOrFail();
            $this->syncUserPermissions->handle($user, $role, $data['permission_ids']);

            return $user->load(['locations', 'permissions.permission', 'role.permissions']);
        });
    }
}
