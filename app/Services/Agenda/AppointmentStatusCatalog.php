<?php

namespace App\Services\Agenda;

final class AppointmentStatusCatalog
{
    public const PENDING = 'pending';

    public const CONFIRMED = 'confirmed';

    public const IN_PROGRESS = 'in_progress';

    public const COMPLETED = 'completed';

    public const CANCELLED = 'cancelled';

    public const NO_SHOW = 'no_show';

    public const RESCHEDULED = 'rescheduled';

    /**
     * @return list<array{name:string,slug:string,color:string,sort_order:int,is_terminal:bool}>
     */
    public static function definitions(): array
    {
        return [
            ['name' => 'Pending', 'slug' => self::PENDING, 'color' => 'zinc', 'sort_order' => 10, 'is_terminal' => false],
            ['name' => 'Confirmed', 'slug' => self::CONFIRMED, 'color' => 'sky', 'sort_order' => 20, 'is_terminal' => false],
            ['name' => 'In Progress', 'slug' => self::IN_PROGRESS, 'color' => 'amber', 'sort_order' => 30, 'is_terminal' => false],
            ['name' => 'Completed', 'slug' => self::COMPLETED, 'color' => 'emerald', 'sort_order' => 40, 'is_terminal' => true],
            ['name' => 'Cancelled', 'slug' => self::CANCELLED, 'color' => 'red', 'sort_order' => 50, 'is_terminal' => true],
            ['name' => 'No Show', 'slug' => self::NO_SHOW, 'color' => 'rose', 'sort_order' => 60, 'is_terminal' => true],
            ['name' => 'Rescheduled', 'slug' => self::RESCHEDULED, 'color' => 'violet', 'sort_order' => 70, 'is_terminal' => false],
        ];
    }
}
