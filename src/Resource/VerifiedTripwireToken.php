<?php

declare(strict_types=1);

namespace Tripwire\Server\Resource;

final class VerifiedTripwireToken
{
    /**
     * @param array<string, mixed> $raw
     * @param array<int, array<string, mixed>> $signals
     * @param array<string, int> $categoryScores
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public readonly string $eventId,
        public readonly string $sessionId,
        public readonly string $verdict,
        public readonly int $score,
        public readonly ?int $manipulationScore,
        public readonly ?string $manipulationVerdict,
        public readonly ?int $evaluationDuration,
        public readonly int $scoredAt,
        public readonly array $metadata,
        public readonly array $signals,
        public readonly array $categoryScores,
        public readonly mixed $botAttribution,
        public readonly ?string $visitorId,
        public readonly ?int $visitorIdConfidence,
        public readonly mixed $embedContext,
        public readonly ?string $phase,
        public readonly ?bool $provisional,
        public readonly array $raw,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $categoryScores = [];
        foreach ((array) ($data['categoryScores'] ?? []) as $key => $value) {
            $categoryScores[(string) $key] = (int) $value;
        }

        $signals = [];
        foreach ((array) ($data['signals'] ?? []) as $signal) {
            $signals[] = (array) $signal;
        }

        return new self(
            (string) $data['eventId'],
            (string) $data['sessionId'],
            (string) $data['verdict'],
            (int) $data['score'],
            array_key_exists('manipulationScore', $data) ? ($data['manipulationScore'] === null ? null : (int) $data['manipulationScore']) : null,
            isset($data['manipulationVerdict']) ? (string) $data['manipulationVerdict'] : null,
            array_key_exists('evaluationDuration', $data) ? ($data['evaluationDuration'] === null ? null : (int) $data['evaluationDuration']) : null,
            (int) $data['scoredAt'],
            (array) ($data['metadata'] ?? []),
            $signals,
            $categoryScores,
            $data['botAttribution'] ?? null,
            isset($data['visitorId']) ? (string) $data['visitorId'] : null,
            array_key_exists('visitorIdConfidence', $data) ? ($data['visitorIdConfidence'] === null ? null : (int) $data['visitorIdConfidence']) : null,
            $data['embedContext'] ?? null,
            isset($data['phase']) ? (string) $data['phase'] : null,
            array_key_exists('provisional', $data) ? ($data['provisional'] === null ? null : (bool) $data['provisional']) : null,
            $data,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->raw;
    }
}
