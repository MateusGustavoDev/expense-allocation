<?php

declare(strict_types=1);

use App\Enums\ConversionStatus;
use App\Livewire\Reports\UnitTotalsPage;
use App\Models\Expense;
use App\Models\ExpenseAllocation;
use App\Models\Unit;
use Livewire\Livewire;

beforeEach(function () {
    // "Hoje" fixo: 15/09/2026 às 22h em Brasília (já 16/09 em UTC)
    $this->travelTo(now('America/Sao_Paulo')->setDate(2026, 9, 15)->setTime(22, 0));
});

function allocatedExpense(Unit $unit, int $brlCents, string $date): Expense
{
    $expense = Expense::factory()->create(['date' => $date, 'amount_cents' => $brlCents, 'amount_brl_cents' => $brlCents]);
    ExpenseAllocation::factory()->for($expense)->for($unit)->create(['amount_cents' => $brlCents, 'amount_brl_cents' => $brlCents]);

    return $expense;
}

it('redirects the home page to the report', function () {
    $this->get('/')->assertRedirect('/reports');
});

it('renders the report page inside the application layout', function () {
    $this->get('/reports')
        ->assertOk()
        ->assertSeeLivewire(UnitTotalsPage::class)
        ->assertSee('<title>Relatório por unidade · ', false);
});

it('starts on the current month in the business time zone', function () {
    Livewire::test(UnitTotalsPage::class)
        ->assertSet('period', ['from' => '2026-09-01', 'to' => '2026-09-15']);
});

it('shows the BRL total and share of each unit', function () {
    $unitA = Unit::factory()->create(['name' => 'Unidade A', 'slug' => 'unidade-a']);
    $unitB = Unit::factory()->create(['name' => 'Unidade B', 'slug' => 'unidade-b']);
    allocatedExpense($unitA, 750000, '2026-09-01');
    allocatedExpense($unitB, 250000, '2026-09-10');

    Livewire::test(UnitTotalsPage::class)
        ->assertSeeInOrder(['Unidade A', 'R$ 7.500,00', '75,0%', 'Unidade B', 'R$ 2.500,00', '25,0%'])
        ->assertSee('R$ 10.000,00')
        ->assertDontSee('Total do período incompleto');
});

it('recalculates when the period changes', function () {
    $unit = Unit::factory()->create();
    allocatedExpense($unit, 100000, '2026-08-20');

    Livewire::test(UnitTotalsPage::class)
        ->assertDontSee('R$ 1.000,00')
        ->set('period', ['from' => '2026-08-01', 'to' => '2026-08-31'])
        ->assertSee('R$ 1.000,00')
        ->assertSee('01/08/2026 a 31/08/2026');
});

it('reads the period from the URL', function () {
    $unit = Unit::factory()->create();
    allocatedExpense($unit, 100000, '2026-08-20');

    Livewire::withQueryParams(['period' => ['from' => '2026-08-01', 'to' => '2026-08-31']])
        ->test(UnitTotalsPage::class)
        ->assertSet('period.from', '2026-08-01')
        ->assertSee('R$ 1.000,00');
});

it('falls back to the current month when the URL period is invalid', function () {
    Livewire::withQueryParams(['period' => ['from' => '2026-09-30', 'to' => '2026-09-01']])
        ->test(UnitTotalsPage::class)
        ->assertSet('period', ['from' => '2026-09-01', 'to' => '2026-09-15']);
});

it('rejects a period that ends before it starts', function () {
    Livewire::test(UnitTotalsPage::class)
        ->set('period', ['from' => '2026-09-30', 'to' => '2026-09-01'])
        ->assertHasErrors(['period.to' => 'after_or_equal'])
        ->assertSee('Período inválido');
});

it('warns that the total is incomplete while expenses await conversion', function () {
    Unit::factory()->create();
    Expense::factory()->pendingUsd()->create(['date' => '2026-09-05', 'amount_cents' => 150000]);
    Expense::factory()->pendingUsd()->create(['date' => '2026-09-06', 'amount_cents' => 64000, 'conversion_status' => ConversionStatus::Failed]);

    Livewire::test(UnitTotalsPage::class)
        ->assertSee('Total do período incompleto')
        ->assertSee('2 despesas deste período ainda sem valor em reais (US$ 2.140,00)')
        ->assertSee('1 falhou na conversão e precisa ser reprocessada');
});

it('invites to register units when there are none', function () {
    Livewire::test(UnitTotalsPage::class)->assertSee('Nenhuma unidade cadastrada');
});
