<?php

namespace App\DTOs\Commissions;

final readonly class CommissionSettlementData
{
    public function __construct(
        public ?int $branchId,
        public string $periodType,
        public string $startsAt,
        public string $endsAt,
        public ?string $notes = null,
    ) {}
}
