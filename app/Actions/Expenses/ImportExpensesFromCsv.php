<?php

declare(strict_types=1);

namespace App\Actions\Expenses;

use App\Data\ExpenseData;
use App\Data\ImportReport;
use App\Data\ImportRowError;
use App\Exceptions\InvalidAllocationException;
use App\Exceptions\InvalidCsvFileException;
use App\Models\Unit;
use App\Services\Csv\CsvReader;
use App\Validation\ExpenseRules;
use Illuminate\Contracts\Validation\Factory as ValidatorFactory;
use Illuminate\Support\Str;

/**
 * Importa despesas de um CSV no formato data;descricao;fornecedor;valor;moeda;rateio.
 *
 * Cada linha é validada com as mesmas regras da API e criada pela mesma CreateExpense, na sua própria transação:
 * uma linha inválida nunca impede a importação das válidas. Unidades são referenciadas pelo slug.
 */
final class ImportExpensesFromCsv
{
    public const HEADER = ['data', 'descricao', 'fornecedor', 'valor', 'moeda', 'rateio'];

    public function __construct(
        private readonly CsvReader $reader,
        private readonly CreateExpense $createExpense,
        private readonly ValidatorFactory $validator,
    ) {}

    /**
     * @throws InvalidCsvFileException quando o arquivo está vazio ou o cabeçalho não é o esperado
     */
    public function execute(string $path): ImportReport
    {
        $rows = $this->reader->rows($path);

        if (! $rows->valid()) {
            throw new InvalidCsvFileException('O arquivo está vazio.');
        }

        $this->assertHeader($rows->current());
        $rows->next();

        // Uma consulta para o arquivo inteiro, em vez de uma por linha
        $unitIdsBySlug = Unit::query()->pluck('id', 'slug')->all();

        $total = 0;
        $imported = 0;
        $errors = [];

        for (; $rows->valid(); $rows->next()) {
            $total++;
            $fields = $rows->current();
            $messages = $this->importRow($fields, $unitIdsBySlug);

            if ($messages === []) {
                $imported++;
            } else {
                $errors[] = new ImportRowError($rows->key(), implode(';', $fields), $messages);
            }
        }

        return new ImportReport($total, $imported, $errors);
    }

    /**
     * @param  list<string>  $header
     */
    private function assertHeader(array $header): void
    {
        if (array_map(mb_strtolower(...), $header) !== self::HEADER) {
            throw new InvalidCsvFileException('Cabeçalho inválido. A primeira linha deve ser: '.implode(';', self::HEADER).'.');
        }
    }

    /**
     * @param  list<string>  $fields
     * @param  array<string, int>  $unitIdsBySlug
     * @return list<string> motivos da recusa; vazio quando a despesa foi criada
     */
    private function importRow(array $fields, array $unitIdsBySlug): array
    {
        if (count($fields) !== count(self::HEADER)) {
            return [sprintf('A linha deve ter %d colunas separadas por ponto e vírgula (encontradas: %d).', count(self::HEADER), count($fields))];
        }

        [$date, $description, $supplier, $amount, $currency, $allocationField] = $fields;

        [$allocations, $allocationErrors] = $this->parseAllocations($allocationField, $unitIdsBySlug);

        if ($allocationErrors !== []) {
            return $allocationErrors;
        }

        $validator = $this->validator
            ->make([
                'date' => $date,
                'description' => Str::squish($description),
                'supplier' => Str::squish($supplier),
                'amount' => $amount,
                'currency' => mb_strtoupper($currency),
                'allocations' => $allocations,
            ], ExpenseRules::rules())
            ->after(ExpenseRules::allocationsSumToOneHundred());

        if ($validator->fails()) {
            return array_values(array_unique($validator->errors()->all()));
        }

        try {
            $this->createExpense->execute(ExpenseData::fromValidated($validator->validated()));
        } catch (InvalidAllocationException $exception) {
            return [$exception->getMessage()];
        }

        return [];
    }

    /**
     * Converte "unidade-a:50|unidade-b:50" em [['unit_id' => 1, 'percentage' => '50'], ...].
     *
     * @param  array<string, int>  $unitIdsBySlug
     * @return array{0: list<array{unit_id: int, percentage: string}>, 1: list<string>}
     */
    private function parseAllocations(string $field, array $unitIdsBySlug): array
    {
        if ($field === '') {
            return [[], ['Informe o rateio no formato slug:percentual, separados por |.']];
        }

        $allocations = [];
        $errors = [];

        foreach (explode('|', $field) as $part) {
            if (preg_match('/^\s*([^:]+?)\s*:\s*(.+?)\s*$/', $part, $matches) !== 1) {
                $errors[] = "Trecho de rateio inválido: \"{$part}\". Use slug:percentual.";

                continue;
            }

            $slug = mb_strtolower($matches[1]);

            if (! isset($unitIdsBySlug[$slug])) {
                $errors[] = "Unidade \"{$slug}\" não encontrada.";

                continue;
            }

            $allocations[] = ['unit_id' => $unitIdsBySlug[$slug], 'percentage' => $matches[2]];
        }

        return [$allocations, $errors];
    }
}
