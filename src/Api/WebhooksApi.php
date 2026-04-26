<?php

declare(strict_types=1);

namespace Tripwire\Server\Api;

use Tripwire\Server\Http\HttpClient;
use Tripwire\Server\Result\ListResult;

final class WebhooksApi
{
    public function __construct(private readonly HttpClient $http) {}

    public function listEndpoints(string $organizationId): ListResult
    {
        $response = $this->http->requestJson('GET', '/v1/organizations/' . rawurlencode($organizationId) . '/webhooks/endpoints');
        return $this->listResult($response);
    }

    /**
     * @param array<int, string> $eventTypes
     * @return array<string, mixed>
     */
    public function createEndpoint(string $organizationId, string $name, string $url, array $eventTypes): array
    {
        $response = $this->http->requestJson(
            'POST',
            '/v1/organizations/' . rawurlencode($organizationId) . '/webhooks/endpoints',
            body: [
                'name' => $name,
                'url' => $url,
                'event_types' => $eventTypes,
            ],
        );

        return (array) $response['data'];
    }

    /**
     * @param array<string, mixed> $updates
     * @return array<string, mixed>
     */
    public function updateEndpoint(string $organizationId, string $endpointId, array $updates): array
    {
        $response = $this->http->requestJson(
            'PATCH',
            '/v1/organizations/' . rawurlencode($organizationId) . '/webhooks/endpoints/' . rawurlencode($endpointId),
            body: $updates,
        );

        return (array) $response['data'];
    }

    /**
     * @return array<string, mixed>
     */
    public function disableEndpoint(string $organizationId, string $endpointId): array
    {
        $response = $this->http->requestJson('DELETE', '/v1/organizations/' . rawurlencode($organizationId) . '/webhooks/endpoints/' . rawurlencode($endpointId));
        return (array) $response['data'];
    }

    /**
     * @return array<string, mixed>
     */
    public function rotateSecret(string $organizationId, string $endpointId): array
    {
        $response = $this->http->requestJson('POST', '/v1/organizations/' . rawurlencode($organizationId) . '/webhooks/endpoints/' . rawurlencode($endpointId) . '/rotations');
        return (array) $response['data'];
    }

    /**
     * @return array<string, mixed>
     */
    public function sendTest(string $organizationId, string $endpointId): array
    {
        $response = $this->http->requestJson('POST', '/v1/organizations/' . rawurlencode($organizationId) . '/webhooks/endpoints/' . rawurlencode($endpointId) . '/test');
        return (array) $response['data'];
    }

    public function listDeliveries(string $organizationId, ?string $endpointId = null, ?int $limit = null): ListResult
    {
        $response = $this->http->requestJson(
            'GET',
            '/v1/organizations/' . rawurlencode($organizationId) . '/webhooks/deliveries',
            query: array_filter([
                'endpoint_id' => $endpointId,
                'limit' => $limit,
            ], static fn (mixed $value): bool => $value !== null),
        );

        return $this->listResult($response);
    }

    /**
     * @param array<string, mixed> $response
     */
    private function listResult(array $response): ListResult
    {
        $pagination = (array) ($response['pagination'] ?? []);
        return new ListResult(
            (array) $response['data'],
            (int) $pagination['limit'],
            (bool) $pagination['has_more'],
            isset($pagination['next_cursor']) ? (string) $pagination['next_cursor'] : null,
        );
    }
}
