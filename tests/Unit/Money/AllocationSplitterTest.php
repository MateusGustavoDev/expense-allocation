<?php

declare(strict_types=1);

use App\Services\Money\AllocationSplitter;

beforeEach(function () {
    $this->splitter = new AllocationSplitter;
});

it('splits exactly when there is no remainder', function () {
    // US$ 1.500,00 em 50% / 30% / 20%, exemplo do enunciado
    expect($this->splitter->split(150000, [1 => 5000, 2 => 3000, 3 => 2000]))
        ->toBe([1 => 75000, 2 => 45000, 3 => 30000]);
});

it('closes R$ 100,00 split in three parts with the total', function () {
    expect($this->splitter->split(10000, [1 => 3334, 2 => 3333, 3 => 3333]))
        ->toBe([1 => 3334, 2 => 3333, 3 => 3333]);
});

it('gives the leftover cent to the largest remainder', function () {
    // 10001 * 33,34% = 3334,3334 | 10001 * 33,33% = 3333,3333 -> sobra 1 centavo para a primeira
    expect($this->splitter->split(10001, [1 => 3334, 2 => 3333, 3 => 3333]))
        ->toBe([1 => 3335, 2 => 3333, 3 => 3333]);
});

it('breaks remainder ties by input order', function () {
    expect($this->splitter->split(1, [7 => 5000, 3 => 5000]))->toBe([7 => 1, 3 => 0]);
    expect($this->splitter->split(1, [3 => 5000, 7 => 5000]))->toBe([3 => 1, 7 => 0]);
});

it('distributes several leftover cents', function () {
    // 5 centavos em 7 partes quase iguais: as 5 maiores frações recebem 1 centavo cada
    $shares = $this->splitter->split(5, [1 => 1429, 2 => 1429, 3 => 1429, 4 => 1429, 5 => 1428, 6 => 1428, 7 => 1428]);

    expect($shares)->toBe([1 => 1, 2 => 1, 3 => 1, 4 => 1, 5 => 1, 6 => 0, 7 => 0]);
});

it('assigns the whole amount to a single participant', function () {
    expect($this->splitter->split(12345, [9 => 10000]))->toBe([9 => 12345]);
});

it('always sums to the total', function (int $total, array $basisPoints) {
    expect(array_sum($this->splitter->split($total, $basisPoints)))->toBe($total);
})->with([
    [1, [1 => 3334, 2 => 3333, 3 => 3333]],
    [99999, [1 => 1, 2 => 9999]],
    [123456789, [1 => 1234, 2 => 5678, 3 => 3088]],
    [100, [1 => 1111, 2 => 2222, 3 => 3333, 4 => 3334]],
    'largest accepted amount' => [99999999999999, [1 => 3334, 2 => 3333, 3 => 3333]],
]);

it('rejects percentages that do not sum to 100%', function (array $basisPoints) {
    $this->splitter->split(10000, $basisPoints);
})->throws(InvalidArgumentException::class)->with([
    'below 100%' => [[1 => 3333, 2 => 3333, 3 => 3333]],
    'above 100%' => [[1 => 5000, 2 => 5001]],
]);

it('rejects invalid inputs', function (int $total, array $basisPoints) {
    $this->splitter->split($total, $basisPoints);
})->throws(InvalidArgumentException::class)->with([
    'no participants' => [10000, []],
    'zero percentage' => [10000, [1 => 10000, 2 => 0]],
    'negative total' => [-1, [1 => 10000]],
]);
