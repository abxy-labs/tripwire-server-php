<?php

declare(strict_types=1);

namespace Tripwire\Server\Resource;

class ApiKey
{
    /**
     * @param array<int, string>|null $allowed_origins
     * @param array<int, string>|null $scopes
     */
    public function __construct(
        public readonly string $object,
        public readonly string $id,
        public readonly string $type,
        public readonly string $name,
        public readonly string $environment,
        public readonly ?array $allowed_origins,
        public readonly ?array $scopes,
        public readonly ?int $rate_limit,
        public readonly string $status,
        public readonly string $key_preview,
        public readonly ?string $display_key,
        public readonly ?string $last_used_at,
        public readonly string $created_at,
        public readonly ?string $rotated_at,
        public readonly ?string $revoked_at,
        public readonly ?string $grace_expires_at,
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
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'object' => $this->object,
            'id' => $this->id,
            'type' => $this->type,
            'name' => $this->name,
            'environment' => $this->environment,
            'allowed_origins' => $this->allowed_origins,
            'scopes' => $this->scopes,
            'rate_limit' => $this->rate_limit,
            'status' => $this->status,
            'key_preview' => $this->key_preview,
            'display_key' => $this->display_key,
            'last_used_at' => $this->last_used_at,
            'created_at' => $this->created_at,
            'rotated_at' => $this->rotated_at,
            'revoked_at' => $this->revoked_at,
            'grace_expires_at' => $this->grace_expires_at,
        ];
    }
}
