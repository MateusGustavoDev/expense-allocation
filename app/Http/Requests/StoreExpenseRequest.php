<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Data\AllocationData;
use App\Data\ExpenseData;
use App\Enums\Currency;
use App\Http\Requests\Concerns\SquishesInput;
use App\Services\Money\AllocationSplitter;
use App\Services\Money\Decimal;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class StoreExpenseRequest extends FormRequest
{
    use SquishesInput;

    // Decimal com ponto e até 2 casas: é o formato que Decimal::toHundredths converte sem float
    private const AMOUNT_PATTERN = '/^\d{1,12}(\.\d{1,2})?$/';

    private const PERCENTAGE_PATTERN = '/^\d{1,3}(\.\d{1,2})?$/';

    /**
     * @return array<string, array<int, ValidationRule|string|\Stringable>>
     */
    public function rules(): array
    {
        return [
            'description' => ['required', 'string', 'max:255'],
            'supplier' => ['required', 'string', 'max:255'],
            // Data da despesa (AAAA-MM-DD). Define a cotação usada na conversão para BRL.
            'date' => ['required', 'date_format:Y-m-d'],
            // Valor total como string decimal com ponto, ex.: "1500.00"
            'amount' => ['required', 'string', 'numeric', 'regex:'.self::AMOUNT_PATTERN, 'gt:0'],
            'currency' => ['required', Rule::enum(Currency::class)],
            // Rateio entre unidades: a soma dos percentuais precisa ser exatamente 100
            'allocations' => ['required', 'array', 'min:1'],
            'allocations.*.unit_id' => ['required', 'integer', 'distinct', 'exists:units,id'],
            // Percentual da unidade como string decimal, ex.: "33.34"
            'allocations.*.percentage' => ['required', 'string', 'numeric', 'regex:'.self::PERCENTAGE_PATTERN, 'gt:0', 'lte:100'],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                // Só soma quando cada percentual individual já é válido
                if ($validator->errors()->hasAny(['allocations', 'allocations.*'])) {
                    return;
                }

                if ($this->totalBasisPoints() !== AllocationSplitter::TOTAL_BASIS_POINTS) {
                    $validator->errors()->add('allocations', 'A soma dos percentuais do rateio precisa ser exatamente 100%.');
                }
            },
        ];
    }

    public function toData(): ExpenseData
    {
        /** @var array{description: string, supplier: string, date: string, amount: string, currency: string, allocations: list<array{unit_id: int|string, percentage: string}>} $validated */
        $validated = $this->validated();

        return new ExpenseData(
            description: $validated['description'],
            supplier: $validated['supplier'],
            date: CarbonImmutable::createFromFormat('!Y-m-d', $validated['date']) ?: throw new \UnexpectedValueException('Data inválida.'),
            amountCents: Decimal::toHundredths($validated['amount']),
            currency: Currency::from($validated['currency']),
            allocations: array_map(
                fn (array $allocation): AllocationData => new AllocationData(
                    unitId: (int) $allocation['unit_id'],
                    basisPoints: Decimal::toHundredths($allocation['percentage']),
                ),
                $validated['allocations'],
            ),
        );
    }

    protected function prepareForValidation(): void
    {
        $this->squish('description', 'supplier');
    }

    private function totalBasisPoints(): int
    {
        /** @var list<array{percentage: string}> $allocations */
        $allocations = $this->input('allocations', []);

        return array_sum(array_map(
            fn (array $allocation): int => Decimal::toHundredths($allocation['percentage']),
            $allocations,
        ));
    }
}
