<?php

declare(strict_types=1);

namespace Tripwire\Server\Resource;

final class AgentTokenVerification
{
    public function __construct(
        public readonly bool $valid,
        public readonly ?string $gate_account_id,
        public readonly ?string $status,
        public readonly ?string $created_at,
        public readonly ?string $expires_at,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            (bool) $data['valid'],
            isset($data['gate_account_id']) ? (string) $data['gate_account_id'] : null,
            isset($data['status']) ? (string) $data['status'] : null,
            isset($data['created_at']) ? (string) $data['created_at'] : null,
            isset($data['expires_at']) ? (string) $data['expires_at'] : null,
        );
    }
}
