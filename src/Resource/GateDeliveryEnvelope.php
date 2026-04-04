<?php

declare(strict_types=1);

namespace Tripwire\Server\Resource;

final class GateDeliveryEnvelope
{
    public function __construct(
        public readonly int $version,
        public readonly string $algorithm,
        public readonly string $key_id,
        public readonly string $ephemeral_public_key,
        public readonly string $salt,
        public readonly string $iv,
        public readonly string $ciphertext,
        public readonly string $tag,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            (int) $data['version'],
            (string) $data['algorithm'],
            (string) $data['key_id'],
            (string) $data['ephemeral_public_key'],
            (string) $data['salt'],
            (string) $data['iv'],
            (string) $data['ciphertext'],
            (string) $data['tag'],
        );
    }
}
