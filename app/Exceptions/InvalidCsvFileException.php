<?php

declare(strict_types=1);

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

// O arquivo inteiro é inválido (vazio ou com cabeçalho errado): nenhuma linha é processada - HTTP 422
final class InvalidCsvFileException extends UnprocessableEntityHttpException {}
