<?php

declare(strict_types=1);

use App\Enums\ConversionStatus;
use App\Enums\Currency;
use App\Jobs\ConvertExpenseCurrencyJob;
use App\Livewire\Expenses\ExpenseShowPage;
use App\Models\Expense;
use App\Models\ExpenseAllocation;
use App\Models\Unit;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

it('shows the expense with its allocations and the rate used', function () {
    $expense = Expense::factory()->create([
        'description' => 'Licença CRM',
        'currency' => Currency::USD,
        'amount_cents' => 150_000,
        'exchange_rate' => '5.412300',
        'amount_brl_cents' => 811_845,
    ]);
    ExpenseAllocation::factory()->for($expense)->for(Unit::factory()->create(['name' => 'Unidade A']))
        ->create(['basis_points' => 10_000, 'amount_cents' => 150_000, 'amount_brl_cents' => 811_845]);

    $this->get("/expenses/{$expense->id}")
        ->assertOk()
        ->assertSee('Licença CRM')
        ->assertSee(['US$ 1.500,00', 'R$ 8.118,45', '5,4123', 'Unidade A']);
});

it('returns 404 for an expense that does not exist', function () {
    $this->get('/expenses/999')->assertNotFound();
});

it('explains that a failed conversion can be retried', function () {
    Queue::fake();
    $expense = Expense::factory()->pendingUsd()->create(['conversion_status' => ConversionStatus::Failed]);

    Livewire::test(ExpenseShowPage::class, ['expense' => $expense])
        ->call('retryConversion')
        ->assertDispatched('toast', type: 'success');

    expect($expense->refresh()->conversion_status)->toBe(ConversionStatus::Pending);
    Queue::assertPushed(ConvertExpenseCurrencyJob::class);
});
