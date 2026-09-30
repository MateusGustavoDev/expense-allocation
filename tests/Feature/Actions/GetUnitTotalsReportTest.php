<?php

declare(strict_types=1);

use App\Actions\Reports\GetUnitTotalsReport;
use App\Data\UnitTotalsReport;
use App\Enums\ConversionStatus;
use App\Models\Expense;
use App\Models\Unit;
use Carbon\CarbonImmutable;

beforeEach(function () {
    $this->unitA = Unit::factory()->create(['name' => 'Unidade A']);
    $this->unitB = Unit::factory()->create(['name' => 'Unidade B']);
    $this->unitC = Unit::factory()->create(['name' => 'Unidade C']);
});

/**
 * Despesa convertida com as fatias em BRL informadas (centavos por unidade).
 *
 * @param  array<int, int>  $brlCentsByUnit
 */
function convertedExpense(string $date, array $brlCentsByUnit, array $attributes = []): Expense
{
    $total = array_sum($brlCentsByUnit);
    $expense = Expense::factory()->create(['date' => $date, 'amount_cents' => $total, 'amount_brl_cents' => $total, ...$attributes]);

    foreach ($brlCentsByUnit as $unitId => $cents) {
        $expense->allocations()->create([
            'unit_id' => $unitId,
            'basis_points' => intdiv($cents * 10_000, $total),
            'amount_cents' => $cents,
            'amount_brl_cents' => $cents,
        ]);
    }

    return $expense;
}

function september(): UnitTotalsReport
{
    return app(GetUnitTotalsReport::class)->execute(CarbonImmutable::parse('2026-09-01'), CarbonImmutable::parse('2026-09-30'));
}

it('sums the BRL shares of each unit in the period, largest first', function () {
    convertedExpense('2026-09-01', [$this->unitA->id => 5000, $this->unitB->id => 3000]);
    convertedExpense('2026-09-10', [$this->unitA->id => 1000, $this->unitC->id => 1000]);

    $report = september();

    expect(array_map(fn ($unit) => [$unit->unitName, $unit->expensesCount, $unit->totalCents], $report->units))->toBe([
        ['Unidade A', 2, 6000],
        ['Unidade B', 1, 3000],
        ['Unidade C', 1, 1000],
    ])
        ->and($report->totalCents)->toBe(10000)
        ->and($report->convertedCount)->toBe(2);
});

it('closes the unit totals with the period total', function () {
    convertedExpense('2026-09-05', [$this->unitA->id => 3334, $this->unitB->id => 3333, $this->unitC->id => 3333]);

    $report = september();

    expect(array_sum(array_map(fn ($unit) => $unit->totalCents, $report->units)))->toBe($report->totalCents);
});

it('includes both ends of the period and nothing outside it', function () {
    convertedExpense('2026-08-31', [$this->unitA->id => 100]);
    convertedExpense('2026-09-01', [$this->unitA->id => 200]);
    convertedExpense('2026-09-30', [$this->unitA->id => 400]);
    convertedExpense('2026-10-01', [$this->unitA->id => 800]);

    expect(september()->totalCents)->toBe(600);
});

it('lists units without expenses in the period with zero', function () {
    convertedExpense('2026-09-01', [$this->unitA->id => 100]);

    $unitC = collect(september()->units)->firstWhere('unitName', 'Unidade C');

    expect($unitC->totalCents)->toBe(0)
        ->and($unitC->expensesCount)->toBe(0)
        ->and($unitC->shareBasisPoints)->toBe(0);
});

it('computes each unit share of the period total', function () {
    convertedExpense('2026-09-01', [$this->unitA->id => 7500, $this->unitB->id => 2500]);

    expect(array_map(fn ($unit) => $unit->shareBasisPoints, september()->units))->toBe([7500, 2500, 0]);
});

it('leaves pending and failed expenses out of the totals and reports them apart', function () {
    convertedExpense('2026-09-01', [$this->unitA->id => 1000]);
    Expense::factory()->pendingUsd()->create(['date' => '2026-09-10', 'amount_cents' => 150000]);
    Expense::factory()->pendingUsd()->create(['date' => '2026-09-11', 'amount_cents' => 64000, 'conversion_status' => ConversionStatus::Failed]);

    $report = september();

    expect($report->totalCents)->toBe(1000)
        ->and($report->pendingCount)->toBe(1)
        ->and($report->failedCount)->toBe(1)
        ->and($report->isComplete())->toBeFalse()
        ->and($report->unconvertedAmounts)->toHaveCount(1)
        ->and($report->unconvertedAmounts[0]->currency->value)->toBe('USD')
        ->and($report->unconvertedAmounts[0]->amountCents)->toBe(214000);
});

it('returns an empty but complete report for a period without expenses', function () {
    $report = september();

    expect($report->totalCents)->toBe(0)
        ->and($report->convertedCount)->toBe(0)
        ->and($report->isComplete())->toBeTrue()
        ->and($report->units)->toHaveCount(3);
});
