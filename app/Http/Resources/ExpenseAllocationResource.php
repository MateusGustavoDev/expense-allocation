<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\ExpenseAllocation;
use App\Services\Money\Decimal;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ExpenseAllocation
 */
final class ExpenseAllocationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'unit_id' => $this->unit_id,
            'unit' => UnitResource::make($this->whenLoaded('unit')),
            'percentage' => Decimal::fromHundredths($this->basis_points),
            'amount' => Decimal::fromHundredths($this->amount_cents),
            'amount_brl' => $this->amount_brl_cents === null ? null : Decimal::fromHundredths($this->amount_brl_cents),
        ];
    }
}
