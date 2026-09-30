<?php

declare(strict_types=1);

namespace App\Livewire\Expenses;

use App\Actions\Expenses\RequeueExpenseConversion;
use App\Models\Expense;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * Detalhe da despesa: dados, cotação usada, situação da conversão e rateio por unidade.
 */
final class ExpenseShowPage extends Component
{
    public Expense $expense;

    public function mount(Expense $expense): void
    {
        $this->expense = $expense;
    }

    public function retryConversion(RequeueExpenseConversion $requeue): void
    {
        try {
            $requeue->execute($this->expense);
            $this->dispatch('toast', type: 'success', message: 'Conversão enviada de novo para a fila.');
        } catch (ConflictHttpException $exception) {
            $this->dispatch('toast', type: 'warning', message: $exception->getMessage());
        }
    }

    public function render(): View
    {
        return view('livewire.expenses.expense-show-page', [
            'allocations' => $this->expense->allocations()->with('unit.company')->get(),
        ])->title($this->expense->description);
    }
}
