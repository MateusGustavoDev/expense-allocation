<?php

declare(strict_types=1);

namespace App\Services\ExchangeRates;

use App\Contracts\ExchangeRateProvider;
use App\Data\ExchangeQuote;
use App\Enums\Currency;
use App\Exceptions\ExchangeRateUnavailableException;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

/**
 * Cotação PTAX do Banco Central (API Olinda, pública e sem autenticação).
 *
 * Consulta uma janela de dias terminando na data pedida e usa a cotação mais recente:
 * assim fins de semana e feriados resolvem para o último dia útil numa única chamada.
 * Usa a cotação de venda, referência para liquidar pagamentos em moeda estrangeira.
 */
final class BcbPtaxProvider implements ExchangeRateProvider
{
    // Maior sequência de dias sem cotação (Carnaval emendado com fim de semana) cabe com folga
    private const LOOKBACK_DAYS = 7;

    public function __construct(
        private readonly string $baseUrl,
        private readonly int $timeoutSeconds,
    ) {}

    public function quote(Currency $currency, CarbonImmutable $date): ExchangeQuote
    {
        if ($currency !== Currency::USD) {
            throw new InvalidArgumentException("Moeda sem cotação PTAX configurada: {$currency->value}.");
        }

        $response = Http::baseUrl($this->baseUrl)
            ->timeout($this->timeoutSeconds)
            ->acceptJson()
            ->get('CotacaoDolarPeriodo(dataInicial=@dataInicial,dataFinalCotacao=@dataFinalCotacao)', [
                '@dataInicial' => $this->formatDate($date->subDays(self::LOOKBACK_DAYS)),
                '@dataFinalCotacao' => $this->formatDate($date),
                '$orderby' => 'dataHoraCotacao desc',
                '$top' => 1,
                '$format' => 'json',
            ])
            ->throw();

        /** @var array{cotacaoVenda: float|int, dataHoraCotacao: string}|null $latest */
        $latest = $response->json('value.0');

        if ($latest === null) {
            throw new ExchangeRateUnavailableException("Sem cotação PTAX até {$date->format('d/m/Y')}.");
        }

        return new ExchangeQuote(
            // A PTAX tem até 5 casas: formatar o float com 6 casas reproduz o valor exato
            rate: sprintf('%.6F', $latest['cotacaoVenda']),
            quotedOn: CarbonImmutable::parse($latest['dataHoraCotacao'])->startOfDay(),
        );
    }

    // A API Olinda espera datas no formato 'MM-DD-AAAA', entre aspas simples
    private function formatDate(CarbonImmutable $date): string
    {
        return "'{$date->format('m-d-Y')}'";
    }
}
