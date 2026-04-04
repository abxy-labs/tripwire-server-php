<?php

declare(strict_types=1);

namespace Tripwire\Server\Resource;

final class GateSessionPollData
{
    public function __construct(
        public readonly string $object,
        public readonly string $id,
        public readonly string $status,
        public readonly ?string $expires_at,
        public readonly ?string $gate_account_id,
        public readonly ?string $account_name,
        public readonly ?GateDeliveryBundle $delivery_bundle,
        public readonly ?string $docs_url,
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
            isset($data['expires_at']) ? (string) $data['expires_at'] : null,
            isset($data['gate_account_id']) ? (string) $data['gate_account_id'] : null,
            isset($data['account_name']) ? (string) $data['account_name'] : null,
            isset($data['delivery_bundle']) && is_array($data['delivery_bundle'])
                ? GateDeliveryBundle::fromArray($data['delivery_bundle'])
                : null,
            isset($data['docs_url']) ? (string) $data['docs_url'] : null,
        );
    }
}
