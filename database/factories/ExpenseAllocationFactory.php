<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Expense;
use App\Models\ExpenseAllocation;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExpenseAllocation>
 */
final class ExpenseAllocationFactory extends Factory
{
    /**
     * Fatia única de 100% da despesa.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'expense_id' => Expense::factory(),
            'unit_id' => Unit::factory(),
            'basis_points' => 10_000,
            'amount_cents' => 10_000,
            'amount_brl_cents' => 10_000,
        ];
    }
}
