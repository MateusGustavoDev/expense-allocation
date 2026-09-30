<?php

declare(strict_types=1);

namespace App\Actions\Expenses;

use App\Enums\ConversionStatus;
use App\Jobs\ConvertExpenseCurrencyJob;
use App\Models\Expense;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * Coloca de novo na fila a conversão de uma despesa pendente ou que falhou.
 *
 * Usada pelo reprocessamento manual (API e interface) e pela varredura agendada de pendentes.
 */
final class RequeueExpenseConversion
{
    public function execute(Expense $expense): Expense
    {
        if ($expense->conversion_status === ConversionStatus::Converted) {
            throw new ConflictHttpException('A despesa já foi convertida.');
        }

        $expense->update(['conversion_status' => ConversionStatus::Pending]);

        ConvertExpenseCurrencyJob::dispatch($expense);

        return $expense;
    }
}
