<?php

declare(strict_types=1);

namespace Tripwire\Server\Resource;

final class WebhookEndpoint
{
    /**
     * @param array<int, string> $event_types
     */
    public function __construct(
        public readonly string $object,
        public readonly string $id,
        public readonly string $name,
        public readonly string $url,
        public readonly string $status,
        public readonly array $event_types,
        public readonly string $created_at,
        public readonly string $updated_at,
        public readonly ?string $signing_secret = null,
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
            (string) $data['name'],
            (string) $data['url'],
            (string) $data['status'],
            array_map(static fn (mixed $value): string => (string) $value, (array) ($data['event_types'] ?? [])),
            (string) $data['created_at'],
            (string) $data['updated_at'],
            isset($data['signing_secret']) ? (string) $data['signing_secret'] : null,
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
            'name' => $this->name,
            'url' => $this->url,
            'status' => $this->status,
            'event_types' => $this->event_types,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'signing_secret' => $this->signing_secret,
        ];
    }
}
