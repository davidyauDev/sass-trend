<?php

namespace App\Actions\Services;

use App\Models\Professional;
use App\Models\Service;
use Illuminate\Support\Facades\DB;

final class SyncServiceProfessionalsAction
{
    /**
     * @param  list<int>  $professionalIds
     */
    public function handle(Service $service, array $professionalIds): void
    {
        DB::transaction(function () use ($service, $professionalIds): void {
            $service->professionalProfiles()->sync($professionalIds);

            $userIds = Professional::query()
                ->whereKey($professionalIds)
                ->pluck('user_id')
                ->filter()
                ->unique()
                ->values()
                ->all();

            $service->professionals()->sync($userIds);
        });
    }
}
