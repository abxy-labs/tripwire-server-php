<?php

declare(strict_types=1);

namespace Tripwire\Server\Resource;

final class Organization
{
    public function __construct(
        public readonly string $object,
        public readonly string $id,
        public readonly string $name,
        public readonly string $slug,
        public readonly string $status,
        public readonly string $created_at,
        public readonly ?string $updated_at,
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
            (string) $data['slug'],
            (string) $data['status'],
            (string) $data['created_at'],
            isset($data['updated_at']) ? (string) $data['updated_at'] : null,
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
            'slug' => $this->slug,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
