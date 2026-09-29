<?php

declare(strict_types=1);

use App\Actions\Expenses\CreateExpense;
use App\Data\AllocationData;
use App\Data\ExpenseData;
use App\Enums\Currency;
use App\Exceptions\InvalidAllocationException;
use App\Models\Expense;
use App\Models\Unit;
use Carbon\CarbonImmutable;

// A Action também é chamada pela importação por CSV, sem Form Request: as regras precisam valer nela

function expenseData(array $allocations): ExpenseData
{
    return new ExpenseData(
        description: 'Licença CRM',
        supplier: 'Fornecedor X',
        date: CarbonImmutable::parse('2026-09-01'),
        amountCents: 150000,
        currency: Currency::USD,
        allocations: $allocations,
    );
}

it('rejects allocations that do not sum to 100%', function () {
    $unit = Unit::factory()->create();

    app(CreateExpense::class)->execute(expenseData([new AllocationData($unit->id, 9000)]));
})->throws(InvalidAllocationException::class, 'A soma dos percentuais do rateio precisa ser exatamente 100%.');

it('rejects the same unit twice', function () {
    $unit = Unit::factory()->create();

    app(CreateExpense::class)->execute(expenseData([new AllocationData($unit->id, 5000), new AllocationData($unit->id, 5000)]));
})->throws(InvalidAllocationException::class, 'Uma unidade não pode aparecer duas vezes no mesmo rateio.');

it('persists nothing when the allocation is invalid', function () {
    $unit = Unit::factory()->create();

    try {
        app(CreateExpense::class)->execute(expenseData([new AllocationData($unit->id, 9000)]));
    } catch (InvalidAllocationException) {
    }

    expect(Expense::count())->toBe(0);
});

it('keeps the allocations in the input order', function () {
    [$first, $second] = Unit::factory()->count(2)->create();

    $expense = app(CreateExpense::class)->execute(expenseData([
        new AllocationData($second->id, 7000),
        new AllocationData($first->id, 3000),
    ]));

    expect($expense->allocations->pluck('unit_id')->all())->toBe([$second->id, $first->id])
        ->and($expense->allocations->pluck('amount_cents')->all())->toBe([105000, 45000]);
});
