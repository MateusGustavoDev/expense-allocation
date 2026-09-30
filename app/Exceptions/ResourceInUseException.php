<?php

declare(strict_types=1);

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

// Lançada ao tentar remover um registro que ainda é referenciado por outros (HTTP 409)
final class ResourceInUseException extends ConflictHttpException {}
