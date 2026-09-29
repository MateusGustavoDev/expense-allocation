<?php

declare(strict_types=1);

namespace App\Data;

final readonly class AllocationData
{
    public function __construct(
        public int $unitId,
        // Percentual em pontos-base: 10000 = 100%
        public int $basisPoints,
    ) {}
}
