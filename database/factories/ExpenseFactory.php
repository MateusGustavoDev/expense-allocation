<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ConversionStatus;
use App\Enums\Currency;
use App\Models\Expense;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Expense>
 */
final class ExpenseFactory extends Factory
{
    /**
     * Despesa em BRL, já convertida.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $amountCents = fake()->numberBetween(1_000, 1_000_000);

        return [
            'description' => fake()->sentence(3),
            'supplier' => fake()->company(),
            'date' => fake()->dateTimeBetween('-1 month')->format('Y-m-d'),
            'amount_cents' => $amountCents,
            'currency' => Currency::BRL,
            'exchange_rate' => '1.000000',
            'amount_brl_cents' => $amountCents,
            'conversion_status' => ConversionStatus::Converted,
            'converted_at' => now(),
        ];
    }

    public function pendingUsd(): self
    {
        return $this->state([
            'currency' => Currency::USD,
            'exchange_rate' => null,
            'amount_brl_cents' => null,
            'conversion_status' => ConversionStatus::Pending,
            'converted_at' => null,
        ]);
    }
}
