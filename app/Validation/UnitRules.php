<?php

declare(strict_types=1);

namespace App\Validation;

use App\Models\Unit;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Regras e normalização de unidade, compartilhadas pela API (UnitRequest) e pela tela de unidades.
 */
final class UnitRules
{
    /**
     * @return array<string, array<int, ValidationRule|string|\Stringable>>
     */
    public static function rules(int|string|null $companyId, ?Unit $ignore = null): array
    {
        return [
            'company_id' => ['bail', 'required', 'integer', 'exists:companies,id'],
            'name' => [
                'bail', 'required', 'string', 'max:120',
                Rule::unique('units')->where('company_id', (int) $companyId)->ignore($ignore),
            ],
            // Opcional: quando ausente, é gerado a partir do nome. Referencia a unidade na importação por CSV.
            'slug' => ['bail', 'sometimes', 'required', 'string', 'max:120', 'alpha_dash', Rule::unique('units')->ignore($ignore)],
        ];
    }

    /**
     * Colapsa espaços do nome e deriva o slug do nome quando ausente; em ambos os casos o slug é normalizado.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public static function normalize(array $input): array
    {
        if (is_string($input['name'] ?? null)) {
            $input['name'] = Str::squish($input['name']);
        }

        $slug = is_string($input['slug'] ?? null) ? trim($input['slug']) : '';
        $source = $slug !== '' ? $slug : (is_string($input['name'] ?? null) ? $input['name'] : '');

        if ($source !== '') {
            $input['slug'] = Str::slug($source);
        }

        return $input;
    }
}
