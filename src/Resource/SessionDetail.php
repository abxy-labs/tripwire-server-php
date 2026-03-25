<?php

declare(strict_types=1);

namespace Tripwire\Server\Resource;

final class SessionDetail
{
    /**
     * @param array<int, ResultSummary> $resultHistory
     * @param array<string, mixed>|null $ipIntel
     */
    public function __construct(
        public readonly string $object,
        public readonly string $id,
        public readonly ?string $createdAt,
        public readonly string $latestEventId,
        public readonly SessionLatestResultDetail $latestResult,
        public readonly ?array $ipIntel,
        public readonly ?FingerprintReference $fingerprint,
        public readonly array $resultHistory,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $resultHistory = [];
        foreach ((array) ($data['resultHistory'] ?? []) as $item) {
            $resultHistory[] = ResultSummary::fromArray((array) $item);
        }

        return new self(
            (string) $data['object'],
            (string) $data['id'],
            isset($data['createdAt']) ? (string) $data['createdAt'] : null,
            (string) $data['latestEventId'],
            SessionLatestResultDetail::fromArray((array) $data['latestResult']),
            isset($data['ipIntel']) && is_array($data['ipIntel']) ? $data['ipIntel'] : null,
            isset($data['fingerprint']) && is_array($data['fingerprint']) ? FingerprintReference::fromArray($data['fingerprint']) : null,
            $resultHistory,
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
            'ipIntel' => $this->ipIntel,
            'fingerprint' => $this->fingerprint?->toArray(),
            'resultHistory' => array_map(static fn (ResultSummary $item): array => $item->toArray(), $this->resultHistory),
        ];
    }
}

