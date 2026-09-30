<?php

declare(strict_types=1);

namespace App\Livewire\Expenses;

use App\Actions\Expenses\ImportExpensesFromCsv;
use App\Data\ImportRowError;
use App\Exceptions\InvalidCsvFileException;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * Importação de despesas por CSV, com a mesma Action da API (POST /api/expenses/import).
 * Linhas inválidas não impedem as válidas; o relatório lista os erros pelo número da linha.
 */
#[Title('Importar despesas')]
final class ImportExpensesPage extends Component
{
    use WithFileUploads;

    // Arquivo CSV separado por ponto e vírgula, até 5 MB
    #[Validate(['bail', 'required', 'file', 'extensions:csv,txt', 'max:5120'])]
    public ?TemporaryUploadedFile $file = null;

    /** @var array{file_name: string, total_rows: int, imported: int, failed: int, errors: list<array{line: int, content: string, messages: list<string>}>}|null */
    public ?array $report = null;

    public function import(ImportExpensesFromCsv $import): void
    {
        $this->validate();

        /** @var TemporaryUploadedFile $file */
        $file = $this->file;

        try {
            $result = $import->execute($file->getRealPath());
        } catch (InvalidCsvFileException $exception) {
            $this->addError('file', $exception->getMessage());

            return;
        }

        $this->report = [
            'file_name' => $file->getClientOriginalName(),
            'total_rows' => $result->totalRows,
            'imported' => $result->importedCount,
            'failed' => $result->failedCount(),
            'errors' => array_map(fn (ImportRowError $error): array => [
                'line' => $error->line,
                'content' => $error->content,
                'messages' => $error->messages,
            ], $result->errors),
        ];

        $this->reset('file');
        $this->dispatch('toast', type: $result->failedCount() > 0 ? 'warning' : 'success', message: "{$result->importedCount} de {$result->totalRows} linhas importadas.");
    }

    public function render(): View
    {
        return view('livewire.expenses.import-expenses-page');
    }
}
