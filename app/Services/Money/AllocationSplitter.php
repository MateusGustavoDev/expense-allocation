<?php

declare(strict_types=1);

namespace App\Services\Money;

use InvalidArgumentException;

/**
 * Divide um valor em centavos entre participantes, proporcionalmente a percentuais em pontos-base.
 *
 * Usa o método do maior resto: cada participante recebe a parte inteira da sua fração e os
 * centavos que sobram são distribuídos, um a um, para as maiores frações descartadas.
 * A soma das partes é sempre igual ao total.
 *
 * Ex.: 10001 centavos em 33,34% / 33,33% / 33,33% -> 3335 + 3333 + 3333.
 */
final class AllocationSplitter
{
    public const TOTAL_BASIS_POINTS = 10_000;

    /**
     * @param  array<int, int>  $basisPoints  percentual de cada participante, indexado pelo seu id
     * @return array<int, int> centavos de cada participante, com as mesmas chaves e ordem da entrada
     */
    public function split(int $totalCents, array $basisPoints): array
    {
        $this->guard($totalCents, $basisPoints);

        $shares = [];
        $remainders = [];

        foreach ($basisPoints as $id => $points) {
            $exact = $totalCents * $points;
            $shares[$id] = intdiv($exact, self::TOTAL_BASIS_POINTS);
            $remainders[$id] = $exact % self::TOTAL_BASIS_POINTS;
        }

        $leftover = $totalCents - array_sum($shares);

        // Ordenação estável: em caso de empate no resto, vence quem veio primeiro na entrada
        arsort($remainders);

        foreach (array_slice(array_keys($remainders), 0, $leftover) as $id) {
            $shares[$id]++;
        }

        return $shares;
    }

    /**
     * @param  array<int, int>  $basisPoints
     */
    private function guard(int $totalCents, array $basisPoints): void
    {
        if ($totalCents < 0) {
            throw new InvalidArgumentException('O valor a ratear não pode ser negativo.');
        }

        if ($basisPoints === []) {
            throw new InvalidArgumentException('O rateio precisa de ao menos um participante.');
        }

        foreach ($basisPoints as $points) {
            if ($points <= 0) {
                throw new InvalidArgumentException('Todo percentual do rateio precisa ser maior que zero.');
            }
        }

        if (array_sum($basisPoints) !== self::TOTAL_BASIS_POINTS) {
            throw new InvalidArgumentException('A soma dos percentuais do rateio precisa ser exatamente 100%.');
        }
    }
}
