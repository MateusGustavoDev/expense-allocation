<?php

declare(strict_types=1);

use App\Enums\ConversionStatus;
use App\Jobs\ConvertExpenseCurrencyJob;
use App\Livewire\Expenses\ExpensesPage;
use App\Models\Expense;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

it('lists expenses newest first inside the application layout', function () {
    Expense::factory()->create(['description' => 'Aluguel', 'date' => '2026-09-01']);
    Expense::factory()->create(['description' => 'Licença CRM', 'date' => '2026-09-10']);

    $this->get('/expenses')->assertOk()->assertSeeLivewire(ExpensesPage::class);

    Livewire::test(ExpensesPage::class)->assertSeeInOrder(['Licença CRM', 'Aluguel']);
});

it('searches by description or supplier', function () {
    Expense::factory()->create(['description' => 'Licença CRM', 'supplier' => 'Fornecedor X']);
    Expense::factory()->create(['description' => 'Aluguel', 'supplier' => 'Imobiliária Y']);

    Livewire::test(ExpensesPage::class)
        ->set('search', 'imobiliária')
        ->assertSee('Aluguel')
        ->assertDontSee('Licença CRM');
});

it('reads the filters from the url', function () {
    Expense::factory()->create(['description' => 'Dentro do período', 'date' => '2026-09-10']);
    Expense::factory()->create(['description' => 'Fora do período', 'date' => '2026-08-31']);

    Livewire::withQueryParams(['period' => ['from' => '2026-09-01', 'to' => '2026-09-30']])
        ->test(ExpensesPage::class)
        ->assertSee('Dentro do período')
        ->assertDontSee('Fora do período');
});

it('ignores a malformed date coming from the url', function () {
    Expense::factory()->create(['description' => 'Licença CRM']);

    Livewire::withQueryParams(['period' => ['from' => 'ontem', 'to' => null]])
        ->test(ExpensesPage::class)
        ->assertSee('Licença CRM');
});

it('groups pending and failed expenses under the unconverted filter', function () {
    Expense::factory()->create(['description' => 'Aluguel em reais']);
    Expense::factory()->pendingUsd()->create(['description' => 'Licença aguardando cotação']);
    Expense::factory()->pendingUsd()->create(['description' => 'Servidor sem cotação', 'conversion_status' => ConversionStatus::Failed]);

    Livewire::test(ExpensesPage::class)
        ->set('status', ExpensesPage::STATUS_UNCONVERTED)
        ->assertSee(['Licença aguardando cotação', 'Servidor sem cotação'])
        ->assertDontSee('Aluguel em reais');
});

it('sorts by the amount in reais and toggles the direction', function () {
    Expense::factory()->create(['description' => 'Barata', 'amount_cents' => 1_000, 'amount_brl_cents' => 1_000]);
    Expense::factory()->create(['description' => 'Cara', 'amount_cents' => 900_000, 'amount_brl_cents' => 900_000]);

    Livewire::test(ExpensesPage::class)
        ->call('sortBy', 'amount_brl')
        ->assertSet('sortDirection', 'desc')
        ->assertSeeInOrder(['Cara', 'Barata'])
        ->call('sortBy', 'amount_brl')
        ->assertSet('sortDirection', 'asc')
        ->assertSeeInOrder(['Barata', 'Cara']);
});

it('ignores sorting by a column that is not sortable', function () {
    Livewire::test(ExpensesPage::class)
        ->call('sortBy', 'supplier')
        ->assertSet('sortColumn', 'date');
});

it('clears every filter at once', function () {
    Livewire::test(ExpensesPage::class)
        ->set('search', 'crm')
        ->set('currency', 'USD')
        ->set('status', 'pending')
        ->call('resetFilters')
        ->assertSet('search', '')
        ->assertSet('currency', '')
        ->assertSet('status', '');
});

it('tells when no expense matches the filters', function () {
    Expense::factory()->create();

    Livewire::test(ExpensesPage::class)
        ->set('search', 'inexistente')
        ->assertSee('Nenhuma despesa encontrada');
});

it('requeues the conversion of a failed expense', function () {
    Queue::fake();
    $expense = Expense::factory()->pendingUsd()->create(['conversion_status' => ConversionStatus::Failed]);

    Livewire::test(ExpensesPage::class)
        ->call('retryConversion', $expense->id)
        ->assertDispatched('toast', type: 'success');

    expect($expense->refresh()->conversion_status)->toBe(ConversionStatus::Pending);
    Queue::assertPushed(ConvertExpenseCurrencyJob::class);
});

it('warns instead of requeueing an expense that is already converted', function () {
    Queue::fake();
    $expense = Expense::factory()->create();

    Livewire::test(ExpensesPage::class)
        ->call('retryConversion', $expense->id)
        ->assertDispatched('toast', type: 'warning', message: 'A despesa já foi convertida.');

    Queue::assertNothingPushed();
});
