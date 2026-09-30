<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Expenses\CreateExpense;
use App\Actions\Expenses\ImportExpensesFromCsv;
use App\Actions\Expenses\RequeueExpenseConversion;
use App\Http\Controllers\Controller;
use App\Http\Requests\ImportExpensesRequest;
use App\Http\Requests\ListExpensesRequest;
use App\Http\Requests\StoreExpenseRequest;
use App\Http\Resources\ExpenseResource;
use App\Http\Resources\ImportReportResource;
use App\Models\Expense;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\UploadedFile;

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
        /** @var array{search?: ?string, date_from?: ?string, date_to?: ?string, currency?: ?string, conversion_status?: ?string, unit_id?: int|string|null} $filters */
        $filters = $request->validated();

        $expenses = Expense::query()
            ->withCount('allocations')
            ->filter($filters)
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
     * Importar despesas por CSV
     *
     * Formato: `data;descricao;fornecedor;valor;moeda;rateio`, com cabeçalho. Rateio: `slug-da-unidade:percentual`
     * separados por `|`. Linhas inválidas não impedem a importação das válidas: o relatório lista os erros por linha.
     */
    public function import(ImportExpensesRequest $request, ImportExpensesFromCsv $import): ImportReportResource
    {
        /** @var UploadedFile $file */
        $file = $request->file('file');

        return ImportReportResource::make($import->execute($file->getRealPath()));
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

    /**
     * Reprocessar conversão
     *
     * Coloca de novo na fila a conversão para BRL de uma despesa pendente ou que falhou.
     */
    public function retryConversion(Expense $expense, RequeueExpenseConversion $requeue): JsonResponse
    {
        /** @status 202 */
        return ExpenseResource::make($requeue->execute($expense))->response()->setStatusCode(202);
    }
}
