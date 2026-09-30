<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\Currency;
use App\Services\Money\Decimal;
use Carbon\CarbonImmutable;
use UnexpectedValueException;

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

    /**
     * Monta a partir de dados já validados por ExpenseRules.
     *
     * @param  array<string, mixed>  $validated
     */
    public static function fromValidated(array $validated): self
    {
        /** @var array{description: string, supplier: string, date: string, amount: string, currency: string, allocations: list<array{unit_id: int|string, percentage: string}>} $validated */
        return new self(
            description: $validated['description'],
            supplier: $validated['supplier'],
            date: CarbonImmutable::createFromFormat('!Y-m-d', $validated['date']) ?: throw new UnexpectedValueException('Data inválida.'),
            amountCents: Decimal::toHundredths($validated['amount']),
            currency: Currency::from($validated['currency']),
            allocations: array_map(
                fn (array $allocation): AllocationData => new AllocationData(
                    unitId: (int) $allocation['unit_id'],
                    basisPoints: Decimal::toHundredths($allocation['percentage']),
                ),
                $validated['allocations'],
            ),
        );
    }
}
