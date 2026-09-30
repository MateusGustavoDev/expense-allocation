<?php

declare(strict_types=1);

use App\Enums\ConversionStatus;
use App\Jobs\ConvertExpenseCurrencyJob;
use App\Livewire\Expenses\CreateExpensePage;
use App\Models\Expense;
use App\Models\Unit;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

it('starts with today in the business timezone', function () {
    // 22h em Brasília já é o dia seguinte em UTC
    $this->travelTo(now('America/Sao_Paulo')->setDate(2026, 9, 15)->setTime(22, 0));

    $this->get('/expenses/create')->assertOk()->assertSeeLivewire(CreateExpensePage::class);

    Livewire::test(CreateExpensePage::class)->assertSet('form.date', '2026-09-15');
});

it('splits 100% evenly giving the leftover to the first rows', function () {
    Livewire::test(CreateExpensePage::class)
        ->call('addAllocation')
        ->call('addAllocation')
        ->call('splitEvenly')
        ->assertSet('form.allocations.0.percentage', '33,34')
        ->assertSet('form.allocations.1.percentage', '33,33')
        ->assertSet('form.allocations.2.percentage', '33,33');
});

it('previews the cents of each unit with the same splitter as the backend', function () {
    $component = Livewire::test(CreateExpensePage::class)
        ->set('form.amount', '100,00')
        ->call('addAllocation')
        ->call('addAllocation')
        ->set('form.allocations.0.percentage', '33,33')
        ->set('form.allocations.1.percentage', '33,33')
        ->set('form.allocations.2.percentage', '33,34');

    expect($component->instance()->form->preview())->toBe([
        'amountCents' => 10_000,
        'totalBasisPoints' => 10_000,
        'shares' => [0 => 3_333, 1 => 3_333, 2 => 3_334],
    ]);
});

it('never removes the last allocation row', function () {
    Livewire::test(CreateExpensePage::class)
        ->call('removeAllocation', 0)
        ->assertCount('form.allocations', 1);
});

it('creates a brl expense typed in the brazilian format and opens its page', function () {
    [$unitA, $unitB] = Unit::factory()->count(2)->create();

    Livewire::test(CreateExpensePage::class)
        ->set('form.description', '  Aluguel   do escritório ')
        ->set('form.supplier', 'Imobiliária Y')
        ->set('form.date', '2026-09-01')
        ->set('form.amount', '1.500,50')
        ->set('form.allocations', [
            ['unit_id' => (string) $unitA->id, 'percentage' => '60'],
            ['unit_id' => (string) $unitB->id, 'percentage' => '40'],
        ])
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('expenses.show', Expense::sole()));

    $expense = Expense::sole();
    expect($expense)
        ->description->toBe('Aluguel do escritório')
        ->amount_cents->toBe(150_050)
        ->amount_brl_cents->toBe(150_050)
        ->conversion_status->toBe(ConversionStatus::Converted)
        ->and($expense->allocations()->pluck('amount_cents')->all())->toBe([90_030, 60_020]);
});

it('saves a usd expense as pending and queues the conversion', function () {
    Queue::fake();
    $unit = Unit::factory()->create();

    Livewire::test(CreateExpensePage::class)
        ->set('form.description', 'Licença CRM')
        ->set('form.supplier', 'Fornecedor X')
        ->set('form.date', '2026-09-01')
        ->set('form.amount', '1500')
        ->set('form.currency', 'USD')
        ->set('form.allocations.0.unit_id', (string) $unit->id)
        ->call('save')
        ->assertHasNoErrors();

    expect(Expense::sole()->conversion_status)->toBe(ConversionStatus::Pending);
    Queue::assertPushed(ConvertExpenseCurrencyJob::class);
});

it('shows the validation errors under the form fields', function () {
    Livewire::test(CreateExpensePage::class)
        ->set('form.amount', '10,555')
        ->call('save')
        ->assertHasErrors(['form.description', 'form.supplier', 'form.amount', 'form.allocations.0.unit_id'])
        ->assertSee('Informe o valor com até 2 casas decimais');

    expect(Expense::count())->toBe(0);
});

it('rejects allocations that do not add up to 100%', function () {
    [$unitA, $unitB] = Unit::factory()->count(2)->create();

    Livewire::test(CreateExpensePage::class)
        ->set('form.description', 'Licença CRM')
        ->set('form.supplier', 'Fornecedor X')
        ->set('form.date', '2026-09-01')
        ->set('form.amount', '100,00')
        ->set('form.allocations', [
            ['unit_id' => (string) $unitA->id, 'percentage' => '50'],
            ['unit_id' => (string) $unitB->id, 'percentage' => '49,99'],
        ])
        ->call('save')
        ->assertHasErrors('form.allocations');

    expect(Expense::count())->toBe(0);
});
