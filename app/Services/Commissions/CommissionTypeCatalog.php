<?php

namespace App\Services\Commissions;

final class CommissionTypeCatalog
{
    public const PERCENTAGE = 'percentage';

    public const FIXED = 'fixed';

    public const TIERED = 'tiered';

    public const MIXED = 'mixed';

    public const REVENUE_BASED = 'revenue_based';

    public const PROFIT_BASED = 'profit_based';

    public const QUANTITY_BASED = 'quantity_based';

    public const TEAM = 'team';

    public const SPLIT = 'split';

    /**
     * @return list<array{slug:string,name:string}>
     */
    public static function definitions(): array
    {
        return [
            ['slug' => self::PERCENTAGE, 'name' => 'Percentage Commission'],
            ['slug' => self::FIXED, 'name' => 'Fixed Amount Commission'],
            ['slug' => self::TIERED, 'name' => 'Tiered Commission'],
            ['slug' => self::MIXED, 'name' => 'Mixed Commission'],
            ['slug' => self::REVENUE_BASED, 'name' => 'Revenue Based Commission'],
            ['slug' => self::PROFIT_BASED, 'name' => 'Profit Based Commission'],
            ['slug' => self::QUANTITY_BASED, 'name' => 'Quantity Based Commission'],
            ['slug' => self::TEAM, 'name' => 'Team Commission'],
            ['slug' => self::SPLIT, 'name' => 'Split Commission'],
        ];
    }
}
