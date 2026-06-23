<?php

namespace App\Actions\Professionals;

use App\Models\ProfessionalGroup;
use App\Models\User;
use App\Services\Professionals\ProfessionalManagementGuard;
use Illuminate\Support\Facades\DB;

final class UpdateProfessionalGroupAction
{
    public function __construct(
        private readonly ProfessionalManagementGuard $guard,
    ) {}

    /**
     * @param  array{name:string,location_id:int,is_active:bool,member_ids:list<int>}  $data
     */
    public function handle(User $actor, ProfessionalGroup $group, array $data): ProfessionalGroup
    {
        $this->guard->ensureCanManage($actor);

        return DB::transaction(function () use ($group, $data): ProfessionalGroup {
            $group->update([
                'name' => $data['name'],
                'location_id' => $data['location_id'],
                'is_active' => $data['is_active'],
            ]);

            $group->professionals()->sync($data['member_ids']);

            return $group->load(['location', 'professionals']);
        });
    }
}
