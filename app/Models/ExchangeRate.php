<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Currency;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property Currency $currency
 * @property CarbonImmutable $date
 * @property string $rate
 * @property CarbonImmutable $quoted_on
 */
#[Fillable(['currency', 'date', 'rate', 'quoted_on'])]
final class ExchangeRate extends Model
{
    protected function casts(): array
    {
        return [
            'currency' => Currency::class,
            'date' => 'immutable_date',
            'rate' => 'decimal:6',
            'quoted_on' => 'immutable_date',
        ];
    }
}
