<?php

namespace App\Services\Sales;

final class SalePaymentMethodCatalog
{
    public const BANK_TRANSFER = 'bank_transfer';

    public const DEBIT_CARD = 'debit_card';

    public const CREDIT_CARD = 'credit_card';

    public const CASH = 'cash';

    public const YAPE = 'yape';

    public const PLIN = 'plin';

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::BANK_TRANSFER => 'Transferencia Bancaria',
            self::DEBIT_CARD => 'Tarjeta de Débito',
            self::CREDIT_CARD => 'Tarjeta de Crédito',
            self::CASH => 'Efectivo',
            self::YAPE => 'Yape',
            self::PLIN => 'Plin',
        ];
    }
}
