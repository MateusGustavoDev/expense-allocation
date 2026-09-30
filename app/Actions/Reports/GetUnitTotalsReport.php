<?php

declare(strict_types=1);

namespace App\Actions\Reports;

use App\Data\UnconvertedAmount;
use App\Data\UnitTotal;
use App\Data\UnitTotalsReport;
use App\Enums\ConversionStatus;
use App\Enums\Currency;
use App\Models\Expense;
use App\Models\ExpenseAllocation;
use App\Models\Unit;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

/**
 * Total em BRL por unidade num período (datas inclusivas), usado pela API e pela interface.
 *
 * As somas são feitas no banco (SUM ... GROUP BY); o PHP só junta os totais às unidades e calcula a participação.
 */
final class GetUnitTotalsReport
{
    public function execute(CarbonImmutable $from, CarbonImmutable $to): UnitTotalsReport
    {
        $period = [$from->toDateString(), $to->toDateString()];

        // Soma das fatias em BRL por unidade, só das despesas convertidas do período
        $totalsByUnit = ExpenseAllocation::query()
            ->join('expenses', 'expenses.id', '=', 'expense_allocations.expense_id')
            ->whereBetween('expenses.date', $period)
            ->where('expenses.conversion_status', ConversionStatus::Converted)
            ->groupBy('expense_allocations.unit_id')
            ->selectRaw('expense_allocations.unit_id, COUNT(*) as expenses_count, SUM(expense_allocations.amount_brl_cents) as total_cents')
            ->toBase()
            ->get()
            ->keyBy('unit_id');

        $converted = $this->expensesIn($period)
            ->where('conversion_status', ConversionStatus::Converted)
            ->selectRaw('COUNT(*) as expenses_count, COALESCE(SUM(amount_brl_cents), 0) as total_cents')
            ->toBase()
            ->first();

        // SUM e COUNT do MySQL chegam como string pelo PDO
        $totalCents = (int) ($converted->total_cents ?? 0);

        $units = array_values(Unit::query()
            ->with('company')
            ->orderBy('name')
            ->get()
            ->map(function (Unit $unit) use ($totalsByUnit, $totalCents): UnitTotal {
                $unitTotal = $totalsByUnit->get($unit->id);
                $unitCents = (int) ($unitTotal->total_cents ?? 0);

                return new UnitTotal(
                    unitId: $unit->id,
                    unitName: $unit->name,
                    unitSlug: $unit->slug,
                    companyId: $unit->company->id,
                    companyName: $unit->company->name,
                    expensesCount: (int) ($unitTotal->expenses_count ?? 0),
                    totalCents: $unitCents,
                    shareBasisPoints: $totalCents === 0 ? 0 : intdiv($unitCents * 10_000 + intdiv($totalCents, 2), $totalCents),
                );
            })
            ->sortByDesc('totalCents')
            ->all());

        $unconverted = $this->expensesIn($period)
            ->whereIn('conversion_status', [ConversionStatus::Pending, ConversionStatus::Failed])
            ->groupBy('currency', 'conversion_status')
            ->selectRaw('currency, conversion_status, COUNT(*) as expenses_count, SUM(amount_cents) as amount_cents')
            ->toBase()
            ->get();

        return new UnitTotalsReport(
            from: $from,
            to: $to,
            units: $units,
            totalCents: $totalCents,
            convertedCount: (int) ($converted->expenses_count ?? 0),
            pendingCount: (int) $unconverted->where('conversion_status', ConversionStatus::Pending->value)->sum('expenses_count'),
            failedCount: (int) $unconverted->where('conversion_status', ConversionStatus::Failed->value)->sum('expenses_count'),
            unconvertedAmounts: array_values($unconverted
                ->groupBy('currency')
                ->map(fn ($rows, string $currency): UnconvertedAmount => new UnconvertedAmount(
                    Currency::from($currency),
                    (int) $rows->sum(fn ($row): int => (int) $row->amount_cents),
                ))
                ->all()),
        );
    }

    /**
     * @param  array{string, string}  $period
     * @return Builder<Expense>
     */
    private function expensesIn(array $period): Builder
    {
        return Expense::query()->whereBetween('date', $period);
    }
}
