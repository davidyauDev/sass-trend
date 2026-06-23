<?php

namespace App\Services\Commissions;

final class CommissionSourceCatalog
{
    public const APPOINTMENT = 'appointment';

    public const SERVICE_SALE = 'service_sale';

    public const PRODUCT_SALE = 'product_sale';

    public const MEMBERSHIP_SALE = 'membership_sale';

    public const PACKAGE_SALE = 'package_sale';

    public const SUBSCRIPTION_SALE = 'subscription_sale';

    public const WALK_IN = 'walk_in';

    public const MANUAL_SALE = 'manual_sale';

    public const CROSS_SELL = 'cross_sell';

    public const UPSELL = 'upsell';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return [
            self::APPOINTMENT,
            self::SERVICE_SALE,
            self::PRODUCT_SALE,
            self::MEMBERSHIP_SALE,
            self::PACKAGE_SALE,
            self::SUBSCRIPTION_SALE,
            self::WALK_IN,
            self::MANUAL_SALE,
            self::CROSS_SELL,
            self::UPSELL,
        ];
    }
}
