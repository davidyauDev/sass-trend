<?php

namespace App\Actions\Professionals;

use App\Actions\Users\AssignUserLocationsAction;
use App\Actions\Users\SendUserInvitationAction;
use App\Models\Location;
use App\Models\Professional;
use App\Models\Role;
use App\Models\User;
use App\Services\Professionals\ProfessionalManagementGuard;
use App\Services\Users\UserRoleCatalog;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class CreateProfessionalAction
{
    public function __construct(
        private readonly ProfessionalManagementGuard $guard,
        private readonly AssignUserLocationsAction $assignUserLocations,
        private readonly SendUserInvitationAction $sendInvitation,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(User $actor, array $data): Professional
    {
        $this->guard->ensureCanManage($actor);

        return DB::transaction(function () use ($data): Professional {
            $photoPath = ($data['photo'] ?? null) instanceof UploadedFile
                ? $data['photo']->store('professionals', 'public')
                : null;

            $professional = Professional::query()->create([
                'public_name' => $data['public_name'],
                'email' => $data['has_system_access'] ? $data['email'] : null,
                'accepts_online_bookings' => $data['accepts_online_bookings'],
                'has_system_access' => $data['has_system_access'],
                'bio' => $data['bio'],
                'photo_path' => $photoPath,
                'is_active' => $data['is_active'],
            ]);

            $locationIds = $this->activeLocationIds();
            $professional->locations()->sync($locationIds);
            $professional->services()->sync($data['service_ids']);

            if ($data['has_system_access'] || $data['accepts_online_bookings']) {
                $user = $this->createLinkedUser(
                    $professional,
                    $data['has_system_access'] ? (string) $data['email'] : null,
                    $locationIds,
                    $data['is_active'],
                    $data['has_system_access'],
                );
                $professional->forceFill(['user_id' => $user->id])->save();
                $user->services()->sync($data['service_ids']);
            }

            $this->syncSchedules($professional, $data['schedules']);

            return $professional->load(['user', 'locations', 'services', 'schedules.breaks', 'groups']);
        });
    }

    /**
     * @param  list<int>  $locationIds
     */
    private function createLinkedUser(Professional $professional, ?string $email, array $locationIds, bool $isActive, bool $hasSystemAccess): User
    {
        $role = $this->professionalRole();
        [$firstName, $lastName] = $this->splitName($professional->public_name);

        $user = User::query()->create([
            'name' => $professional->public_name,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email ?? "booking-professional-{$professional->id}@internal.invalid",
            'phone' => null,
            'role_id' => $role->id,
            'password' => Hash::make(Str::password(32)),
            'is_active' => $hasSystemAccess && $isActive,
            'is_primary_admin' => false,
            'invited_at' => $hasSystemAccess ? now() : null,
            'invitation_accepted_at' => null,
        ]);

        $this->assignUserLocations->handle($user, $locationIds);
        if ($hasSystemAccess) {
            $this->sendInvitation->handle($user);
        }

        return $user;
    }

    /**
     * @return list<int>
     */
    private function activeLocationIds(): array
    {
        return Location::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->values()
            ->pipe(fn ($ids): array => array_values($ids->all()));
    }

    /**
     * @param  array<int, array<string, mixed>>  $schedules
     */
    private function syncSchedules(Professional $professional, array $schedules): void
    {
        foreach ($schedules as $scheduleData) {
            $schedule = $professional->schedules()->create([
                'day_of_week' => $scheduleData['day_of_week'],
                'is_working' => $scheduleData['is_working'],
                'starts_at' => $scheduleData['is_working'] ? $scheduleData['starts_at'] : null,
                'ends_at' => $scheduleData['is_working'] ? $scheduleData['ends_at'] : null,
            ]);

            foreach ($scheduleData['breaks'] as $break) {
                if ($break['starts_at'] === null || $break['ends_at'] === null) {
                    continue;
                }

                $schedule->breaks()->create([
                    'starts_at' => $break['starts_at'],
                    'ends_at' => $break['ends_at'],
                ]);
            }
        }
    }

    private function professionalRole(): Role
    {
        $definition = collect(UserRoleCatalog::definitions())
            ->firstWhere('slug', UserRoleCatalog::PROFESSIONAL);

        return Role::query()->firstOrCreate(
            ['slug' => UserRoleCatalog::PROFESSIONAL],
            [
                'name' => $definition['name'] ?? 'Profesional',
                'description' => $definition['description'] ?? null,
                'is_system' => $definition['is_system'] ?? true,
            ],
        );
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function splitName(string $name): array
    {
        $parts = collect(explode(' ', trim($name)))
            ->filter()
            ->values();

        $firstName = (string) ($parts->first() ?? $name);
        $lastName = (string) $parts->slice(1)->implode(' ');

        return [$firstName, $lastName];
    }
}
