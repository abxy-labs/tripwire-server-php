<?php

declare(strict_types=1);

namespace Tripwire\Server\Resource;

class ApiKey
{
    /**
     * @param array<int, string>|null $allowedOrigins
     */
    public function __construct(
        public readonly string $object,
        public readonly string $id,
        public readonly string $key,
        public readonly string $name,
        public readonly bool $isTest,
        public readonly ?array $allowedOrigins,
        public readonly ?int $rateLimit,
        public readonly string $status,
        public readonly string $createdAt,
        public readonly ?string $rotatedAt,
        public readonly ?string $revokedAt,
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
            (string) $data['key'],
            (string) $data['name'],
            (bool) $data['isTest'],
            isset($data['allowedOrigins']) ? array_map(static fn (mixed $value): string => (string) $value, (array) $data['allowedOrigins']) : null,
            array_key_exists('rateLimit', $data) ? ($data['rateLimit'] === null ? null : (int) $data['rateLimit']) : null,
            (string) $data['status'],
            (string) $data['createdAt'],
            isset($data['rotatedAt']) ? (string) $data['rotatedAt'] : null,
            isset($data['revokedAt']) ? (string) $data['revokedAt'] : null,
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
            'key' => $this->key,
            'name' => $this->name,
            'isTest' => $this->isTest,
            'allowedOrigins' => $this->allowedOrigins,
            'rateLimit' => $this->rateLimit,
            'status' => $this->status,
            'createdAt' => $this->createdAt,
            'rotatedAt' => $this->rotatedAt,
            'revokedAt' => $this->revokedAt,
        ];
    }
}

