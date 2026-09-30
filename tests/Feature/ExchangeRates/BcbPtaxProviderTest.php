<?php

declare(strict_types=1);

use App\Contracts\ExchangeRateProvider;
use App\Enums\Currency;
use App\Exceptions\ExchangeRateUnavailableException;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

function ptax(): ExchangeRateProvider
{
    return app(ExchangeRateProvider::class);
}

it('returns the selling rate and the quote date', function () {
    fakePtax(5.4123, '2026-09-01');

    $quote = ptax()->quote(Currency::USD, CarbonImmutable::parse('2026-09-01'));

    expect($quote->rate)->toBe('5.412300')
        ->and($quote->quotedOn->toDateString())->toBe('2026-09-01');
});

it('asks for a window ending on the expense date so weekends resolve to the last business day', function () {
    fakePtax(5.3968, '2026-09-11');

    $quote = ptax()->quote(Currency::USD, CarbonImmutable::parse('2026-09-12'));

    expect($quote->quotedOn->toDateString())->toBe('2026-09-11');
    Http::assertSent(fn (Request $request): bool => str_contains(urldecode($request->url()), "@dataInicial='09-05-2026'")
        && str_contains(urldecode($request->url()), "@dataFinalCotacao='09-12-2026'")
        && str_contains(urldecode($request->url()), '$top=1'));
});

it('reports when there is no quote for the date', function () {
    Http::fake(['olinda.bcb.gov.br/*' => Http::response(['value' => []])]);

    ptax()->quote(Currency::USD, CarbonImmutable::parse('2026-09-01'));
})->throws(ExchangeRateUnavailableException::class);

it('propagates failures of the exchange API so the queue can retry', function () {
    fakePtaxDown();

    ptax()->quote(Currency::USD, CarbonImmutable::parse('2026-09-01'));
})->throws(RequestException::class);

it('does not quote BRL', function () {
    ptax()->quote(Currency::BRL, CarbonImmutable::parse('2026-09-01'));
})->throws(InvalidArgumentException::class);
