<?php

namespace App\Actions\Services;

use App\Models\Service;

final class SyncServiceProfessionalsAction
{
    /**
     * @param  list<int>  $professionalIds
     */
    public function handle(Service $service, array $professionalIds): void
    {
        $service->professionals()->sync($professionalIds);
    }
}
