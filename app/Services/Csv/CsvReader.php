<?php

declare(strict_types=1);

namespace App\Services\Csv;

use Generator;
use RuntimeException;

/**
 * Lê um CSV linha a linha, sem regra de negócio.
 *
 * Trata o que costuma quebrar importações de planilhas: BOM do Excel, quebras de linha do Windows (\r\n),
 * linhas em branco e arquivos salvos em Windows-1252 em vez de UTF-8. Cada registro ocupa uma linha física.
 */
final class CsvReader
{
    private const UTF8_BOM = "\xEF\xBB\xBF";

    /**
     * @return Generator<int, list<string>> campos de cada linha não vazia, indexados pelo número da linha no arquivo (a primeira é 1)
     */
    public function rows(string $path, string $delimiter = ';'): Generator
    {
        $handle = is_file($path) && is_readable($path) ? fopen($path, 'rb') : false;

        if ($handle === false) {
            throw new RuntimeException("Não foi possível ler o arquivo: {$path}.");
        }

        try {
            $lineNumber = 0;

            while (($line = fgets($handle)) !== false) {
                $lineNumber++;

                if ($lineNumber === 1 && str_starts_with($line, self::UTF8_BOM)) {
                    $line = substr($line, strlen(self::UTF8_BOM));
                }

                $line = rtrim($line, "\r\n");

                if (! mb_check_encoding($line, 'UTF-8')) {
                    $line = mb_convert_encoding($line, 'UTF-8', 'Windows-1252');
                }

                if (trim($line) === '') {
                    continue;
                }

                yield $lineNumber => array_map(
                    fn (?string $field): string => trim((string) $field),
                    str_getcsv($line, $delimiter, '"', ''),
                );
            }
        } finally {
            fclose($handle);
        }
    }
}
