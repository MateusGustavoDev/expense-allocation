<?php

declare(strict_types=1);

namespace App\Enums;

enum Currency: string
{
    case BRL = 'BRL';
    case USD = 'USD';

    public function label(): string
    {
        return match ($this) {
            self::BRL => 'Real (BRL)',
            self::USD => 'Dólar (USD)',
        };
    }

    public function symbol(): string
    {
        return match ($this) {
            self::BRL => 'R$',
            self::USD => 'US$',
        };
    }
}
