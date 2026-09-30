<?php

declare(strict_types=1);

namespace App\Services\Money;

use InvalidArgumentException;

/**
 * Converte centavos de uma moeda para centavos de BRL, dada a cotação.
 *
 * Usa bcmath (aritmética decimal em string): multiplicar centavos por uma cotação com 6 casas
 * estouraria o inteiro de 64 bits nos valores altos, e float perderia precisão.
 */
final class CurrencyConverter
{
    private const RATE_PATTERN = '/^\d{1,6}(\.\d{1,6})?$/';

    public function toBrlCents(int $amountCents, string $rate): int
    {
        if ($amountCents < 0) {
            throw new InvalidArgumentException('O valor a converter não pode ser negativo.');
        }

        // is_numeric estreita o tipo para numeric-string (exigido pelo bcmath); a regex restringe o formato
        if (! is_numeric($rate) || preg_match(self::RATE_PATTERN, $rate) !== 1 || bccomp($rate, '0', 6) <= 0) {
            throw new InvalidArgumentException("Cotação inválida: \"{$rate}\".");
        }

        // Arredondamento comercial (meio centavo para cima), como a nota fiscal faria
        return (int) bcround(bcmul((string) $amountCents, $rate, 6), 0);
    }
}
