<?php

namespace App\Services\Commissions;

final class CommissionSettlementStatusCatalog
{
    public const DRAFT = 'draft';

    public const PENDING_APPROVAL = 'pending_approval';

    public const APPROVED = 'approved';

    public const PAID = 'paid';

    public const CANCELLED = 'cancelled';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return [
            self::DRAFT,
            self::PENDING_APPROVAL,
            self::APPROVED,
            self::PAID,
            self::CANCELLED,
        ];
    }
}
