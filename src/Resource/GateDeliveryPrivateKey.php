<?php

declare(strict_types=1);

namespace Tripwire\Server\Resource;

final class GateDeliveryPrivateKey
{
    public function __construct(public readonly string $bytes)
    {
    }
}
