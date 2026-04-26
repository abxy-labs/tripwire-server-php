<?php

declare(strict_types=1);

namespace Tripwire\Server\Resource;

final class EventSubject
{
    public function __construct(
        public readonly string $type,
        public readonly string $id,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            (string) $data['type'],
            (string) $data['id'],
        );
    }

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'id' => $this->id,
        ];
    }
}
