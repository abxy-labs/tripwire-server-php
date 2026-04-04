<?php

declare(strict_types=1);

namespace Tripwire\Server\Resource;

final class GateServiceSdkInstall
{
    public function __construct(
        public readonly string $label,
        public readonly string $install,
        public readonly string $url,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            (string) $data['label'],
            (string) $data['install'],
            (string) $data['url'],
        );
    }
}
