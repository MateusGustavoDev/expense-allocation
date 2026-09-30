<?php

declare(strict_types=1);

use App\Enums\ConversionStatus;
use App\Jobs\ConvertExpenseCurrencyJob;
use App\Models\Expense;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Queue::fake();
    $this->travelTo(now('America/Sao_Paulo')->setDate(2026, 9, 15)->setTime(10, 0));
});

it('requeues pending expenses whose quote already exists', function () {
    $pastPending = Expense::factory()->pendingUsd()->create(['date' => '2026-09-14']);
    Expense::factory()->pendingUsd()->create(['date' => '2026-09-15']);
    Expense::factory()->create(['date' => '2026-09-10']);

    $this->artisan('expenses:convert-pending')
        ->expectsOutput('1 despesa(s) enfileirada(s) para conversão.')
        ->assertSuccessful();

    Queue::assertPushed(ConvertExpenseCurrencyJob::class, 1);
    Queue::assertPushed(ConvertExpenseCurrencyJob::class, fn (ConvertExpenseCurrencyJob $job): bool => $job->expense->is($pastPending));
});

it('leaves failed expenses alone unless asked', function () {
    Expense::factory()->pendingUsd()->create(['date' => '2026-09-01', 'conversion_status' => ConversionStatus::Failed]);

    $this->artisan('expenses:convert-pending')->assertSuccessful();

    Queue::assertNothingPushed();
});

it('retries failed expenses with --failed', function () {
    $failed = Expense::factory()->pendingUsd()->create(['date' => '2026-09-01', 'conversion_status' => ConversionStatus::Failed]);

    $this->artisan('expenses:convert-pending --failed')->assertSuccessful();

    expect($failed->refresh()->conversion_status)->toBe(ConversionStatus::Pending);
    Queue::assertPushed(ConvertExpenseCurrencyJob::class, 1);
});
