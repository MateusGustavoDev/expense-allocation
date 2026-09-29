<?php

declare(strict_types=1);

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

// Rateio que viola as regras de negócio (soma diferente de 100%, unidade repetida) - HTTP 422
final class InvalidAllocationException extends UnprocessableEntityHttpException {}
