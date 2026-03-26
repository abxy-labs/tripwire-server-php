<?php

declare(strict_types=1);

namespace Tripwire\Server\Resource;

final class VerifiedTripwireToken
{
    /**
     * @param array<string, mixed> $decision
     * @param array<string, mixed> $request
     * @param array<string, mixed>|null $visitor_fingerprint
     * @param array<int, array<string, mixed>> $signals
     * @param array<string, mixed> $score_breakdown
     * @param array<string, mixed> $attribution
     * @param array<string, mixed>|null $embed
     * @param array<string, mixed> $raw
     */
    public function __construct(
        public readonly string $object,
        public readonly string $session_id,
        public readonly array $decision,
        public readonly array $request,
        public readonly ?array $visitor_fingerprint,
        public readonly array $signals,
        public readonly array $score_breakdown,
        public readonly array $attribution,
        public readonly ?array $embed,
        public readonly array $raw,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            (string) $data['object'],
            (string) $data['session_id'],
            (array) $data['decision'],
            (array) $data['request'],
            isset($data['visitor_fingerprint']) && is_array($data['visitor_fingerprint']) ? $data['visitor_fingerprint'] : null,
            array_map(static fn (mixed $signal): array => (array) $signal, (array) ($data['signals'] ?? [])),
            isset($data['score_breakdown']) && is_array($data['score_breakdown']) ? $data['score_breakdown'] : [],
            isset($data['attribution']) && is_array($data['attribution']) ? $data['attribution'] : [],
            isset($data['embed']) && is_array($data['embed']) ? $data['embed'] : null,
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
