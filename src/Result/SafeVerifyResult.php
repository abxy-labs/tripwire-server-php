<?php

declare(strict_types=1);

namespace Tripwire\Server\Result;

use Throwable;
use Tripwire\Server\Resource\VerifiedTripwireToken;

final class SafeVerifyResult
{
    public function __construct(
        public readonly bool $ok,
        public readonly ?VerifiedTripwireToken $data = null,
        public readonly ?Throwable $error = null,
    ) {
    }

    public static function success(VerifiedTripwireToken $data): self
    {
        return new self(true, $data, null);
    }

    public static function failure(Throwable $error): self
    {
        return new self(false, null, $error);
    }
}

