<?php

namespace App\Services\Agenda;

use App\Models\Appointment;
use App\Models\ScheduleBlock;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

final class AppointmentAvailabilityService
{
    /**
     * @return list<array{starts_at: string, ends_at: string}>
     */
    public function searchSlots(
        CarbonImmutable $day,
        int $durationMinutes,
        ?int $branchId = null,
        ?int $professionalId = null,
        ?int $resourceId = null,
        int $stepMinutes = 30,
    ): array {
        $start = $day->startOfDay()->addHours(8);
        $end = $day->startOfDay()->addHours(20);
        $slots = [];

        for ($cursor = $start; $cursor->addMinutes($durationMinutes)->lessThanOrEqualTo($end); $cursor = $cursor->addMinutes($stepMinutes)) {
            $slotEnd = $cursor->addMinutes($durationMinutes);

            if ($this->hasConflict($cursor, $slotEnd, null, $branchId, $professionalId, $resourceId)) {
                continue;
            }

            $slots[] = [
                'starts_at' => $cursor->toDateTimeString(),
                'ends_at' => $slotEnd->toDateTimeString(),
            ];
        }

        return $slots;
    }

    public function ensureAvailability(
        CarbonImmutable $startsAt,
        CarbonImmutable $endsAt,
        ?int $appointmentId = null,
        ?int $branchId = null,
        ?int $professionalId = null,
        ?int $resourceId = null,
    ): void {
        $conflicts = $this->conflicts($startsAt, $endsAt, $appointmentId, $branchId, $professionalId, $resourceId);

        if ($conflicts->isEmpty()) {
            return;
        }

        throw ValidationException::withMessages([
            'starts_at' => 'Ya existe una reserva o bloqueo en ese horario.',
        ]);
    }

    /**
     * @return Collection<int, string>
     */
    public function conflicts(
        CarbonImmutable $startsAt,
        CarbonImmutable $endsAt,
        ?int $appointmentId = null,
        ?int $branchId = null,
        ?int $professionalId = null,
        ?int $resourceId = null,
    ): Collection {
        $appointments = Appointment::query()
            ->when($appointmentId !== null, fn (Builder $query): Builder => $query->whereKeyNot($appointmentId))
            ->when($branchId !== null, fn (Builder $query): Builder => $query->where('branch_id', $branchId))
            ->where(function (Builder $query) use ($professionalId, $resourceId): void {
                if ($professionalId !== null) {
                    $query->orWhere('professional_id', $professionalId);
                }

                if ($resourceId !== null) {
                    $query->orWhere('resource_id', $resourceId);
                }
            })
            ->where('starts_at', '<', $endsAt)
            ->where('ends_at', '>', $startsAt)
            ->pluck('reference_code')
            ->map(fn (mixed $reference): string => (string) $reference);

        $blocks = ScheduleBlock::query()
            ->when($branchId !== null, fn (Builder $query): Builder => $query->where(function (Builder $blockQuery) use ($branchId, $professionalId, $resourceId): void {
                $blockQuery->whereNull('branch_id');
                if ($branchId !== null) {
                    $blockQuery->orWhere('branch_id', $branchId);
                }
                if ($professionalId !== null) {
                    $blockQuery->orWhere('user_id', $professionalId);
                }
                if ($resourceId !== null) {
                    $blockQuery->orWhere('resource_id', $resourceId);
                }
            }))
            ->where('starts_at', '<', $endsAt)
            ->where('ends_at', '>', $startsAt)
            ->pluck('block_type')
            ->map(fn (mixed $type): string => (string) $type);

        return $appointments->merge($blocks)->values();
    }

    private function hasConflict(
        CarbonImmutable $startsAt,
        CarbonImmutable $endsAt,
        ?int $appointmentId = null,
        ?int $branchId = null,
        ?int $professionalId = null,
        ?int $resourceId = null,
    ): bool {
        return $this->conflicts($startsAt, $endsAt, $appointmentId, $branchId, $professionalId, $resourceId)->isNotEmpty();
    }
}
