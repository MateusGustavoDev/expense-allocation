<?php

declare(strict_types=1);

namespace App\Data;

// Resultado de uma importação: as linhas válidas entram mesmo quando outras falham
final readonly class ImportReport
{
    /**
     * @param  list<ImportRowError>  $errors
     */
    public function __construct(
        public int $totalRows,
        public int $importedCount,
        public array $errors,
    ) {}

    public function failedCount(): int
    {
        return count($this->errors);
    }
}
