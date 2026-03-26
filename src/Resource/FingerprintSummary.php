<?php

declare(strict_types=1);

namespace Tripwire\Server\Resource;

final class FingerprintSummary
{
    /**
     * @param array<string, mixed> $lifecycle
     * @param array<string, mixed> $latest_request
     * @param array<string, mixed> $storage
     * @param array<string, mixed> $anchors
     */
    public function __construct(
        public readonly string $object,
        public readonly string $id,
        public readonly array $lifecycle,
        public readonly array $latest_request,
        public readonly array $storage,
        public readonly array $anchors,
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
            (array) $data['lifecycle'],
            (array) $data['latest_request'],
            (array) $data['storage'],
            (array) $data['anchors'],
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
            'lifecycle' => $this->lifecycle,
            'latest_request' => $this->latest_request,
            'storage' => $this->storage,
            'anchors' => $this->anchors,
        ];
    }
}
