<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Expenses\CreateExpense;
use App\Http\Controllers\Controller;
use App\Http\Requests\ListExpensesRequest;
use App\Http\Requests\StoreExpenseRequest;
use App\Http\Resources\ExpenseResource;
use App\Models\Expense;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

#[Group('Despesas', weight: 3)]
final class ExpenseController extends Controller
{
    /**
     * Listar despesas
     *
     * Da mais recente para a mais antiga, com a quantidade de unidades do rateio.
     */
    public function index(ListExpensesRequest $request): AnonymousResourceCollection
    {
        $expenses = Expense::query()
            ->withCount('allocations')
            ->when($request->date('date_from'), fn (Builder $query, $from) => $query->whereDate('date', '>=', $from))
            ->when($request->date('date_to'), fn (Builder $query, $to) => $query->whereDate('date', '<=', $to))
            ->when($request->string('currency')->toString(), fn (Builder $query, string $currency) => $query->where('currency', $currency))
            ->when($request->string('conversion_status')->toString(), fn (Builder $query, string $status) => $query->where('conversion_status', $status))
            ->when($request->integer('unit_id'), fn (Builder $query, int $unitId) => $query->whereHas('allocations', fn (Builder $allocations) => $allocations->where('unit_id', $unitId)))
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate();

        return ExpenseResource::collection($expenses);
    }

    /**
     * Cadastrar despesa
     *
     * Despesas em BRL são gravadas já convertidas. Em USD, ficam pendentes até a conversão pela cotação da data.
     */
    public function store(StoreExpenseRequest $request, CreateExpense $createExpense): ExpenseResource
    {
        /** @status 201 */
        return ExpenseResource::make($createExpense->execute($request->toData()));
    }

    /**
     * Exibir despesa
     *
     * Inclui o rateio por unidade, com os valores na moeda original e em BRL.
     */
    public function show(Expense $expense): ExpenseResource
    {
        return ExpenseResource::make($expense->load('allocations.unit'));
    }
}
