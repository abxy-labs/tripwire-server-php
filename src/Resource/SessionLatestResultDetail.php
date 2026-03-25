<?php

declare(strict_types=1);

namespace Tripwire\Server\Resource;

final class SessionLatestResultDetail
{
    public function __construct(
        public readonly string $eventId,
        public readonly string $verdict,
        public readonly int $riskScore,
        public readonly ?string $phase,
        public readonly ?bool $provisional,
        public readonly ?int $manipulationScore,
        public readonly ?string $manipulationVerdict,
        public readonly ?int $evaluationDuration,
        public readonly string $scoredAt,
        public readonly ?string $visitorId,
        public readonly SessionMetadata $metadata,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            (string) $data['eventId'],
            (string) $data['verdict'],
            (int) $data['riskScore'],
            isset($data['phase']) ? (string) $data['phase'] : null,
            array_key_exists('provisional', $data) ? ($data['provisional'] === null ? null : (bool) $data['provisional']) : null,
            array_key_exists('manipulationScore', $data) ? ($data['manipulationScore'] === null ? null : (int) $data['manipulationScore']) : null,
            isset($data['manipulationVerdict']) ? (string) $data['manipulationVerdict'] : null,
            array_key_exists('evaluationDuration', $data) ? ($data['evaluationDuration'] === null ? null : (int) $data['evaluationDuration']) : null,
            (string) $data['scoredAt'],
            isset($data['visitorId']) ? (string) $data['visitorId'] : null,
            SessionMetadata::fromArray((array) $data['metadata']),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'eventId' => $this->eventId,
            'verdict' => $this->verdict,
            'riskScore' => $this->riskScore,
            'phase' => $this->phase,
            'provisional' => $this->provisional,
            'manipulationScore' => $this->manipulationScore,
            'manipulationVerdict' => $this->manipulationVerdict,
            'evaluationDuration' => $this->evaluationDuration,
            'scoredAt' => $this->scoredAt,
            'visitorId' => $this->visitorId,
            'metadata' => $this->metadata->toArray(),
        ];
    }
}

