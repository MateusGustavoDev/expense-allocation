<?php

declare(strict_types=1);

namespace App\Validation;

use App\Enums\Currency;
use App\Services\Money\AllocationSplitter;
use App\Services\Money\Decimal;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Regras de validação de uma despesa, compartilhadas pela API (StoreExpenseRequest) e pela importação por CSV:
 * as duas entradas aceitam e recusam exatamente os mesmos dados, com as mesmas mensagens.
 */
final class ExpenseRules
{
    // Decimal com ponto e até 2 casas: é o formato que Decimal::toHundredths converte sem float
    private const AMOUNT_PATTERN = '/^\d{1,12}(\.\d{1,2})?$/';

    private const PERCENTAGE_PATTERN = '/^\d{1,3}(\.\d{1,2})?$/';

    /**
     * `bail` interrompe no primeiro erro de cada campo: uma mensagem clara em vez de três redundantes.
     *
     * @return array<string, array<int, ValidationRule|string|\Stringable>>
     */
    public static function rules(): array
    {
        return [
            'description' => ['bail', 'required', 'string', 'max:255'],
            'supplier' => ['bail', 'required', 'string', 'max:255'],
            // Data da despesa (AAAA-MM-DD). Define a cotação usada na conversão para BRL.
            'date' => ['bail', 'required', 'date_format:Y-m-d'],
            // Valor total como string decimal com ponto, ex.: "1500.00"
            'amount' => ['bail', 'required', 'string', 'regex:'.self::AMOUNT_PATTERN, 'numeric', 'gt:0'],
            'currency' => ['bail', 'required', Rule::enum(Currency::class)],
            // Rateio entre unidades: a soma dos percentuais precisa ser exatamente 100
            'allocations' => ['bail', 'required', 'array', 'min:1'],
            'allocations.*.unit_id' => ['bail', 'required', 'integer', 'distinct', 'exists:units,id'],
            // Percentual da unidade como string decimal, ex.: "33.34"
            'allocations.*.percentage' => ['bail', 'required', 'string', 'regex:'.self::PERCENTAGE_PATTERN, 'numeric', 'gt:0', 'lte:100'],
        ];
    }

    /**
     * Validação que depende de vários campos: a soma dos percentuais precisa ser exatamente 100%.
     *
     * @return Closure(Validator): void
     */
    public static function allocationsSumToOneHundred(): Closure
    {
        return function (Validator $validator): void {
            // Só soma quando cada percentual individual já é válido
            if ($validator->errors()->hasAny(['allocations', 'allocations.*'])) {
                return;
            }

            /** @var list<array{percentage: string}> $allocations */
            $allocations = $validator->getData()['allocations'] ?? [];

            $total = array_sum(array_map(
                fn (array $allocation): int => Decimal::toHundredths($allocation['percentage']),
                $allocations,
            ));

            if ($total !== AllocationSplitter::TOTAL_BASIS_POINTS) {
                $validator->errors()->add('allocations', 'A soma dos percentuais do rateio precisa ser exatamente 100%.');
            }
        };
    }
}
