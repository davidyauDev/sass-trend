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

        return $this->searchSlotsBetween(
            $start,
            $end,
            $durationMinutes,
            $branchId,
            $professionalId,
            $resourceId,
            $stepMinutes,
        );
    }

    /**
     * @return list<array{starts_at: string, ends_at: string}>
     */
    public function searchSlotsBetween(
        CarbonImmutable $start,
        CarbonImmutable $end,
        int $durationMinutes,
        ?int $branchId = null,
        ?int $professionalId = null,
        ?int $resourceId = null,
        int $stepMinutes = 30,
    ): array {
        $conflictPeriods = $this->conflictPeriods($start, $end, $branchId, $professionalId, $resourceId);
        $slots = [];

        for ($cursor = $start; $cursor->addMinutes($durationMinutes)->lessThanOrEqualTo($end); $cursor = $cursor->addMinutes($stepMinutes)) {
            $slotEnd = $cursor->addMinutes($durationMinutes);

            if ($conflictPeriods->contains(
                fn (array $period): bool => $period['starts_at']->lessThan($slotEnd)
                    && $period['ends_at']->greaterThan($cursor),
            )) {
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

    /**
     * @return Collection<int, array{starts_at: CarbonImmutable, ends_at: CarbonImmutable}>
     */
    private function conflictPeriods(
        CarbonImmutable $startsAt,
        CarbonImmutable $endsAt,
        ?int $branchId = null,
        ?int $professionalId = null,
        ?int $resourceId = null,
    ): Collection {
        $appointments = Appointment::query()
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
            ->get(['starts_at', 'ends_at'])
            ->map(fn (Appointment $appointment): array => [
                'starts_at' => CarbonImmutable::parse($appointment->starts_at),
                'ends_at' => CarbonImmutable::parse($appointment->ends_at),
            ]);

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
            ->get(['starts_at', 'ends_at'])
            ->map(fn (ScheduleBlock $block): array => [
                'starts_at' => CarbonImmutable::parse($block->starts_at),
                'ends_at' => CarbonImmutable::parse($block->ends_at),
            ]);

        return $appointments->concat($blocks)->values();
    }
}
