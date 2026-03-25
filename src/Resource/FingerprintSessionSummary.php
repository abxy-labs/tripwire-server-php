<?php

declare(strict_types=1);

namespace Tripwire\Server\Resource;

final class FingerprintSessionSummary
{
    /**
     * @param array<string, int>|null $categoryScores
     */
    public function __construct(
        public readonly string $eventId,
        public readonly string $verdict,
        public readonly int $riskScore,
        public readonly string $scoredAt,
        public readonly string $userAgent,
        public readonly string $url,
        public readonly string $clientIp,
        public readonly ?string $screenSize,
        public readonly ?array $categoryScores,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $categoryScores = null;
        if (isset($data['categoryScores']) && is_array($data['categoryScores'])) {
            $categoryScores = [];
            foreach ($data['categoryScores'] as $key => $value) {
                $categoryScores[(string) $key] = (int) $value;
            }
        }

        return new self(
            (string) $data['eventId'],
            (string) $data['verdict'],
            (int) $data['riskScore'],
            (string) $data['scoredAt'],
            (string) $data['userAgent'],
            (string) $data['url'],
            (string) $data['clientIp'],
            isset($data['screenSize']) ? (string) $data['screenSize'] : null,
            $categoryScores,
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
            'scoredAt' => $this->scoredAt,
            'userAgent' => $this->userAgent,
            'url' => $this->url,
            'clientIp' => $this->clientIp,
            'screenSize' => $this->screenSize,
            'categoryScores' => $this->categoryScores,
        ];
    }
}

