<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\Currency;

// Soma, na moeda original, das despesas do período que ainda não têm valor em BRL
final readonly class UnconvertedAmount
{
    public function __construct(
        public Currency $currency,
        public int $amountCents,
    ) {}
}
