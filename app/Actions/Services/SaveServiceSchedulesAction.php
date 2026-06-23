<?php

namespace App\Actions\Services;

use App\Models\Service;
use App\Models\ServiceSchedule;
use Illuminate\Support\Carbon;

final class SaveServiceSchedulesAction
{
    /**
     * @param  array<int, array<string, mixed>>  $schedules
     */
    public function handle(Service $service, bool $hasSpecialSchedule, array $schedules): void
    {
        ServiceSchedule::query()
            ->whereBelongsTo($service)
            ->delete();

        if (! $hasSpecialSchedule) {
            return;
        }

        $timestamp = Carbon::now();

        ServiceSchedule::query()->insert(
            collect($schedules)
                ->map(fn (array $schedule): array => [
                    'service_id' => $service->id,
                    'day_of_week' => (int) $schedule['day_of_week'],
                    'is_active' => (bool) $schedule['is_active'],
                    'starts_at' => $schedule['is_active'] ? $schedule['starts_at'] : null,
                    'ends_at' => $schedule['is_active'] ? $schedule['ends_at'] : null,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ])
                ->all(),
        );
    }
}
