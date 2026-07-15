<?php

namespace App\Actions\Agenda;

use App\Models\User;
use App\Models\WaitlistEntry;

final class CreateWaitlistEntryAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(User $actor, array $data): WaitlistEntry
    {
        return WaitlistEntry::query()->create(array_merge($data, [
            'status' => WaitlistEntry::STATUS_WAITING,
            'created_by' => $actor->id,
        ]));
    }
}
