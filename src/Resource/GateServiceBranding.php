<?php

declare(strict_types=1);

namespace Tripwire\Server\Resource;

final class GateServiceBranding
{
    public function __construct(
        public readonly ?string $logo_url,
        public readonly ?string $primary_color,
        public readonly ?string $secondary_color,
        public readonly ?string $ascii_art,
        public readonly bool $verified,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            isset($data['logo_url']) ? (string) $data['logo_url'] : null,
            isset($data['primary_color']) ? (string) $data['primary_color'] : null,
            isset($data['secondary_color']) ? (string) $data['secondary_color'] : null,
            isset($data['ascii_art']) ? (string) $data['ascii_art'] : null,
            (bool) ($data['verified'] ?? false),
        );
    }
}
