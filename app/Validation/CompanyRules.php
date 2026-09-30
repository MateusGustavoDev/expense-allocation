<?php

declare(strict_types=1);

namespace App\Validation;

use App\Models\Company;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

/**
 * Regras de empresa, compartilhadas pela API (CompanyRequest) e pela tela de empresas.
 */
final class CompanyRules
{
    /**
     * @return array<string, array<int, ValidationRule|string|\Stringable>>
     */
    public static function rules(?Company $ignore = null): array
    {
        return [
            // Na edição, ignora a própria empresa na checagem de nome único
            'name' => ['bail', 'required', 'string', 'max:120', Rule::unique('companies')->ignore($ignore)],
        ];
    }
}
