<?php

declare(strict_types=1);

namespace Tripwire\Server\Exception;

use RuntimeException;

final class TripwireApiError extends RuntimeException
{
    /**
     * @param array<int, array<string, mixed>> $fieldErrors
     */
    public function __construct(
        public readonly int $status,
        public readonly string $errorCode,
        string $message,
        public readonly ?string $requestId = null,
        public readonly array $fieldErrors = [],
        public readonly ?string $docsUrl = null,
        public readonly mixed $body = null,
    ) {
        parent::__construct($message);
    }

    public function __get(string $name): mixed
    {
        if ($name === 'code') {
            return $this->errorCode;
        }

        trigger_error(sprintf('Undefined property: %s::$%s', self::class, $name), E_USER_NOTICE);

        return null;
    }

    public function __isset(string $name): bool
    {
        return $name === 'code';
    }
}
