<?php

declare(strict_types=1);

namespace App\Services\ExchangeRates;

use App\Contracts\ExchangeRateProvider;
use App\Enums\Currency;
use App\Models\ExchangeRate;
use Carbon\CarbonImmutable;

/**
 * Cotações com cache no banco: o provedor externo é consultado no máximo uma vez por moeda e data.
 */
final class ExchangeRates
{
    public function __construct(private readonly ExchangeRateProvider $provider) {}

    public function for(Currency $currency, CarbonImmutable $date): ExchangeRate
    {
        $cached = ExchangeRate::query()
            ->where('currency', $currency)
            ->whereDate('date', $date)
            ->first();

        if ($cached !== null) {
            return $cached;
        }

        $quote = $this->provider->quote($currency, $date);

        // createOrFirst: se outro worker gravou a mesma data ao mesmo tempo, reaproveita o registro
        return ExchangeRate::createOrFirst(
            ['currency' => $currency, 'date' => $date->toDateString()],
            ['rate' => $quote->rate, 'quoted_on' => $quote->quotedOn->toDateString()],
        );
    }
}
