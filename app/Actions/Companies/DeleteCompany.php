<?php

declare(strict_types=1);

namespace App\Actions\Companies;

use App\Exceptions\ResourceInUseException;
use App\Models\Company;

// Usada pela API e pela tela de empresas: uma empresa com unidades não pode ficar sem dona
final class DeleteCompany
{
    /**
     * @throws ResourceInUseException
     */
    public function execute(Company $company): void
    {
        if ($company->units()->exists()) {
            throw new ResourceInUseException('Não é possível excluir uma empresa com unidades cadastradas.');
        }

        $company->delete();
    }
}
