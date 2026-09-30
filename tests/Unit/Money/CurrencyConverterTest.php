<?php

declare(strict_types=1);

use App\Services\Money\CurrencyConverter;

it('converts cents with the exchange rate without float precision loss', function (int $cents, string $rate, int $expected) {
    expect((new CurrencyConverter)->toBrlCents($cents, $rate))->toBe($expected);
})->with([
    'licença CRM: US$ 1.500,00 a 5,4123' => [150000, '5.412300', 811845],
    'rounds half a cent up' => [101, '0.500000', 51],
    'rounds below half down' => [1, '5.437800', 5],
    'one cent' => [1, '5.500000', 6],
    // 99.999.999.999.999 x 5,4378 = 543.779.999.999.994,5622: estouraria int64 se calculado em micro-unidades
    'largest accepted amount' => [99999999999999, '5.437800', 543779999999995],
]);

it('rejects invalid exchange rates', function (string $rate) {
    (new CurrencyConverter)->toBrlCents(100, $rate);
})->throws(InvalidArgumentException::class)->with([
    'zero' => ['0'],
    'negative' => ['-5.40'],
    'comma separator' => ['5,40'],
    'text' => ['abc'],
    'scientific notation' => ['5e2'],
]);
