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
        if (! $hasSpecialSchedule) {
            ServiceSchedule::query()
                ->withoutGlobalScopes()
                ->where('service_id', $service->id)
                ->delete();

            return;
        }

        $timestamp = Carbon::now();

        ServiceSchedule::query()->upsert(
            collect($schedules)
                ->map(fn (array $schedule): array => [
                    'tenant_id' => $service->getAttribute('tenant_id'),
                    'service_id' => $service->id,
                    'day_of_week' => (int) $schedule['day_of_week'],
                    'is_active' => (bool) $schedule['is_active'],
                    'starts_at' => $schedule['is_active'] ? $schedule['starts_at'] : null,
                    'ends_at' => $schedule['is_active'] ? $schedule['ends_at'] : null,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ])
                ->all(),
            ['service_id', 'day_of_week'],
            ['tenant_id', 'is_active', 'starts_at', 'ends_at', 'updated_at'],
        );
    }
}
