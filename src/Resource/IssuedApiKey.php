<?php

declare(strict_types=1);

namespace Tripwire\Server\Resource;

final class IssuedApiKey extends ApiKey
{
    /**
     * @param array<int, string>|null $allowed_origins
     */
    public function __construct(
        string $object,
        string $id,
        string $public_key,
        string $name,
        string $environment,
        ?array $allowed_origins,
        ?int $rate_limit,
        string $status,
        string $created_at,
        ?string $rotated_at,
        ?string $revoked_at,
        public readonly string $secret_key,
    ) {
        parent::__construct(
            $object,
            $id,
            $public_key,
            $name,
            $environment,
            $allowed_origins,
            $rate_limit,
            $status,
            $created_at,
            $rotated_at,
            $revoked_at,
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            (string) $data['object'],
            (string) $data['id'],
            (string) $data['public_key'],
            (string) $data['name'],
            (string) $data['environment'],
            isset($data['allowed_origins']) ? array_map(static fn (mixed $value): string => (string) $value, (array) $data['allowed_origins']) : null,
            array_key_exists('rate_limit', $data) ? ($data['rate_limit'] === null ? null : (int) $data['rate_limit']) : null,
            (string) $data['status'],
            (string) $data['created_at'],
            isset($data['rotated_at']) ? (string) $data['rotated_at'] : null,
            isset($data['revoked_at']) ? (string) $data['revoked_at'] : null,
            (string) $data['secret_key'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return parent::toArray() + ['secret_key' => $this->secret_key];
    }
}
