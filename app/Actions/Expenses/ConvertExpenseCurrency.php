<?php

declare(strict_types=1);

namespace App\Actions\Expenses;

use App\Enums\ConversionStatus;
use App\Exceptions\ExchangeRateUnavailableException;
use App\Models\Expense;
use App\Services\ExchangeRates\ExchangeRates;
use App\Services\Money\AllocationSplitter;
use App\Services\Money\CurrencyConverter;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Facades\DB;

/**
 * Converte para BRL uma despesa pendente, pela cotação da data da despesa, e rateia o valor convertido.
 *
 * Idempotente: executar de novo numa despesa já convertida não faz nada.
 */
final class ConvertExpenseCurrency
{
    public function __construct(
        private readonly ExchangeRates $rates,
        private readonly CurrencyConverter $converter,
        private readonly AllocationSplitter $splitter,
        private readonly Repository $config,
    ) {}

    /**
     * @return bool se a despesa foi convertida nesta execução
     *
     * @throws ExchangeRateUnavailableException
     */
    public function execute(Expense $expense): bool
    {
        if ($expense->conversion_status === ConversionStatus::Converted || ! $this->quoteIsFinal($expense->date)) {
            return false;
        }

        $rate = $this->rates->for($expense->currency, $expense->date);

        return DB::transaction(function () use ($expense, $rate): bool {
            // Trava a linha: dois workers com a mesma despesa não convertem duas vezes
            $locked = Expense::query()->whereKey($expense->getKey())->lockForUpdate()->with('allocations')->firstOrFail();

            if ($locked->conversion_status === ConversionStatus::Converted) {
                return false;
            }

            $amountBrlCents = $this->converter->toBrlCents($locked->amount_cents, $rate->rate);

            // Rateio refeito sobre o valor em BRL com os mesmos percentuais: os centavos fecham no total convertido
            $shares = $this->splitter->split(
                $amountBrlCents,
                $locked->allocations->mapWithKeys(fn ($allocation): array => [$allocation->unit_id => $allocation->basis_points])->all(),
            );

            foreach ($locked->allocations as $allocation) {
                $allocation->update(['amount_brl_cents' => $shares[$allocation->unit_id]]);
            }

            $locked->update([
                'exchange_rate' => $rate->rate,
                'exchange_rate_date' => $rate->quoted_on,
                'amount_brl_cents' => $amountBrlCents,
                'conversion_status' => ConversionStatus::Converted,
                'converted_at' => now(),
            ]);

            return true;
        });
    }

    // A PTAX de um dia só é definitiva depois que o dia termina no horário de Brasília
    private function quoteIsFinal(CarbonImmutable $date): bool
    {
        $today = CarbonImmutable::now($this->config->string('services.ptax.timezone'))->startOfDay();

        return $date->toDateString() < $today->toDateString();
    }
}
