<?php

namespace App\Services\Website;

use App\Models\Location;
use App\Models\Professional;
use App\Models\Service;
use App\Services\Agenda\AppointmentAvailabilityService;
use Carbon\CarbonImmutable;

final class PublicBookingAvailabilityService
{
    public function __construct(
        private readonly AppointmentAvailabilityService $availability,
    ) {}

    /**
     * @return list<array{starts_at: string, ends_at: string, label: string}>
     */
    public function availableSlots(
        Location $location,
        Service $service,
        Professional $professional,
        CarbonImmutable $day,
        int $stepMinutes = 30,
    ): array {
        if (
            $location->branch_id === null
            || ! $location->accepts_online_bookings
            || ! $location->is_active
            || ! $professional->is_active
            || ! $professional->accepts_online_bookings
            || $professional->user_id === null
        ) {
            return [];
        }

        $dayOfWeek = $day->isoWeekday();
        $locationSchedule = $location->schedules->firstWhere('day_of_week', $dayOfWeek);

        if ($locationSchedule === null || ! $locationSchedule->is_open || $locationSchedule->opens_at === null || $locationSchedule->closes_at === null) {
            return [];
        }

        $windowStartsAt = $day->setTimeFromTimeString($locationSchedule->opens_at);
        $windowEndsAt = $day->setTimeFromTimeString($locationSchedule->closes_at);

        if ($service->has_special_schedule) {
            $serviceSchedule = $service->schedules->firstWhere('day_of_week', $dayOfWeek);

            if ($serviceSchedule === null || ! $serviceSchedule->is_active || $serviceSchedule->starts_at === null || $serviceSchedule->ends_at === null) {
                return [];
            }

            $windowStartsAt = $windowStartsAt->max($day->setTimeFromTimeString($serviceSchedule->starts_at));
            $windowEndsAt = $windowEndsAt->min($day->setTimeFromTimeString($serviceSchedule->ends_at));
        }

        $professionalSchedule = $professional->schedules->firstWhere('day_of_week', $dayOfWeek);

        if ($professionalSchedule === null || ! $professionalSchedule->is_working || $professionalSchedule->starts_at === null || $professionalSchedule->ends_at === null) {
            return [];
        }

        $windowStartsAt = $windowStartsAt->max($day->setTimeFromTimeString($professionalSchedule->starts_at));
        $windowEndsAt = $windowEndsAt->min($day->setTimeFromTimeString($professionalSchedule->ends_at));

        if ($windowEndsAt->lessThanOrEqualTo($windowStartsAt)) {
            return [];
        }

        $durationMinutes = max(15, (int) $service->duration_minutes);
        $availableSlots = $this->availability->searchSlotsBetween(
            $windowStartsAt,
            $windowEndsAt,
            $durationMinutes,
            $location->branch_id,
            $professional->user_id,
            null,
            $stepMinutes,
        );
        $slots = [];

        foreach ($availableSlots as $availableSlot) {
            $cursor = CarbonImmutable::parse($availableSlot['starts_at']);
            $slotEnd = CarbonImmutable::parse($availableSlot['ends_at']);

            $hasBreakConflict = collect($professionalSchedule->breaks)
                ->contains(fn ($break): bool => $day->setTimeFromTimeString($break->starts_at)->lt($slotEnd)
                    && $day->setTimeFromTimeString($break->ends_at)->gt($cursor));

            if ($hasBreakConflict) {
                continue;
            }

            $slots[] = [
                'starts_at' => $availableSlot['starts_at'],
                'ends_at' => $availableSlot['ends_at'],
                'label' => $cursor->format('H:i').' - '.$slotEnd->format('H:i'),
            ];
        }

        return $slots;
    }
}
