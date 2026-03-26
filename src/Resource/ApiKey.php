<?php

declare(strict_types=1);

namespace Tripwire\Server\Resource;

class ApiKey
{
    /**
     * @param array<int, string>|null $allowed_origins
     */
    public function __construct(
        public readonly string $object,
        public readonly string $id,
        public readonly string $public_key,
        public readonly string $name,
        public readonly string $environment,
        public readonly ?array $allowed_origins,
        public readonly ?int $rate_limit,
        public readonly string $status,
        public readonly string $created_at,
        public readonly ?string $rotated_at,
        public readonly ?string $revoked_at,
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
            (string) $data['public_key'],
            (string) $data['name'],
            (string) $data['environment'],
            isset($data['allowed_origins']) ? array_map(static fn (mixed $value): string => (string) $value, (array) $data['allowed_origins']) : null,
            array_key_exists('rate_limit', $data) ? ($data['rate_limit'] === null ? null : (int) $data['rate_limit']) : null,
            (string) $data['status'],
            (string) $data['created_at'],
            isset($data['rotated_at']) ? (string) $data['rotated_at'] : null,
            isset($data['revoked_at']) ? (string) $data['revoked_at'] : null,
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
            'public_key' => $this->public_key,
            'name' => $this->name,
            'environment' => $this->environment,
            'allowed_origins' => $this->allowed_origins,
            'rate_limit' => $this->rate_limit,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'rotated_at' => $this->rotated_at,
            'revoked_at' => $this->revoked_at,
        ];
    }
}
