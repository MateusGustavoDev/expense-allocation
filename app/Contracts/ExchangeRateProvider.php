<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Data\ExchangeQuote;
use App\Enums\Currency;
use App\Exceptions\ExchangeRateUnavailableException;
use Carbon\CarbonImmutable;

interface ExchangeRateProvider
{
    /**
     * Cotação da moeda em BRL válida para a data: a do próprio dia ou, se não houver, a do último dia útil anterior.
     *
     * Falhas de rede ou do serviço externo propagam a exceção original, para que a fila tente de novo.
     *
     * @throws ExchangeRateUnavailableException quando o provedor não tem cotação para a data
     */
    public function quote(Currency $currency, CarbonImmutable $date): ExchangeQuote;
}
