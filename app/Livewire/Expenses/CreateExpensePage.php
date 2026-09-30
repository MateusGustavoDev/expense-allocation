<?php

declare(strict_types=1);

namespace App\Livewire\Expenses;

use App\Actions\Expenses\CreateExpense;
use App\Livewire\Forms\ExpenseForm;
use App\Models\Unit;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Nova despesa com rateio entre unidades. A prévia mostra a soma dos percentuais e o valor de cada unidade
 * enquanto o usuário digita, calculados pelo mesmo AllocationSplitter que grava a despesa.
 */
#[Title('Nova despesa')]
final class CreateExpensePage extends Component
{
    public ExpenseForm $form;

    public function mount(): void
    {
        $this->form->date = CarbonImmutable::now(config()->string('app.business_timezone'))->toDateString();
    }

    public function addAllocation(): void
    {
        $this->form->addAllocation();
    }

    public function removeAllocation(int $index): void
    {
        $this->form->removeAllocation($index);
    }

    public function splitEvenly(): void
    {
        $this->form->splitEvenly();
    }

    /**
     * Unidades para o select do rateio: "Unidade A · Acme Holding".
     *
     * @return array<int, string>
     */
    #[Computed]
    public function unitOptions(): array
    {
        return Unit::query()
            ->with('company')
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (Unit $unit): array => [$unit->id => "{$unit->name} · {$unit->company->name}"])
            ->all();
    }

    public function save(CreateExpense $createExpense): void
    {
        $expense = $this->form->store($createExpense);

        session()->flash('toast', ['type' => 'success', 'message' => 'Despesa cadastrada.']);
        $this->redirectRoute('expenses.show', $expense);
    }

    public function render(): View
    {
        return view('livewire.expenses.create-expense-page', ['preview' => $this->form->preview()]);
    }
}
