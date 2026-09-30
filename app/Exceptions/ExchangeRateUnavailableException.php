<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

// O provedor respondeu, mas não há cotação para a data: tentar de novo não resolve
final class ExchangeRateUnavailableException extends RuntimeException {}
