<?php

declare(strict_types=1);

use App\Enums\Currency;
use App\Support\Format;
use Carbon\CarbonImmutable;

it('formats cents as Brazilian money', function (int $cents, Currency $currency, string $expected) {
    expect(Format::money($cents, $currency))->toBe($expected);
})->with([
    'thousands separator' => [4839217, Currency::BRL, 'R$ 48.392,17'],
    'one cent' => [1, Currency::BRL, 'R$ 0,01'],
    'zero' => [0, Currency::BRL, 'R$ 0,00'],
    'millions' => [123456789, Currency::BRL, 'R$ 1.234.567,89'],
    'dollars' => [150000, Currency::USD, 'US$ 1.500,00'],
]);

it('formats negative amounts', function () {
    expect(Format::decimal(-150050))->toBe('-1.500,50');
});

it('formats basis points as a percentage with one decimal place', function (int $basisPoints, string $expected) {
    expect(Format::percent($basisPoints))->toBe($expected);
})->with([
    [3187, '31,9%'],
    [3184, '31,8%'],
    [10000, '100,0%'],
    [0, '0,0%'],
    [5, '0,1%'],
]);

it('formats dates as dd/mm/yyyy', function () {
    expect(Format::date(CarbonImmutable::parse('2026-09-01')))->toBe('01/09/2026');
});
