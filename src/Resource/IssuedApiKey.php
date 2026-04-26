<?php

declare(strict_types=1);

namespace Tripwire\Server\Resource;

final class IssuedApiKey extends ApiKey
{
    /**
     * @param array<int, string>|null $allowed_origins
     * @param array<int, string>|null $scopes
     */
    public function __construct(
        string $object,
        string $id,
        string $type,
        string $name,
        string $environment,
        ?array $allowed_origins,
        ?array $scopes,
        ?int $rate_limit,
        string $status,
        string $key_preview,
        ?string $display_key,
        ?string $last_used_at,
        string $created_at,
        ?string $rotated_at,
        ?string $revoked_at,
        ?string $grace_expires_at,
        public readonly string $revealed_key,
    ) {
        parent::__construct(
            $object,
            $id,
            $type,
            $name,
            $environment,
            $allowed_origins,
            $scopes,
            $rate_limit,
            $status,
            $key_preview,
            $display_key,
            $last_used_at,
            $created_at,
            $rotated_at,
            $revoked_at,
            $grace_expires_at,
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
            (string) $data['type'],
            (string) $data['name'],
            (string) $data['environment'],
            isset($data['allowed_origins']) ? array_map(static fn (mixed $value): string => (string) $value, (array) $data['allowed_origins']) : null,
            isset($data['scopes']) ? array_map(static fn (mixed $value): string => (string) $value, (array) $data['scopes']) : null,
            array_key_exists('rate_limit', $data) ? ($data['rate_limit'] === null ? null : (int) $data['rate_limit']) : null,
            (string) $data['status'],
            (string) $data['key_preview'],
            isset($data['display_key']) ? (string) $data['display_key'] : null,
            isset($data['last_used_at']) ? (string) $data['last_used_at'] : null,
            (string) $data['created_at'],
            isset($data['rotated_at']) ? (string) $data['rotated_at'] : null,
            isset($data['revoked_at']) ? (string) $data['revoked_at'] : null,
            isset($data['grace_expires_at']) ? (string) $data['grace_expires_at'] : null,
            (string) $data['revealed_key'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return parent::toArray() + ['revealed_key' => $this->revealed_key];
    }
}
