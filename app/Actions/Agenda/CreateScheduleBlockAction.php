<?php

namespace App\Actions\Agenda;

use App\Models\ScheduleBlock;
use App\Models\User;
use Carbon\CarbonImmutable;

final class CreateScheduleBlockAction
{
    /**
     * @param  array{branch_id:?int,resource_id:?int,user_id:?int,starts_at:string,ends_at:string,block_type:string,reason:?string,is_all_day:bool,recurrence_rule:?string}  $data
     */
    public function handle(User $actor, array $data): ScheduleBlock
    {
        return ScheduleBlock::query()->create([
            'branch_id' => $data['branch_id'],
            'resource_id' => $data['resource_id'],
            'user_id' => $data['user_id'],
            'starts_at' => CarbonImmutable::parse($data['starts_at']),
            'ends_at' => CarbonImmutable::parse($data['ends_at']),
            'block_type' => $data['block_type'],
            'reason' => $data['reason'],
            'is_all_day' => $data['is_all_day'],
            'recurrence_rule' => $data['recurrence_rule'],
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
        ]);
    }
}
