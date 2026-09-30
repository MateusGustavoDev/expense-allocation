<?php

declare(strict_types=1);

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

final class TooManyLoginAttemptsException extends TooManyRequestsHttpException
{
    public function __construct(public readonly int $retryAfterSeconds)
    {
        parent::__construct($retryAfterSeconds, __('auth.throttle', ['seconds' => $retryAfterSeconds]));
    }
}
