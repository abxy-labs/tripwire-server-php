<?php

declare(strict_types=1);

namespace Tripwire\Server\Resource;

final class Team
{
    public function __construct(
        public readonly string $object,
        public readonly string $id,
        public readonly string $name,
        public readonly string $slug,
        public readonly string $status,
        public readonly string $createdAt,
        public readonly ?string $updatedAt,
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
            (string) $data['createdAt'],
            isset($data['updatedAt']) ? (string) $data['updatedAt'] : null,
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
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt,
        ];
    }
}

