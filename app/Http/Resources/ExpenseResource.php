<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Expense;
use App\Services\Money\Decimal;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Expense
 */
final class ExpenseResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'description' => $this->description,
            'supplier' => $this->supplier,
            'date' => $this->date->format('Y-m-d'),
            'amount' => Decimal::fromHundredths($this->amount_cents),
            'currency' => $this->currency,
            'exchange_rate' => $this->exchange_rate,
            // Dia da cotação usada: em fins de semana e feriados, o último dia útil anterior à data da despesa
            'exchange_rate_date' => $this->exchange_rate_date?->format('Y-m-d'),
            'amount_brl' => $this->amount_brl_cents === null ? null : Decimal::fromHundredths($this->amount_brl_cents),
            'conversion_status' => $this->conversion_status,
            'converted_at' => $this->converted_at,
            'allocations_count' => $this->whenCounted('allocations'),
            'allocations' => ExpenseAllocationResource::collection($this->whenLoaded('allocations')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
