<?php

declare(strict_types=1);

namespace Foil\Server\Exception;

use RuntimeException;
use Throwable;

final class FoilTokenVerificationError extends RuntimeException
{
    public function __construct(string $message, ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}

