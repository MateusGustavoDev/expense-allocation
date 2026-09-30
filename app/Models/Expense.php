<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ConversionStatus;
use App\Enums\Currency;
use Carbon\CarbonImmutable;
use Database\Factories\ExpenseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
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

    /**
     * Filtros da listagem, compartilhados pela API e pela interface. Chaves ausentes ou vazias são ignoradas.
     *
     * @param  Builder<Expense>  $query
     * @param  array{search?: ?string, date_from?: ?string, date_to?: ?string, currency?: ?string, conversion_status?: string|list<string>|null, unit_id?: int|string|null}  $filters
     */
    #[Scope]
    protected function filter(Builder $query, array $filters): void
    {
        $query
            ->when($filters['search'] ?? null, function (Builder $query, string $term): void {
                // % e _ digitados pelo usuário são literais, não curingas do LIKE
                $like = '%'.addcslashes($term, '\\%_').'%';
                $query->where(fn (Builder $query) => $query->where('description', 'like', $like)->orWhere('supplier', 'like', $like));
            })
            ->when($filters['date_from'] ?? null, fn (Builder $query, string $from) => $query->whereDate('date', '>=', $from))
            ->when($filters['date_to'] ?? null, fn (Builder $query, string $to) => $query->whereDate('date', '<=', $to))
            ->when($filters['currency'] ?? null, fn (Builder $query, string $currency) => $query->where('currency', $currency))
            ->when($filters['conversion_status'] ?? null, fn (Builder $query, string|array $status) => $query->whereIn('conversion_status', (array) $status))
            ->when($filters['unit_id'] ?? null, fn (Builder $query, int|string $unitId) => $query->whereHas('allocations', fn (Builder $allocations) => $allocations->where('unit_id', (int) $unitId)));
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
