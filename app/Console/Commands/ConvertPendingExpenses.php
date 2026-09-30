<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Expenses\RequeueExpenseConversion;
use App\Enums\ConversionStatus;
use App\Models\Expense;
use Carbon\CarbonImmutable;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository;

/**
 * Rede de segurança da conversão: reenfileira despesas pendentes cuja cotação já existe.
 *
 * Cobre as despesas de hoje (cotação só fecha no fim do dia) e o caso raro de o processo cair
 * entre gravar a despesa e enfileirar o job. Com --failed, reprocessa também as que falharam.
 */
#[Signature('expenses:convert-pending {--failed : Reprocessa também as despesas cuja conversão falhou}')]
#[Description('Enfileira a conversão para BRL das despesas pendentes')]
final class ConvertPendingExpenses extends Command
{
    public function handle(RequeueExpenseConversion $requeue, Repository $config): int
    {
        $statuses = $this->option('failed')
            ? [ConversionStatus::Pending, ConversionStatus::Failed]
            : [ConversionStatus::Pending];

        $today = CarbonImmutable::now($config->string('services.ptax.timezone'))->toDateString();
        $count = 0;

        Expense::query()
            ->whereIn('conversion_status', $statuses)
            ->whereDate('date', '<', $today)
            ->each(function (Expense $expense) use ($requeue, &$count): void {
                $requeue->execute($expense);
                $count++;
            });

        $this->info("{$count} despesa(s) enfileirada(s) para conversão.");

        return self::SUCCESS;
    }
}
