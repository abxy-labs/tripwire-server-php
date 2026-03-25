<?php

declare(strict_types=1);

namespace Tripwire\Server\Resource;

final class SessionSummary
{
    public function __construct(
        public readonly string $object,
        public readonly string $id,
        public readonly ?string $createdAt,
        public readonly string $latestEventId,
        public readonly ResultSummary $latestResult,
        public readonly ?FingerprintReference $fingerprint,
        public readonly string $lastScoredAt,
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
            isset($data['createdAt']) ? (string) $data['createdAt'] : null,
            (string) $data['latestEventId'],
            ResultSummary::fromArray((array) $data['latestResult']),
            isset($data['fingerprint']) && is_array($data['fingerprint']) ? FingerprintReference::fromArray($data['fingerprint']) : null,
            (string) $data['lastScoredAt'],
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
            'createdAt' => $this->createdAt,
            'latestEventId' => $this->latestEventId,
            'latestResult' => $this->latestResult->toArray(),
            'fingerprint' => $this->fingerprint?->toArray(),
            'lastScoredAt' => $this->lastScoredAt,
        ];
    }
}

