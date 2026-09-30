<?php

declare(strict_types=1);

namespace App\Data;

use Carbon\CarbonImmutable;

/**
 * Total em BRL por unidade num período.
 *
 * Só despesas convertidas entram nos totais; as pendentes e as que falharam são informadas à parte,
 * para deixar claro quando o total do período ainda pode mudar.
 */
final readonly class UnitTotalsReport
{
    /**
     * @param  list<UnitTotal>  $units
     * @param  list<UnconvertedAmount>  $unconvertedAmounts
     */
    public function __construct(
        public CarbonImmutable $from,
        public CarbonImmutable $to,
        public array $units,
        public int $totalCents,
        public int $convertedCount,
        public int $pendingCount,
        public int $failedCount,
        public array $unconvertedAmounts,
    ) {}

    public function isComplete(): bool
    {
        return $this->pendingCount === 0 && $this->failedCount === 0;
    }
}
