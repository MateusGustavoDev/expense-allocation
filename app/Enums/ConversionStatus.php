<?php

declare(strict_types=1);

namespace App\Enums;

// Situação da conversão do valor da despesa para BRL
enum ConversionStatus: string
{
    // Aguardando a cotação (despesa em moeda estrangeira recém-criada ou em nova tentativa)
    case Pending = 'pending';

    // Valor em BRL calculado; a despesa entra no relatório
    case Converted = 'converted';

    // Tentativas esgotadas; precisa de reprocessamento manual
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendente',
            self::Converted => 'Convertida',
            self::Failed => 'Falhou',
        };
    }
}
