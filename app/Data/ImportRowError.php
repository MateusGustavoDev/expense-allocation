<?php

declare(strict_types=1);

namespace App\Data;

// Linha do arquivo que não foi importada, com os motivos
final readonly class ImportRowError
{
    /**
     * @param  list<string>  $messages
     */
    public function __construct(
        // Número da linha no arquivo, contando o cabeçalho como linha 1
        public int $line,
        public string $content,
        public array $messages,
    ) {}
}
