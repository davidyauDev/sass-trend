<?php

namespace App\Services\Sales;

final class SaleStatusCatalog
{
    public const DRAFT = 'draft';

    public const PARTIAL = 'partial';

    public const PAID = 'paid';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return [
            self::DRAFT,
            self::PARTIAL,
            self::PAID,
        ];
    }
}
