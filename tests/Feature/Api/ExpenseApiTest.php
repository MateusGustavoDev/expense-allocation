<?php

declare(strict_types=1);

use App\Enums\ConversionStatus;
use App\Models\Expense;
use App\Models\ExpenseAllocation;
use App\Models\Unit;

beforeEach(function () {
    [$this->unitA, $this->unitB, $this->unitC] = Unit::factory()->count(3)->create();
});

function expensePayload(array $overrides = []): array
{
    return array_merge([
        'description' => 'Licença CRM',
        'supplier' => 'Fornecedor X',
        'date' => '2026-09-01',
        'amount' => '100.00',
        'currency' => 'BRL',
        'allocations' => [],
    ], $overrides);
}

function splitInThree(Unit $a, Unit $b, Unit $c): array
{
    return [
        ['unit_id' => $a->id, 'percentage' => '33.34'],
        ['unit_id' => $b->id, 'percentage' => '33.33'],
        ['unit_id' => $c->id, 'percentage' => '33.33'],
    ];
}

it('creates a BRL expense already converted, with cents closing the total', function () {
    $this->postJson('/api/expenses', expensePayload(['allocations' => splitInThree($this->unitA, $this->unitB, $this->unitC)]))
        ->assertCreated()
        ->assertJsonPath('data.amount', '100.00')
        ->assertJsonPath('data.amount_brl', '100.00')
        ->assertJsonPath('data.exchange_rate', '1.000000')
        ->assertJsonPath('data.conversion_status', 'converted')
        ->assertJsonPath('data.allocations.0.amount', '33.34')
        ->assertJsonPath('data.allocations.1.amount', '33.33')
        ->assertJsonPath('data.allocations.2.amount', '33.33')
        ->assertJsonPath('data.allocations.0.amount_brl', '33.34');

    // SUM() do MySQL chega como string pelo PDO
    expect((int) ExpenseAllocation::sum('amount_cents'))->toBe(10000);
});

it('gives the leftover cent to the largest remainder', function () {
    $this->postJson('/api/expenses', expensePayload([
        'amount' => '100.01',
        'allocations' => splitInThree($this->unitA, $this->unitB, $this->unitC),
    ]))
        ->assertCreated()
        ->assertJsonPath('data.allocations.0.amount', '33.35')
        ->assertJsonPath('data.allocations.1.amount', '33.33')
        ->assertJsonPath('data.allocations.2.amount', '33.33');
});

it('creates a USD expense as pending conversion', function () {
    $this->postJson('/api/expenses', expensePayload([
        'amount' => '1500.00',
        'currency' => 'USD',
        'allocations' => [
            ['unit_id' => $this->unitA->id, 'percentage' => '50'],
            ['unit_id' => $this->unitB->id, 'percentage' => '30'],
            ['unit_id' => $this->unitC->id, 'percentage' => '20'],
        ],
    ]))
        ->assertCreated()
        ->assertJsonPath('data.currency', 'USD')
        ->assertJsonPath('data.conversion_status', 'pending')
        ->assertJsonPath('data.exchange_rate', null)
        ->assertJsonPath('data.amount_brl', null)
        ->assertJsonPath('data.allocations.0.amount', '750.00')
        ->assertJsonPath('data.allocations.0.amount_brl', null);

    expect(Expense::sole()->conversion_status)->toBe(ConversionStatus::Pending);
});

it('rejects allocations that do not sum to 100%', function () {
    $this->postJson('/api/expenses', expensePayload([
        'allocations' => [
            ['unit_id' => $this->unitA->id, 'percentage' => '50'],
            ['unit_id' => $this->unitB->id, 'percentage' => '40'],
        ],
    ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['allocations' => 'A soma dos percentuais do rateio precisa ser exatamente 100%.']);

    expect(Expense::count())->toBe(0);
});

it('rejects the same unit twice in the allocation', function () {
    $this->postJson('/api/expenses', expensePayload([
        'allocations' => [
            ['unit_id' => $this->unitA->id, 'percentage' => '50'],
            ['unit_id' => $this->unitA->id, 'percentage' => '50'],
        ],
    ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['allocations.0.unit_id', 'allocations.1.unit_id']);
});

it('rejects an unknown unit', function () {
    $this->postJson('/api/expenses', expensePayload([
        'allocations' => [['unit_id' => 999, 'percentage' => '100']],
    ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('allocations.0.unit_id');
});

it('rejects invalid amounts', function (mixed $amount) {
    $this->postJson('/api/expenses', expensePayload([
        'amount' => $amount,
        'allocations' => [['unit_id' => $this->unitA->id, 'percentage' => '100']],
    ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('amount');
})->with([
    'zero' => ['0.00'],
    'negative' => ['-10.00'],
    'three decimal places' => ['10.555'],
    'comma separator' => ['10,50'],
    'number instead of string' => [10.5],
]);

it('rejects a date that does not exist', function () {
    $this->postJson('/api/expenses', expensePayload([
        'date' => '2026-09-31',
        'allocations' => [['unit_id' => $this->unitA->id, 'percentage' => '100']],
    ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('date');
});

it('rejects an unsupported currency', function () {
    $this->postJson('/api/expenses', expensePayload([
        'currency' => 'EUR',
        'allocations' => [['unit_id' => $this->unitA->id, 'percentage' => '100']],
    ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('currency');
});

it('collapses repeated whitespace in description and supplier', function () {
    $this->postJson('/api/expenses', expensePayload([
        'description' => '  Licença    CRM ',
        'supplier' => 'Fornecedor    X',
        'allocations' => [['unit_id' => $this->unitA->id, 'percentage' => '100']],
    ]))
        ->assertCreated()
        ->assertJsonPath('data.description', 'Licença CRM')
        ->assertJsonPath('data.supplier', 'Fornecedor X');
});

it('shows an expense with its allocations and units', function () {
    $expense = Expense::factory()->create();
    ExpenseAllocation::factory()->for($expense)->for($this->unitA)->create();

    $this->getJson("/api/expenses/{$expense->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $expense->id)
        ->assertJsonPath('data.allocations.0.unit.id', $this->unitA->id)
        ->assertJsonPath('data.allocations.0.percentage', '100.00');
});

it('lists expenses newest first with the allocations count', function () {
    Expense::factory()->create(['date' => '2026-09-01']);
    $newest = Expense::factory()->has(ExpenseAllocation::factory()->count(2), 'allocations')->create(['date' => '2026-09-20']);

    $this->getJson('/api/expenses')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.id', $newest->id)
        ->assertJsonPath('data.0.allocations_count', 2);
});

it('filters expenses by period, currency and status', function () {
    Expense::factory()->create(['date' => '2026-08-31']);
    $inPeriod = Expense::factory()->pendingUsd()->create(['date' => '2026-09-15']);
    Expense::factory()->create(['date' => '2026-09-16']);

    $this->getJson('/api/expenses?date_from=2026-09-01&date_to=2026-09-30&currency=USD&conversion_status=pending')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $inPeriod->id);
});

it('filters expenses allocated to a unit', function () {
    $mine = ExpenseAllocation::factory()->for($this->unitA)->create()->expense_id;
    ExpenseAllocation::factory()->for($this->unitB)->create();

    $this->getJson("/api/expenses?unit_id={$this->unitA->id}")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $mine);
});
