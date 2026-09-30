<?php

declare(strict_types=1);

use App\Models\Expense;
use App\Models\ExpenseAllocation;
use App\Models\Unit;

it('returns the BRL total per unit for the period', function () {
    $unit = Unit::factory()->create(['name' => 'Unidade A', 'slug' => 'unidade-a']);
    $expense = Expense::factory()->create(['date' => '2026-09-01', 'amount_cents' => 811845, 'amount_brl_cents' => 811845]);
    ExpenseAllocation::factory()->for($expense)->for($unit)->create(['amount_cents' => 811845, 'amount_brl_cents' => 811845]);
    Expense::factory()->pendingUsd()->create(['date' => '2026-09-29', 'amount_cents' => 32000]);

    $this->getJson('/api/reports/unit-totals?date_from=2026-09-01&date_to=2026-09-30')
        ->assertOk()
        ->assertJsonPath('data.period.from', '2026-09-01')
        ->assertJsonPath('data.total_brl', '8118.45')
        ->assertJsonPath('data.converted_count', 1)
        ->assertJsonPath('data.is_complete', false)
        ->assertJsonPath('data.units.0.unit.slug', 'unidade-a')
        ->assertJsonPath('data.units.0.company.id', $unit->company_id)
        ->assertJsonPath('data.units.0.total_brl', '8118.45')
        ->assertJsonPath('data.units.0.share', '100.00')
        ->assertJsonPath('data.unconverted.pending_count', 1)
        ->assertJsonPath('data.unconverted.amounts.0.currency', 'USD')
        ->assertJsonPath('data.unconverted.amounts.0.amount', '320.00');
});

it('requires the period', function () {
    $this->getJson('/api/reports/unit-totals')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['date_from', 'date_to']);
});

it('rejects a period that ends before it starts', function () {
    $this->getJson('/api/reports/unit-totals?date_from=2026-09-30&date_to=2026-09-01')
        ->assertUnprocessable()
        ->assertJsonValidationErrors('date_to');
});
