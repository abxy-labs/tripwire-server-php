<?php

declare(strict_types=1);

namespace Tripwire\Server\Resource;

final class GateDeliveryBundle
{
    public function __construct(
        public readonly GateDeliveryEnvelope $integrator,
        public readonly GateDeliveryEnvelope $gate,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            GateDeliveryEnvelope::fromArray((array) $data['integrator']),
            GateDeliveryEnvelope::fromArray((array) $data['gate']),
        );
    }
}
