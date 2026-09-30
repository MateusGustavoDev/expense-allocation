<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\Expenses\ConvertExpenseCurrency;
use App\Enums\ConversionStatus;
use App\Exceptions\ExchangeRateUnavailableException;
use App\Models\Expense;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\Backoff;
use Illuminate\Queue\Attributes\DeleteWhenMissingModels;
use Illuminate\Queue\Attributes\Tries;
use Illuminate\Queue\Attributes\UniqueFor;
use Throwable;

/**
 * Converte a despesa em segundo plano, fora da requisição que a criou.
 *
 * Falha de rede ou da API de câmbio relança a exceção e a fila tenta de novo, com espera crescente
 * (1 min, 5 min, 15 min, 1 h). Esgotadas as tentativas, a despesa fica como "falhou" e pode ser reprocessada.
 * Uma única instância por despesa na fila, para o reprocessamento não duplicar um job ainda em andamento.
 */
#[Tries(5)]
#[Backoff(60, 300, 900, 3600)]
#[UniqueFor(7200)]
#[DeleteWhenMissingModels]
final class ConvertExpenseCurrencyJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Expense $expense) {}

    public function uniqueId(): string
    {
        return (string) $this->expense->getKey();
    }

    public function handle(ConvertExpenseCurrency $convert): void
    {
        try {
            $convert->execute($this->expense);
        } catch (ExchangeRateUnavailableException $exception) {
            // O provedor respondeu sem cotação: novas tentativas dariam o mesmo resultado
            $this->fail($exception);
        }
    }

    public function failed(?Throwable $exception): void
    {
        Expense::query()
            ->whereKey($this->expense->getKey())
            ->where('conversion_status', ConversionStatus::Pending)
            ->update(['conversion_status' => ConversionStatus::Failed]);
    }
}
