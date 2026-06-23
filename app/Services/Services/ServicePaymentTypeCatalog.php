<?php

namespace App\Services\Services;

final class ServicePaymentTypeCatalog
{
    public const NOT_ALLOWED = 'not_allowed';

    public const ALLOWED = 'allowed';

    public const REQUIRED = 'required';

    public const DEPOSIT_REQUIRED = 'deposit_required';

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::NOT_ALLOWED => 'No permite',
            self::ALLOWED => 'Permite',
            self::REQUIRED => 'Requiere pago online',
            self::DEPOSIT_REQUIRED => 'Requiere abono/seña',
        ];
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_keys(self::options());
    }
}
