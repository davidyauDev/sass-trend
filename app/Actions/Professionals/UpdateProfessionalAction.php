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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class UpdateProfessionalAction
{
    public function __construct(
        private readonly ProfessionalManagementGuard $guard,
        private readonly AssignUserLocationsAction $assignUserLocations,
        private readonly SendUserInvitationAction $sendInvitation,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(User $actor, Professional $professional, array $data): Professional
    {
        $this->guard->ensureCanManage($actor);

        return DB::transaction(function () use ($professional, $data): Professional {
            $photoPath = $professional->photo_path;

            if (($data['photo'] ?? null) instanceof UploadedFile) {
                $newPhotoPath = $data['photo']->store('professionals', 'public');

                if ($photoPath !== null) {
                    Storage::disk('public')->delete($photoPath);
                }

                $photoPath = $newPhotoPath;
            }

            $professional->update([
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

            $linkedUser = $professional->user;

            if ($data['has_system_access']) {
                if ($linkedUser === null) {
                    $linkedUser = $this->createLinkedUser($professional, (string) $data['email'], $locationIds, $data['is_active']);
                    $professional->forceFill(['user_id' => $linkedUser->id])->save();
                } else {
                    [$firstName, $lastName] = $this->splitName($data['public_name']);

                    $linkedUser->update([
                        'name' => $data['public_name'],
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'email' => $data['email'],
                        'role_id' => $this->professionalRole()->id,
                        'is_active' => $data['is_active'],
                    ]);
                }

                $this->assignUserLocations->handle($linkedUser, $locationIds);
                $linkedUser->services()->sync($data['service_ids']);
            } elseif ($linkedUser !== null) {
                [$firstName, $lastName] = $this->splitName($data['public_name']);

                $linkedUser->update([
                    'name' => $data['public_name'],
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'is_active' => false,
                ]);

                $this->assignUserLocations->handle($linkedUser, $locationIds);
                $linkedUser->services()->sync($data['service_ids']);
            }

            $professional->schedules()->delete();
            $this->syncSchedules($professional, $data['schedules']);

            return $professional->load(['user', 'locations', 'services', 'schedules.breaks', 'groups']);
        });
    }

    /**
     * @param  list<int>  $locationIds
     */
    private function createLinkedUser(Professional $professional, string $email, array $locationIds, bool $isActive): User
    {
        [$firstName, $lastName] = $this->splitName($professional->public_name);

        $user = User::query()->create([
            'name' => $professional->public_name,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'phone' => null,
            'role_id' => $this->professionalRole()->id,
            'password' => Hash::make(Str::password(32)),
            'is_active' => $isActive,
            'is_primary_admin' => false,
            'invited_at' => now(),
            'invitation_accepted_at' => null,
        ]);

        $this->assignUserLocations->handle($user, $locationIds);
        $this->sendInvitation->handle($user);

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
