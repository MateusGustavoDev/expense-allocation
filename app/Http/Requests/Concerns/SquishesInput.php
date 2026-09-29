<?php

declare(strict_types=1);

namespace App\Http\Requests\Concerns;

use Illuminate\Support\Str;

/**
 * Normaliza campos de texto livre antes da validação.
 *
 * O middleware TrimStrings já remove espaços nas pontas; aqui também colapsamos
 * espaços repetidos no meio, para que "Grupo    Teste" e "Grupo Teste" sejam o mesmo valor.
 */
trait SquishesInput
{
    protected function squish(string ...$fields): void
    {
        foreach ($fields as $field) {
            $value = $this->input($field);

            if (is_string($value)) {
                $this->merge([$field => Str::squish($value)]);
            }
        }
    }
}
