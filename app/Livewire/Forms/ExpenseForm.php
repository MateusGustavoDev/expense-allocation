<?php

declare(strict_types=1);

namespace App\Livewire\Forms;

use App\Actions\Expenses\CreateExpense;
use App\Data\ExpenseData;
use App\Models\Expense;
use App\Services\Money\AllocationSplitter;
use App\Services\Money\Decimal;
use App\Validation\ExpenseRules;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Livewire\Form;

/**
 * Formulário de nova despesa. Valida com as mesmas ExpenseRules da API e cria pela mesma CreateExpense.
 *
 * Diferente da API, aceita valores no formato brasileiro digitado (1.500,00 e 33,34): converte para o formato
 * da API (1500.00) antes de validar.
 */
final class ExpenseForm extends Form
{
    public string $description = '';

    public string $supplier = '';

    public ?string $date = null;

    public string $amount = '';

    public string $currency = 'BRL';

    /** @var list<array{unit_id: string, percentage: string}> */
    public array $allocations = [['unit_id' => '', 'percentage' => '100']];

    public function addAllocation(): void
    {
        $this->allocations[] = ['unit_id' => '', 'percentage' => ''];
    }

    public function removeAllocation(int $index): void
    {
        if (count($this->allocations) > 1) {
            array_splice($this->allocations, $index, 1);
        }
    }

    // Divide 100% igualmente; o que sobra da divisão vai para as primeiras linhas (3 unidades: 33,34 / 33,33 / 33,33)
    public function splitEvenly(): void
    {
        $count = count($this->allocations);
        $base = intdiv(AllocationSplitter::TOTAL_BASIS_POINTS, $count);
        $remainder = AllocationSplitter::TOTAL_BASIS_POINTS % $count;

        foreach ($this->allocations as $index => $allocation) {
            $this->allocations[$index]['percentage'] = str_replace('.', ',', Decimal::fromHundredths($base + ($index < $remainder ? 1 : 0)));
        }
    }

    /**
     * Prévia do rateio enquanto o usuário digita: soma dos percentuais e o valor de cada linha.
     *
     * @return array{amountCents: int|null, totalBasisPoints: int|null, shares: array<int, int>|null}
     */
    public function preview(): array
    {
        $amountCents = $this->toHundredths($this->amount);
        $basisPoints = array_map(fn (array $allocation): ?int => $this->toHundredths($allocation['percentage']), $this->allocations);
        $totalBasisPoints = in_array(null, $basisPoints, true) ? null : array_sum($basisPoints);

        // Mesmo algoritmo do backend: a prévia mostra exatamente os centavos que serão gravados
        $shares = null;
        if ($amountCents !== null && $totalBasisPoints === AllocationSplitter::TOTAL_BASIS_POINTS && ! in_array(0, $basisPoints, true)) {
            $shares = app(AllocationSplitter::class)->split($amountCents, $basisPoints);
        }

        return ['amountCents' => $amountCents, 'totalBasisPoints' => $totalBasisPoints, 'shares' => $shares];
    }

    /**
     * @throws ValidationException
     */
    public function store(CreateExpense $createExpense): Expense
    {
        $this->description = Str::squish($this->description);
        $this->supplier = Str::squish($this->supplier);

        $data = [
            'description' => $this->description,
            'supplier' => $this->supplier,
            'date' => $this->date,
            'amount' => $this->normalizeDecimal($this->amount),
            'currency' => $this->currency,
            'allocations' => array_map(fn (array $allocation): array => [
                'unit_id' => $allocation['unit_id'],
                'percentage' => $this->normalizeDecimal($allocation['percentage']),
            ], $this->allocations),
        ];

        // Na tela o usuário digita no formato brasileiro: a mensagem de formato mostra esse formato, não o da API
        $validator = validator($data, ExpenseRules::rules(), [
            'amount.regex' => 'Informe o valor com até 2 casas decimais, ex.: 1.500,00.',
            'allocations.*.percentage.regex' => 'Informe o percentual com até 2 casas decimais, ex.: 33,34.',
        ])->after(ExpenseRules::allocationsSumToOneHundred());

        if ($validator->fails()) {
            // Mesmo prefixo que o Form Object aplica: o erro aparece embaixo do campo com wire:model="form.x"
            throw ValidationException::withMessages(
                collect($validator->errors()->messages())->mapWithKeys(fn (array $messages, string $key): array => ["form.{$key}" => $messages])->all(),
            );
        }

        return $createExpense->execute(ExpenseData::fromValidated($validator->validated()));
    }

    // "1.500,00" -> "1500.00"; "33,34" -> "33.34"; valores já com ponto decimal ficam como estão
    private function normalizeDecimal(string $value): string
    {
        $value = trim($value);

        return str_contains($value, ',') ? str_replace(['.', ','], ['', '.'], $value) : $value;
    }

    private function toHundredths(string $value): ?int
    {
        try {
            return Decimal::toHundredths($this->normalizeDecimal($value));
        } catch (InvalidArgumentException) {
            return null;
        }
    }
}
