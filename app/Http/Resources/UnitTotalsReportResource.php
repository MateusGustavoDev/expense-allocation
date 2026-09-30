<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Data\UnconvertedAmount;
use App\Data\UnitTotal;
use App\Data\UnitTotalsReport;
use App\Services\Money\Decimal;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property UnitTotalsReport $resource
 */
final class UnitTotalsReportResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $report = $this->resource;

        return [
            'period' => [
                'from' => $report->from->format('Y-m-d'),
                'to' => $report->to->format('Y-m-d'),
            ],
            // Soma em BRL das despesas convertidas do período
            'total_brl' => Decimal::fromHundredths($report->totalCents),
            'converted_count' => $report->convertedCount,
            // false quando há despesas do período ainda sem valor em BRL: o total pode aumentar
            'is_complete' => $report->isComplete(),
            'units' => array_map(fn (UnitTotal $unit): array => [
                'unit' => ['id' => $unit->unitId, 'name' => $unit->unitName, 'slug' => $unit->unitSlug],
                'company' => ['id' => $unit->companyId, 'name' => $unit->companyName],
                'expenses_count' => $unit->expensesCount,
                'total_brl' => Decimal::fromHundredths($unit->totalCents),
                // Participação no total do período, em %
                'share' => Decimal::fromHundredths($unit->shareBasisPoints),
            ], $report->units),
            // Despesas do período fora do total, com os valores na moeda original
            'unconverted' => [
                'pending_count' => $report->pendingCount,
                'failed_count' => $report->failedCount,
                'amounts' => array_map(fn (UnconvertedAmount $amount): array => [
                    'currency' => $amount->currency,
                    'amount' => Decimal::fromHundredths($amount->amountCents),
                ], $report->unconvertedAmounts),
            ],
        ];
    }
}
