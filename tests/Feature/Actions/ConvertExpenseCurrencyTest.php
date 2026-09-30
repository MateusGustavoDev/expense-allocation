<?php

declare(strict_types=1);

use App\Actions\Expenses\ConvertExpenseCurrency;
use App\Enums\ConversionStatus;
use App\Models\ExchangeRate;
use App\Models\Expense;
use App\Models\Unit;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    // "Hoje" fixo às 10h de Brasília, para a regra da cotação do dia ser determinística
    $this->travelTo(now('America/Sao_Paulo')->setDate(2026, 9, 15)->setTime(10, 0));
});

// Licença CRM do exemplo: US$ 1.500,00 rateados em 50% / 30% / 20%
function crmExpense(string $date = '2026-09-01'): Expense
{
    $expense = Expense::factory()->pendingUsd()->create(['date' => $date, 'amount_cents' => 150000]);
    [$a, $b, $c] = Unit::factory()->count(3)->create();

    $expense->allocations()->createMany([
        ['unit_id' => $a->id, 'basis_points' => 5000, 'amount_cents' => 75000],
        ['unit_id' => $b->id, 'basis_points' => 3000, 'amount_cents' => 45000],
        ['unit_id' => $c->id, 'basis_points' => 2000, 'amount_cents' => 30000],
    ]);

    return $expense;
}

it('converts a pending expense with the quote of its date', function () {
    fakePtax(5.4123, '2026-09-01');
    $expense = crmExpense();

    expect(app(ConvertExpenseCurrency::class)->execute($expense))->toBeTrue();

    $expense->refresh();
    expect($expense->conversion_status)->toBe(ConversionStatus::Converted)
        ->and($expense->exchange_rate)->toBe('5.412300')
        ->and($expense->exchange_rate_date->toDateString())->toBe('2026-09-01')
        ->and($expense->amount_brl_cents)->toBe(811845)
        ->and($expense->converted_at)->not->toBeNull();
});

it('splits the converted amount so the BRL shares close the total', function () {
    fakePtax(5.4123, '2026-09-01');
    $expense = crmExpense();

    app(ConvertExpenseCurrency::class)->execute($expense);

    // 811845 em 50/30/20 = 405922,5 / 243553,5 / 162369: o centavo que sobra vai para a primeira unidade (empate de resto)
    expect($expense->allocations()->pluck('amount_brl_cents')->all())->toBe([405923, 243553, 162369])
        ->and(array_sum($expense->allocations()->pluck('amount_brl_cents')->all()))->toBe(811845);
});

it('uses the last business day quote for a weekend expense', function () {
    fakePtax(5.3968, '2026-09-11');
    $expense = crmExpense('2026-09-12');

    app(ConvertExpenseCurrency::class)->execute($expense);

    expect($expense->refresh()->exchange_rate_date->toDateString())->toBe('2026-09-11');
});

it('asks the exchange API only once per date', function () {
    fakePtax(5.4123, '2026-09-01');

    app(ConvertExpenseCurrency::class)->execute(crmExpense());
    app(ConvertExpenseCurrency::class)->execute(crmExpense());

    Http::assertSentCount(1);
    expect(ExchangeRate::count())->toBe(1);
});

it('does nothing for an expense that is already converted', function () {
    $expense = Expense::factory()->create();

    expect(app(ConvertExpenseCurrency::class)->execute($expense))->toBeFalse();
    Http::assertNothingSent();
});

it('waits until the day is over to convert an expense dated today', function () {
    $expense = crmExpense('2026-09-15');

    expect(app(ConvertExpenseCurrency::class)->execute($expense))->toBeFalse()
        ->and($expense->refresh()->conversion_status)->toBe(ConversionStatus::Pending);
    Http::assertNothingSent();
});

it('keeps the expense pending when the exchange API is down', function () {
    fakePtaxDown();
    $expense = crmExpense();

    expect(fn () => app(ConvertExpenseCurrency::class)->execute($expense))->toThrow(Exception::class);

    expect($expense->refresh()->conversion_status)->toBe(ConversionStatus::Pending)
        ->and($expense->amount_brl_cents)->toBeNull();
});
