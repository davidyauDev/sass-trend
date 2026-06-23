<?php

namespace App\Actions\Tenants;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Users\UserRoleCatalog;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

final class CreateTenantAction
{
    /**
     * @param  array{name:string,slug:string,owner_name:string,owner_email:string,owner_password:string,plan:string,status:string}  $data
     */
    public function handle(User $actor, array $data): Tenant
    {
        abort_unless($actor->isAdministratorGeneral() && $actor->is_active, 403);

        $tenant = Tenant::create([
            'name' => $data['name'],
            'slug' => $data['slug'],
            'owner_name' => $data['owner_name'],
            'owner_email' => $data['owner_email'],
            'plan' => $data['plan'],
            'status' => Tenant::STATUS_PENDING,
        ]);

        try {
            $this->provisionTenant($tenant, $data);

            $tenant->forceFill([
                'status' => $data['status'],
                'provisioning_error' => null,
                'provisioned_at' => now(),
                'suspended_at' => $data['status'] === Tenant::STATUS_ACTIVE ? null : now(),
            ])->save();
        } catch (Throwable $exception) {
            $tenant->forceFill([
                'status' => Tenant::STATUS_FAILED,
                'provisioning_error' => Str::limit($exception->getMessage(), 1000, ''),
            ])->save();

            throw ValidationException::withMessages([
                'form.slug' => 'No se pudo provisionar el tenant. Revisa el estado failed para ver el detalle técnico.',
            ]);
        }

        return $tenant->refresh()->load('domains');
    }

    /**
     * @param  array{owner_name:string,owner_email:string,owner_password:string}  $data
     */
    private function provisionTenant(Tenant $tenant, array $data): void
    {
        $role = Role::query()
            ->where('slug', UserRoleCatalog::GENERAL_ADMIN)
            ->firstOrFail();

        User::query()->create([
            'tenant_id' => $tenant->id,
            'name' => $data['owner_name'],
            'first_name' => Str::of($data['owner_name'])->before(' ')->toString(),
            'last_name' => Str::of($data['owner_name'])->after(' ')->toString(),
            'email' => $data['owner_email'],
            'email_verified_at' => now(),
            'password' => Hash::make($data['owner_password']),
            'role_id' => $role->id,
            'is_active' => true,
            'is_primary_admin' => true,
            'invited_at' => now(),
            'invitation_accepted_at' => now(),
        ]);
    }
}
