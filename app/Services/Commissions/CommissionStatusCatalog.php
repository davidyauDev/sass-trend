<?php

namespace App\Services\Commissions;

final class CommissionStatusCatalog
{
    public const DRAFT = 'draft';

    public const GENERATED = 'generated';

    public const PENDING_REVIEW = 'pending_review';

    public const APPROVED = 'approved';

    public const REJECTED = 'rejected';

    public const PAID = 'paid';

    public const CANCELLED = 'cancelled';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return [
            self::DRAFT,
            self::GENERATED,
            self::PENDING_REVIEW,
            self::APPROVED,
            self::REJECTED,
            self::PAID,
            self::CANCELLED,
        ];
    }
}
