<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ConversionStatus;
use App\Enums\Currency;
use Carbon\CarbonImmutable;
use Database\Factories\ExpenseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property CarbonImmutable $date
 * @property Currency $currency
 * @property ConversionStatus $conversion_status
 * @property string|null $exchange_rate
 * @property CarbonImmutable|null $exchange_rate_date
 * @property CarbonImmutable|null $converted_at
 */
#[Fillable([
    'description', 'supplier', 'date', 'amount_cents', 'currency', 'exchange_rate',
    'exchange_rate_date', 'amount_brl_cents', 'conversion_status', 'converted_at',
])]
final class Expense extends Model
{
    /** @use HasFactory<ExpenseFactory> */
    use HasFactory;

    /**
     * @return HasMany<ExpenseAllocation, $this>
     */
    public function allocations(): HasMany
    {
        // Mantém a ordem em que o rateio foi informado: sem ORDER BY o MySQL devolve na ordem do índice usado
        return $this->hasMany(ExpenseAllocation::class)->orderBy('id');
    }

    protected function casts(): array
    {
        return [
            'date' => 'immutable_date',
            'amount_cents' => 'integer',
            'currency' => Currency::class,
            // Cotação como string decimal: mantém as 6 casas exatas, sem passar por float
            'exchange_rate' => 'decimal:6',
            'exchange_rate_date' => 'immutable_date',
            'amount_brl_cents' => 'integer',
            'conversion_status' => ConversionStatus::class,
            'converted_at' => 'immutable_datetime',
        ];
    }
}
