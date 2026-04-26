<?php

declare(strict_types=1);

namespace Tripwire\Server\Api;

use Tripwire\Server\Http\HttpClient;
use Tripwire\Server\Resource\GateManagedService;

final class GateServicesApi
{
    public function __construct(private readonly HttpClient $http) {}

    /**
     * @return array<int, GateManagedService>
     */
    public function list(): array
    {
        $response = $this->http->requestJson('GET', '/v1/gate/services');
        $items = [];
        foreach ((array) $response['data'] as $item) {
            $items[] = GateManagedService::fromArray((array) $item);
        }

        return $items;
    }

    public function get(string $serviceId): GateManagedService
    {
        $response = $this->http->requestJson('GET', '/v1/gate/services/' . rawurlencode($serviceId));
        return GateManagedService::fromArray((array) $response['data']);
    }

    /**
     * @param array<int, array<string, mixed>>|null $envVars
     * @param array<int, array<string, mixed>>|null $sdks
     * @param array<string, mixed>|null $branding
     * @param array<string, mixed>|null $consent
     */
    public function create(
        string $id,
        string $name,
        string $description,
        string $website,
        string $webhookEndpointId,
        ?bool $discoverable = null,
        ?string $dashboardLoginUrl = null,
        ?array $envVars = null,
        ?string $docsUrl = null,
        ?array $sdks = null,
        ?array $branding = null,
        ?array $consent = null,
    ): GateManagedService {
        $response = $this->http->requestJson(
            'POST',
            '/v1/gate/services',
            body: $this->compact([
                'id' => $id,
                'discoverable' => $discoverable,
                'name' => $name,
                'description' => $description,
                'website' => $website,
                'dashboard_login_url' => $dashboardLoginUrl,
                'webhook_endpoint_id' => $webhookEndpointId,
                'env_vars' => $envVars,
                'docs_url' => $docsUrl,
                'sdks' => $sdks,
                'branding' => $branding,
                'consent' => $consent,
            ]),
        );
        return GateManagedService::fromArray((array) $response['data']);
    }

    /**
     * @param array<int, array<string, mixed>>|null $envVars
     * @param array<int, array<string, mixed>>|null $sdks
     * @param array<string, mixed>|null $branding
     * @param array<string, mixed>|null $consent
     */
    public function update(
        string $serviceId,
        ?bool $discoverable = null,
        ?string $name = null,
        ?string $description = null,
        ?string $website = null,
        ?string $dashboardLoginUrl = null,
        ?string $webhookEndpointId = null,
        ?array $envVars = null,
        ?string $docsUrl = null,
        ?array $sdks = null,
        ?array $branding = null,
        ?array $consent = null,
    ): GateManagedService {
        $response = $this->http->requestJson(
            'PATCH',
            '/v1/gate/services/' . rawurlencode($serviceId),
            body: $this->compact([
                'discoverable' => $discoverable,
                'name' => $name,
                'description' => $description,
                'website' => $website,
                'dashboard_login_url' => $dashboardLoginUrl,
                'webhook_endpoint_id' => $webhookEndpointId,
                'env_vars' => $envVars,
                'docs_url' => $docsUrl,
                'sdks' => $sdks,
                'branding' => $branding,
                'consent' => $consent,
            ]),
        );
        return GateManagedService::fromArray((array) $response['data']);
    }

    public function disable(string $serviceId): GateManagedService
    {
        $response = $this->http->requestJson('DELETE', '/v1/gate/services/' . rawurlencode($serviceId));
        return GateManagedService::fromArray((array) $response['data']);
    }

    /**
     * @param array<string, mixed> $values
     * @return array<string, mixed>
     */
    private function compact(array $values): array
    {
        return array_filter($values, static fn (mixed $value): bool => $value !== null);
    }
}
