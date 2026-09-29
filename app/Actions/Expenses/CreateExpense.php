<?php

declare(strict_types=1);

namespace App\Actions\Expenses;

use App\Data\AllocationData;
use App\Data\ExpenseData;
use App\Enums\ConversionStatus;
use App\Enums\Currency;
use App\Exceptions\InvalidAllocationException;
use App\Models\Expense;
use App\Services\Money\AllocationSplitter;
use Illuminate\Support\Facades\DB;

/**
 * Cria a despesa e o seu rateio entre unidades.
 *
 * Ponto único de criação usado pela API, pela interface e pela importação por CSV:
 * as regras do rateio são validadas aqui mesmo que a entrada já tenha passado por um Form Request.
 */
final class CreateExpense
{
    public function __construct(private readonly AllocationSplitter $splitter) {}

    public function execute(ExpenseData $data): Expense
    {
        $basisPoints = $this->basisPointsByUnit($data->allocations);

        // Despesa em BRL já nasce convertida; em moeda estrangeira aguarda a cotação
        $isBrl = $data->currency === Currency::BRL;
        $shares = $this->splitter->split($data->amountCents, $basisPoints);

        return DB::transaction(function () use ($data, $isBrl, $basisPoints, $shares): Expense {
            $expense = Expense::create([
                'description' => $data->description,
                'supplier' => $data->supplier,
                'date' => $data->date,
                'amount_cents' => $data->amountCents,
                'currency' => $data->currency,
                'exchange_rate' => $isBrl ? '1.000000' : null,
                'amount_brl_cents' => $isBrl ? $data->amountCents : null,
                'conversion_status' => $isBrl ? ConversionStatus::Converted : ConversionStatus::Pending,
                'converted_at' => $isBrl ? now() : null,
            ]);

            $expense->allocations()->createMany(
                array_map(fn (int $unitId): array => [
                    'unit_id' => $unitId,
                    'basis_points' => $basisPoints[$unitId],
                    'amount_cents' => $shares[$unitId],
                    'amount_brl_cents' => $isBrl ? $shares[$unitId] : null,
                ], array_keys($basisPoints)),
            );

            return $expense->load('allocations.unit');
        });
    }

    /**
     * @param  list<AllocationData>  $allocations
     * @return array<int, int> pontos-base indexados pelo id da unidade, na ordem de entrada
     */
    private function basisPointsByUnit(array $allocations): array
    {
        $basisPoints = [];

        foreach ($allocations as $allocation) {
            if (isset($basisPoints[$allocation->unitId])) {
                throw new InvalidAllocationException('Uma unidade não pode aparecer duas vezes no mesmo rateio.');
            }

            $basisPoints[$allocation->unitId] = $allocation->basisPoints;
        }

        if (array_sum($basisPoints) !== AllocationSplitter::TOTAL_BASIS_POINTS) {
            throw new InvalidAllocationException('A soma dos percentuais do rateio precisa ser exatamente 100%.');
        }

        return $basisPoints;
    }
}
