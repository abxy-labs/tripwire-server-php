<?php

declare(strict_types=1);

namespace Tripwire\Server\Resource;

final class GateManagedService
{
    /**
     * @param array<int, GateServiceEnvVar> $env_vars
     * @param array<int, GateServiceSdkInstall> $sdks
     */
    public function __construct(
        public readonly string $object,
        public readonly string $id,
        public readonly string $status,
        public readonly bool $discoverable,
        public readonly string $name,
        public readonly string $description,
        public readonly string $website,
        public readonly ?string $dashboard_login_url,
        public readonly ?string $webhook_endpoint_id,
        public readonly array $env_vars,
        public readonly string $docs_url,
        public readonly array $sdks,
        public readonly GateServiceBranding $branding,
        public readonly GateServiceConsent $consent,
        public readonly string $created_at,
        public readonly string $updated_at,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $entry = GateRegistryEntry::fromArray($data);

        return new self(
            (string) $data['object'],
            $entry->id,
            $entry->status,
            $entry->discoverable,
            $entry->name,
            $entry->description,
            $entry->website,
            $entry->dashboard_login_url,
            isset($data['webhook_endpoint_id']) ? (string) $data['webhook_endpoint_id'] : null,
            $entry->env_vars,
            $entry->docs_url,
            $entry->sdks,
            $entry->branding,
            $entry->consent,
            (string) $data['created_at'],
            (string) $data['updated_at'],
        );
    }
}
