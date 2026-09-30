<?php

declare(strict_types=1);

namespace App\Data;

use Carbon\CarbonImmutable;

// Cotação devolvida por um provedor de câmbio
final readonly class ExchangeQuote
{
    public function __construct(
        // Quantos BRL vale 1 unidade da moeda, com até 6 casas decimais
        public string $rate,
        // Dia da cotação; em fins de semana e feriados é o último dia útil anterior à data pedida
        public CarbonImmutable $quotedOn,
    ) {}
}
