<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ExpenseAllocationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Fatia de uma despesa atribuída a uma unidade
#[Fillable(['expense_id', 'unit_id', 'basis_points', 'amount_cents', 'amount_brl_cents'])]
final class ExpenseAllocation extends Model
{
    /** @use HasFactory<ExpenseAllocationFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Expense, $this>
     */
    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class);
    }

    /**
     * @return BelongsTo<Unit, $this>
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    protected function casts(): array
    {
        return [
            'basis_points' => 'integer',
            'amount_cents' => 'integer',
            'amount_brl_cents' => 'integer',
        ];
    }
}
