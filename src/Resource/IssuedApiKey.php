<?php

declare(strict_types=1);

namespace Tripwire\Server\Resource;

final class IssuedApiKey extends ApiKey
{
    /**
     * @param array<int, string>|null $allowedOrigins
     */
    public function __construct(
        string $object,
        string $id,
        string $key,
        string $name,
        bool $isTest,
        ?array $allowedOrigins,
        ?int $rateLimit,
        string $status,
        string $createdAt,
        ?string $rotatedAt,
        ?string $revokedAt,
        public readonly string $secretKey,
    ) {
        parent::__construct(
            $object,
            $id,
            $key,
            $name,
            $isTest,
            $allowedOrigins,
            $rateLimit,
            $status,
            $createdAt,
            $rotatedAt,
            $revokedAt,
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
            (string) $data['key'],
            (string) $data['name'],
            (bool) $data['isTest'],
            isset($data['allowedOrigins']) ? array_map(static fn (mixed $value): string => (string) $value, (array) $data['allowedOrigins']) : null,
            array_key_exists('rateLimit', $data) ? ($data['rateLimit'] === null ? null : (int) $data['rateLimit']) : null,
            (string) $data['status'],
            (string) $data['createdAt'],
            isset($data['rotatedAt']) ? (string) $data['rotatedAt'] : null,
            isset($data['revokedAt']) ? (string) $data['revokedAt'] : null,
            (string) $data['secretKey'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return parent::toArray() + ['secretKey' => $this->secretKey];
    }
}

