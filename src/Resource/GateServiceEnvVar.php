<?php

declare(strict_types=1);

namespace Tripwire\Server\Resource;

final class GateServiceEnvVar
{
    public function __construct(
        public readonly string $name,
        public readonly string $key,
        public readonly bool $secret,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            (string) $data['name'],
            (string) $data['key'],
            (bool) $data['secret'],
        );
    }
}
