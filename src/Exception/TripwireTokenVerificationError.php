<?php

declare(strict_types=1);

namespace Tripwire\Server\Exception;

use RuntimeException;
use Throwable;

final class TripwireTokenVerificationError extends RuntimeException
{
    public function __construct(string $message, ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}

