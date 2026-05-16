<?php

declare(strict_types=1);

namespace Foil\Server\Result;

use Throwable;
use Foil\Server\Resource\VerifiedFoilToken;

final class SafeVerifyResult
{
    public function __construct(
        public readonly bool $ok,
        public readonly ?VerifiedFoilToken $data = null,
        public readonly ?Throwable $error = null,
    ) {
    }

    public static function success(VerifiedFoilToken $data): self
    {
        return new self(true, $data, null);
    }

    public static function failure(Throwable $error): self
    {
        return new self(false, null, $error);
    }
}

