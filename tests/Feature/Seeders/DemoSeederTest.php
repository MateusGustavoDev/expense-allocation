<?php

declare(strict_types=1);

use App\Enums\ConversionStatus;
use App\Enums\Currency;
use App\Jobs\ConvertExpenseCurrencyJob;
use App\Models\Company;
use App\Models\Expense;
use App\Models\ExpenseAllocation;
use App\Models\Unit;
use Database\Seeders\DemoSeeder;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Queue::fake();
    $this->travelTo(now('America/Sao_Paulo')->setDate(2026, 9, 30)->setTime(10, 0));
});

it('creates the group with six months of expenses up to yesterday', function () {
    $this->seed(DemoSeeder::class);

    expect(Company::count())->toBe(3)
        ->and(Unit::count())->toBe(5)
        ->and(Expense::count())->toBeGreaterThan(100)
        ->and(Expense::min('date'))->toStartWith('2026-04-')
        ->and(Expense::max('date'))->toBeLessThan('2026-09-30');

    // O mês corrente também tem despesas: o relatório padrão não abre vazio
    expect(Expense::whereBetween('date', ['2026-09-01', '2026-09-29'])->count())->toBeGreaterThan(15);
});

it('goes through the same action as the api', function () {
    $this->seed(DemoSeeder::class);

    // Toda despesa soma 100% e os centavos rateados fecham com o total
    Expense::with('allocations')->each(function (Expense $expense): void {
        expect($expense->allocations->sum('basis_points'))->toBe(10_000)
            ->and($expense->allocations->sum('amount_cents'))->toBe($expense->amount_cents);
    });

    // BRL nasce convertida; USD fica pendente e vai para a fila
    expect(Expense::where('currency', Currency::BRL)->where('conversion_status', '!=', ConversionStatus::Converted)->count())->toBe(0);
    Queue::assertPushed(ConvertExpenseCurrencyJob::class, Expense::where('currency', Currency::USD)->count());
});

it('splits the three-way license with the leftover cent', function () {
    $this->seed(DemoSeeder::class);

    $expense = Expense::where('description', 'like', 'Power BI Pro%')->with('allocations')->firstOrFail();

    expect($expense->allocations->pluck('basis_points')->sort()->values()->all())->toBe([3_333, 3_333, 3_334])
        ->and($expense->allocations->sum('amount_cents'))->toBe($expense->amount_cents);
});

it('does nothing when the demo group already exists', function () {
    $this->seed(DemoSeeder::class);
    $expenses = Expense::count();

    $this->seed(DemoSeeder::class);

    expect(Expense::count())->toBe($expenses)
        ->and(Company::count())->toBe(3)
        ->and(ExpenseAllocation::count())->toBeGreaterThan(0);
});
