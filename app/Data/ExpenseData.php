<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\Currency;
use Carbon\CarbonImmutable;

// Entrada da criação de despesa, já normalizada (centavos e pontos-base), vinda da API ou do CSV
final readonly class ExpenseData
{
    /**
     * @param  list<AllocationData>  $allocations
     */
    public function __construct(
        public string $description,
        public string $supplier,
        public CarbonImmutable $date,
        public int $amountCents,
        public Currency $currency,
        public array $allocations,
    ) {}
}
