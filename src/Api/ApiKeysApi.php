<?php

declare(strict_types=1);

namespace Tripwire\Server\Api;

use Tripwire\Server\Http\HttpClient;
use Tripwire\Server\Resource\ApiKey;
use Tripwire\Server\Resource\IssuedApiKey;
use Tripwire\Server\Result\ListResult;

final class ApiKeysApi
{
    public function __construct(private readonly HttpClient $http) {}

    /**
     * @param array<int, string>|null $allowed_origins
     * @param array<int, string>|null $scopes
     */
    public function create(
        string $organizationId,
        string $name,
        ?string $type = null,
        ?string $environment = null,
        ?array $allowed_origins = null,
        ?array $scopes = null,
    ): IssuedApiKey {
        $response = $this->http->requestJson(
            'POST',
            '/v1/organizations/' . rawurlencode($organizationId) . '/api-keys',
            [],
            $this->compact([
                'name' => $name,
                'type' => $type,
                'environment' => $environment,
                'allowed_origins' => $allowed_origins,
                'scopes' => $scopes,
            ]),
        );

        return IssuedApiKey::fromArray((array) $response['data']);
    }

    public function list(string $organizationId, ?int $limit = null, ?string $cursor = null): ListResult
    {
        $response = $this->http->requestJson(
            'GET',
            '/v1/organizations/' . rawurlencode($organizationId) . '/api-keys',
            [
                'limit' => $limit,
                'cursor' => $cursor,
            ],
        );

        $items = [];
        foreach ((array) $response['data'] as $item) {
            $items[] = ApiKey::fromArray((array) $item);
        }

        $pagination = (array) ($response['pagination'] ?? []);

        return new ListResult(
            $items,
            (int) $pagination['limit'],
            (bool) $pagination['has_more'],
            isset($pagination['next_cursor']) ? (string) $pagination['next_cursor'] : null,
        );
    }

    /**
     * @param array<int, string>|null $allowed_origins
     * @param array<int, string>|null $scopes
     */
    public function update(
        string $organizationId,
        string $keyId,
        ?string $name = null,
        ?array $allowed_origins = null,
        ?array $scopes = null,
    ): ApiKey {
        $response = $this->http->requestJson(
            'PATCH',
            '/v1/organizations/' . rawurlencode($organizationId) . '/api-keys/' . rawurlencode($keyId),
            [],
            $this->compact([
                'name' => $name,
                'allowed_origins' => $allowed_origins,
                'scopes' => $scopes,
            ]),
        );
        return ApiKey::fromArray((array) $response['data']);
    }

    public function revoke(string $organizationId, string $keyId): ApiKey
    {
        $response = $this->http->requestJson(
            'DELETE',
            '/v1/organizations/' . rawurlencode($organizationId) . '/api-keys/' . rawurlencode($keyId),
        );
        return ApiKey::fromArray((array) $response['data']);
    }

    public function rotate(string $organizationId, string $keyId): IssuedApiKey
    {
        $response = $this->http->requestJson(
            'POST',
            '/v1/organizations/' . rawurlencode($organizationId) . '/api-keys/' . rawurlencode($keyId) . '/rotations',
        );

        return IssuedApiKey::fromArray((array) $response['data']);
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
