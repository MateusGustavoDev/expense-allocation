<?php

declare(strict_types=1);

use App\Enums\ConversionStatus;
use App\Jobs\ConvertExpenseCurrencyJob;
use App\Models\Expense;
use App\Models\ExpenseAllocation;
use Illuminate\Http\Client\RequestException;
use Illuminate\Queue\Attributes\Backoff;
use Illuminate\Queue\Attributes\Tries;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->travelTo(now('America/Sao_Paulo')->setDate(2026, 9, 15)->setTime(10, 0));
    $this->expense = Expense::factory()->pendingUsd()->create(['date' => '2026-09-01']);
    ExpenseAllocation::factory()->for($this->expense)->create(['amount_brl_cents' => null]);
});

it('retries with growing waits: 1 min, 5 min, 15 min and 1 h', function () {
    $job = new ReflectionClass(ConvertExpenseCurrencyJob::class);

    expect($job->getAttributes(Tries::class)[0]->newInstance()->tries)->toBe(5)
        ->and($job->getAttributes(Backoff::class)[0]->newInstance()->backoff)->toBe([60, 300, 900, 3600]);
});

it('rethrows exchange API failures so the queue tries again', function () {
    fakePtaxDown();

    expect(fn () => app()->call([new ConvertExpenseCurrencyJob($this->expense), 'handle']))
        ->toThrow(RequestException::class);

    expect($this->expense->refresh()->conversion_status)->toBe(ConversionStatus::Pending);
});

it('marks the expense as failed when the retries run out', function () {
    (new ConvertExpenseCurrencyJob($this->expense))->failed(new RuntimeException('API fora do ar'));

    expect($this->expense->refresh()->conversion_status)->toBe(ConversionStatus::Failed);
});

it('does not mark as failed an expense converted in the meantime', function () {
    $this->expense->update(['conversion_status' => ConversionStatus::Converted]);

    (new ConvertExpenseCurrencyJob($this->expense))->failed(new RuntimeException('API fora do ar'));

    expect($this->expense->refresh()->conversion_status)->toBe(ConversionStatus::Converted);
});

it('fails right away when the provider has no quote for the date', function () {
    Http::fake(['olinda.bcb.gov.br/*' => Http::response(['value' => []])]);

    // Na fila síncrona dos testes, fail() dispara o failed() do job imediatamente
    ConvertExpenseCurrencyJob::dispatchSync($this->expense);

    expect($this->expense->refresh()->conversion_status)->toBe(ConversionStatus::Failed);
});

it('converts the expense when dispatched', function () {
    fakePtax(5.4123, '2026-09-01');

    ConvertExpenseCurrencyJob::dispatchSync($this->expense);

    expect($this->expense->refresh()->conversion_status)->toBe(ConversionStatus::Converted);
});
