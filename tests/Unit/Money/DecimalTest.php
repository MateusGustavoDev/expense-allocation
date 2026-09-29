<?php

declare(strict_types=1);

use App\Services\Money\Decimal;

it('converts a decimal string to hundredths without float precision loss', function (string $value, int $expected) {
    expect(Decimal::toHundredths($value))->toBe($expected);
})->with([
    'integer' => ['1500', 150000],
    'one decimal place' => ['1500.5', 150050],
    'two decimal places' => ['1500.50', 150050],
    'float trap' => ['19.99', 1999],
    'one cent' => ['0.01', 1],
    'zero' => ['0', 0],
    'percentage' => ['33.33', 3333],
    'largest accepted value' => ['999999999999.99', 99999999999999],
]);

it('rejects malformed decimal strings', function (string $value) {
    Decimal::toHundredths($value);
})->throws(InvalidArgumentException::class)->with([
    'empty' => [''],
    'negative' => ['-10.00'],
    'comma separator' => ['10,50'],
    'three decimal places' => ['10.555'],
    'thousands separator' => ['1.500.00'],
    'trailing dot' => ['10.'],
    'text' => ['abc'],
    'more than 12 integer digits' => ['1000000000000.00'],
]);

it('formats hundredths as a decimal string with two places', function (int $value, string $expected) {
    expect(Decimal::fromHundredths($value))->toBe($expected);
})->with([
    [150050, '1500.50'],
    [1, '0.01'],
    [0, '0.00'],
    [10000, '100.00'],
]);

it('round-trips between string and hundredths', function () {
    expect(Decimal::fromHundredths(Decimal::toHundredths('8118.45')))->toBe('8118.45');
});
