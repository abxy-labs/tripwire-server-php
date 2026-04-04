<?php

declare(strict_types=1);

namespace Tripwire\Server\Resource;

final class GateSessionDeliveryAcknowledgement
{
    public function __construct(
        public readonly string $object,
        public readonly string $gate_session_id,
        public readonly string $status,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            (string) $data['object'],
            (string) $data['gate_session_id'],
            (string) $data['status'],
        );
    }
}
