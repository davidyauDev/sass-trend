<?php

namespace App\DTOs\Commissions;

final readonly class CommissionGenerationData
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public int $branchId,
        public int $userId,
        public string $sourceType,
        public string $sourceReference,
        public float $revenueAmount,
        public ?float $costAmount = null,
        public int $quantity = 1,
        public ?int $commissionRuleId = null,
        public ?int $commissionTypeId = null,
        public ?string $description = null,
        public array $metadata = [],
    ) {}
}
