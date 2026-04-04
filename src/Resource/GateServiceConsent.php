<?php

declare(strict_types=1);

namespace Tripwire\Server\Resource;

final class GateServiceConsent
{
    public function __construct(
        public readonly ?string $terms_url,
        public readonly ?string $privacy_url,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            isset($data['terms_url']) ? (string) $data['terms_url'] : null,
            isset($data['privacy_url']) ? (string) $data['privacy_url'] : null,
        );
    }
}
