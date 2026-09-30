<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\Currency;
use Carbon\CarbonInterface;

/**
 * Formatação para exibição no padrão brasileiro (R$ 1.500,00; 31,9%; 01/09/2026).
 *
 * Parte de inteiros (centavos, pontos-base) e nunca passa por float. Não depende da extensão intl.
 */
final class Format
{
    public static function money(int $cents, Currency $currency = Currency::BRL): string
    {
        return $currency->symbol().' '.self::decimal($cents);
    }

    // Número com 2 casas: 4839217 -> "48.392,17"
    public static function decimal(int $hundredths): string
    {
        $sign = $hundredths < 0 ? '-' : '';
        $absolute = abs($hundredths);

        return $sign.number_format(intdiv($absolute, 100), 0, ',', '.').','.str_pad((string) ($absolute % 100), 2, '0', STR_PAD_LEFT);
    }

    // Percentual a partir de pontos-base (10000 = 100%), com 1 casa: 3187 -> "31,9%"
    public static function percent(int $basisPoints): string
    {
        $tenths = intdiv($basisPoints + 5, 10);

        return intdiv($tenths, 10).','.($tenths % 10).'%';
    }

    public static function date(CarbonInterface $date): string
    {
        return $date->format('d/m/Y');
    }
}
