<?php

declare(strict_types=1);

namespace App\Services\Money;

use InvalidArgumentException;

/**
 * Converte valores decimais com até 2 casas em inteiros escalados por 100, e vice-versa.
 *
 * Usado para dinheiro ("1500.50" <-> 150050 centavos) e percentuais ("33.33" <-> 3333 pontos-base).
 * A conversão manipula a string diretamente: passar por float perderia precisão
 * ((int) (19.99 * 100) resulta em 1998).
 */
final class Decimal
{
    // Até 12 dígitos inteiros (999.999.999.999,99): garante que centavos x 10.000 pontos-base
    // no AllocationSplitter caibam num inteiro de 64 bits
    private const PATTERN = '/^(\d{1,12})(?:\.(\d{1,2}))?$/';

    public static function toHundredths(string $value): int
    {
        if (preg_match(self::PATTERN, $value, $matches) !== 1) {
            throw new InvalidArgumentException("Valor decimal inválido: \"{$value}\".");
        }

        $fraction = str_pad($matches[2] ?? '', 2, '0');

        return (int) $matches[1] * 100 + (int) $fraction;
    }

    public static function fromHundredths(int $value): string
    {
        if ($value < 0) {
            throw new InvalidArgumentException("Valor negativo não suportado: {$value}.");
        }

        return sprintf('%d.%02d', intdiv($value, 100), $value % 100);
    }
}
