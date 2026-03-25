<?php

declare(strict_types=1);

namespace Tripwire\Server\Resource;

final class FingerprintDetail
{
    /**
     * @param array<int, int> $fingerprintVector
     * @param array<int, FingerprintSessionSummary> $sessions
     */
    public function __construct(
        public readonly string $object,
        public readonly string $id,
        public readonly string $firstSeenAt,
        public readonly string $lastSeenAt,
        public readonly int $seenCount,
        public readonly string $lastUserAgent,
        public readonly string $lastIp,
        public readonly string $expiresAt,
        public readonly ?string $anchorWebglHash,
        public readonly ?string $anchorParamsHash,
        public readonly ?string $anchorAudioHash,
        public readonly array $fingerprintVector,
        public readonly bool $hasCookie,
        public readonly bool $hasLs,
        public readonly bool $hasIdb,
        public readonly bool $hasSw,
        public readonly bool $hasWn,
        public readonly array $sessions,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $sessions = [];
        foreach ((array) ($data['sessions'] ?? []) as $item) {
            $sessions[] = FingerprintSessionSummary::fromArray((array) $item);
        }

        return new self(
            (string) $data['object'],
            (string) $data['id'],
            (string) $data['firstSeenAt'],
            (string) $data['lastSeenAt'],
            (int) $data['seenCount'],
            (string) $data['lastUserAgent'],
            (string) $data['lastIp'],
            (string) $data['expiresAt'],
            isset($data['anchorWebglHash']) ? (string) $data['anchorWebglHash'] : null,
            isset($data['anchorParamsHash']) ? (string) $data['anchorParamsHash'] : null,
            isset($data['anchorAudioHash']) ? (string) $data['anchorAudioHash'] : null,
            array_map(static fn (mixed $value): int => (int) $value, (array) ($data['fingerprintVector'] ?? [])),
            (bool) ($data['hasCookie'] ?? false),
            (bool) ($data['hasLs'] ?? false),
            (bool) ($data['hasIdb'] ?? false),
            (bool) ($data['hasSw'] ?? false),
            (bool) ($data['hasWn'] ?? false),
            $sessions,
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
            'firstSeenAt' => $this->firstSeenAt,
            'lastSeenAt' => $this->lastSeenAt,
            'seenCount' => $this->seenCount,
            'lastUserAgent' => $this->lastUserAgent,
            'lastIp' => $this->lastIp,
            'expiresAt' => $this->expiresAt,
            'anchorWebglHash' => $this->anchorWebglHash,
            'anchorParamsHash' => $this->anchorParamsHash,
            'anchorAudioHash' => $this->anchorAudioHash,
            'fingerprintVector' => $this->fingerprintVector,
            'hasCookie' => $this->hasCookie,
            'hasLs' => $this->hasLs,
            'hasIdb' => $this->hasIdb,
            'hasSw' => $this->hasSw,
            'hasWn' => $this->hasWn,
            'sessions' => array_map(static fn (FingerprintSessionSummary $item): array => $item->toArray(), $this->sessions),
        ];
    }
}

