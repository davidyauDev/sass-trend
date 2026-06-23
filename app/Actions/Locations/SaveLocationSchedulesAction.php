<?php

namespace App\Actions\Locations;

use App\Models\Location;
use App\Models\LocationSchedule;
use Illuminate\Support\Carbon;

final class SaveLocationSchedulesAction
{
    /**
     * @param  array<int, array<string, mixed>>  $schedules
     */
    public function handle(Location $location, array $schedules): void
    {
        $timestamp = Carbon::now();

        $payload = collect($schedules)
            ->map(fn (array $schedule): array => [
                'location_id' => $location->id,
                'day_of_week' => (int) $schedule['day_of_week'],
                'is_open' => (bool) $schedule['is_open'],
                'opens_at' => $schedule['is_open'] ? $schedule['opens_at'] : null,
                'closes_at' => $schedule['is_open'] ? $schedule['closes_at'] : null,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ])
            ->all();

        LocationSchedule::query()
            ->whereBelongsTo($location)
            ->delete();

        LocationSchedule::query()->insert($payload);
    }
}
