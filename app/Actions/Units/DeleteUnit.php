<?php

declare(strict_types=1);

namespace App\Actions\Units;

use App\Exceptions\ResourceInUseException;
use App\Models\Unit;

// Usada pela API e pela tela de unidades: o histórico do relatório depende das unidades com despesas rateadas
final class DeleteUnit
{
    /**
     * @throws ResourceInUseException
     */
    public function execute(Unit $unit): void
    {
        if ($unit->allocations()->exists()) {
            throw new ResourceInUseException('Não é possível excluir uma unidade com despesas rateadas.');
        }

        $unit->delete();
    }
}
