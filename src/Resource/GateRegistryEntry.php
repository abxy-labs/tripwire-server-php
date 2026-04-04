<?php

declare(strict_types=1);

namespace Tripwire\Server\Resource;

final class GateRegistryEntry
{
    /**
     * @param array<int, GateServiceEnvVar> $env_vars
     * @param array<int, GateServiceSdkInstall> $sdks
     */
    public function __construct(
        public readonly string $id,
        public readonly string $status,
        public readonly bool $discoverable,
        public readonly string $name,
        public readonly string $description,
        public readonly string $website,
        public readonly ?string $dashboard_login_url,
        public readonly array $env_vars,
        public readonly string $docs_url,
        public readonly array $sdks,
        public readonly GateServiceBranding $branding,
        public readonly GateServiceConsent $consent,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $envVars = [];
        foreach ((array) ($data['env_vars'] ?? []) as $item) {
            $envVars[] = GateServiceEnvVar::fromArray((array) $item);
        }

        $sdks = [];
        foreach ((array) ($data['sdks'] ?? []) as $item) {
            $sdks[] = GateServiceSdkInstall::fromArray((array) $item);
        }

        return new self(
            (string) $data['id'],
            (string) $data['status'],
            (bool) $data['discoverable'],
            (string) $data['name'],
            (string) $data['description'],
            (string) $data['website'],
            isset($data['dashboard_login_url']) ? (string) $data['dashboard_login_url'] : null,
            $envVars,
            (string) $data['docs_url'],
            $sdks,
            GateServiceBranding::fromArray(isset($data['branding']) && is_array($data['branding']) ? $data['branding'] : []),
            GateServiceConsent::fromArray(isset($data['consent']) && is_array($data['consent']) ? $data['consent'] : []),
        );
    }
}
