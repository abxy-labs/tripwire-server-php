<?php

declare(strict_types=1);

namespace Tripwire\Server\Resource;

final class FingerprintReference
{
    public function __construct(
        public readonly string $object,
        public readonly string $id,
        public readonly ?int $confidence,
        public readonly ?string $timestamp,
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
            array_key_exists('confidence', $data) ? ($data['confidence'] === null ? null : (int) $data['confidence']) : null,
            isset($data['timestamp']) ? (string) $data['timestamp'] : null,
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
            'confidence' => $this->confidence,
            'timestamp' => $this->timestamp,
        ];
    }
}

