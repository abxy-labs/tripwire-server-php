<?php

declare(strict_types=1);

namespace Tripwire\Server\Resource;

final class GateDashboardLogin
{
    public function __construct(
        public readonly string $object,
        public readonly string $gate_account_id,
        public readonly string $account_name,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            (string) $data['object'],
            (string) $data['gate_account_id'],
            (string) $data['account_name'],
        );
    }
}
