<?php

declare(strict_types=1);

namespace Tripwire\Server\Resource;

final class GateLoginSession
{
    public function __construct(
        public readonly string $object,
        public readonly string $id,
        public readonly string $status,
        public readonly string $consent_url,
        public readonly string $expires_at,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            (string) $data['object'],
            (string) $data['id'],
            (string) $data['status'],
            (string) $data['consent_url'],
            (string) $data['expires_at'],
        );
    }
}
