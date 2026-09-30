<?php

declare(strict_types=1);

namespace App\Data;

// Linha do relatório: quanto uma unidade recebeu, em BRL, das despesas convertidas do período
final readonly class UnitTotal
{
    public function __construct(
        public int $unitId,
        public string $unitName,
        public string $unitSlug,
        public int $companyId,
        public string $companyName,
        public int $expensesCount,
        public int $totalCents,
        // Participação no total do período, em pontos-base (10000 = 100%)
        public int $shareBasisPoints,
    ) {}
}
