<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class ImportExpensesRequest extends FormRequest
{
    /**
     * @return array<string, array<int, ValidationRule|string|\Stringable>>
     */
    public function rules(): array
    {
        return [
            // Arquivo CSV separado por ponto e vírgula, com cabeçalho data;descricao;fornecedor;valor;moeda;rateio (até 5 MB)
            'file' => ['bail', 'required', 'file', 'extensions:csv,txt', 'max:5120'],
        ];
    }
}
