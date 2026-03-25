<?php

declare(strict_types=1);

namespace Tripwire\Server\Resource;

final class SessionMetadata
{
    public function __construct(
        public readonly string $userAgent,
        public readonly string $url,
        public readonly ?string $screenSize,
        public readonly ?bool $touchDevice,
        public readonly string $clientIp,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            (string) $data['userAgent'],
            (string) $data['url'],
            isset($data['screenSize']) ? (string) $data['screenSize'] : null,
            array_key_exists('touchDevice', $data) ? ($data['touchDevice'] === null ? null : (bool) $data['touchDevice']) : null,
            (string) $data['clientIp'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'userAgent' => $this->userAgent,
            'url' => $this->url,
            'screenSize' => $this->screenSize,
            'touchDevice' => $this->touchDevice,
            'clientIp' => $this->clientIp,
        ];
    }
}

