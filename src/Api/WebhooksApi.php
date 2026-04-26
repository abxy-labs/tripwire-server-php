<?php

declare(strict_types=1);

namespace Tripwire\Server\Api;

use Tripwire\Server\Http\HttpClient;
use Tripwire\Server\Resource\Event;
use Tripwire\Server\Resource\WebhookEndpoint;
use Tripwire\Server\Resource\WebhookTest;
use Tripwire\Server\Result\ListResult;

final class WebhooksApi
{
    public function __construct(private readonly HttpClient $http) {}

    public function listEndpoints(string $organizationId): ListResult
    {
        $response = $this->http->requestJson('GET', '/v1/organizations/' . rawurlencode($organizationId) . '/webhooks/endpoints');
        return $this->listResult($response, static fn (array $item): WebhookEndpoint => WebhookEndpoint::fromArray($item));
    }

    /**
     * @param array<int, string> $eventTypes
     */
    public function createEndpoint(string $organizationId, string $name, string $url, array $eventTypes): WebhookEndpoint
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

        return WebhookEndpoint::fromArray((array) $response['data']);
    }

    /**
     * @param array<string, mixed> $updates
     */
    public function updateEndpoint(string $organizationId, string $endpointId, array $updates): WebhookEndpoint
    {
        $response = $this->http->requestJson(
            'PATCH',
            '/v1/organizations/' . rawurlencode($organizationId) . '/webhooks/endpoints/' . rawurlencode($endpointId),
            body: $updates,
        );

        return WebhookEndpoint::fromArray((array) $response['data']);
    }

    public function disableEndpoint(string $organizationId, string $endpointId): WebhookEndpoint
    {
        $response = $this->http->requestJson('DELETE', '/v1/organizations/' . rawurlencode($organizationId) . '/webhooks/endpoints/' . rawurlencode($endpointId));
        return WebhookEndpoint::fromArray((array) $response['data']);
    }

    public function rotateSecret(string $organizationId, string $endpointId): WebhookEndpoint
    {
        $response = $this->http->requestJson('POST', '/v1/organizations/' . rawurlencode($organizationId) . '/webhooks/endpoints/' . rawurlencode($endpointId) . '/rotations');
        return WebhookEndpoint::fromArray((array) $response['data']);
    }

    public function sendTest(string $organizationId, string $endpointId): WebhookTest
    {
        $response = $this->http->requestJson('POST', '/v1/organizations/' . rawurlencode($organizationId) . '/webhooks/endpoints/' . rawurlencode($endpointId) . '/test');
        return WebhookTest::fromArray((array) $response['data']);
    }

    public function listEvents(string $organizationId, ?string $endpointId = null, ?string $type = null, ?int $limit = null): ListResult
    {
        $response = $this->http->requestJson(
            'GET',
            '/v1/organizations/' . rawurlencode($organizationId) . '/events',
            query: array_filter([
                'endpoint_id' => $endpointId,
                'type' => $type,
                'limit' => $limit,
            ], static fn (mixed $value): bool => $value !== null),
        );

        return $this->listResult($response, static fn (array $item): Event => Event::fromArray($item));
    }

    public function retrieveEvent(string $organizationId, string $eventId): Event
    {
        $response = $this->http->requestJson('GET', '/v1/organizations/' . rawurlencode($organizationId) . '/events/' . rawurlencode($eventId));
        return Event::fromArray((array) $response['data']);
    }

    /**
     * @param array<string, mixed> $response
     * @param callable(array<string, mixed>): mixed $mapItem
     */
    private function listResult(array $response, callable $mapItem): ListResult
    {
        $items = [];
        foreach ((array) $response['data'] as $item) {
            $items[] = $mapItem((array) $item);
        }

        $pagination = (array) ($response['pagination'] ?? []);
        return new ListResult(
            $items,
            (int) $pagination['limit'],
            (bool) $pagination['has_more'],
            isset($pagination['next_cursor']) ? (string) $pagination['next_cursor'] : null,
        );
    }
}
