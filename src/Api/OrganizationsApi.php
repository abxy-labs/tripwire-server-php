<?php

declare(strict_types=1);

namespace Foil\Server\Api;

use Foil\Server\Http\HttpClient;
use Foil\Server\Resource\Organization;

final class OrganizationsApi
{
    private ApiKeysApi $apiKeys;

    public function __construct(private readonly HttpClient $http)
    {
        $this->apiKeys = new ApiKeysApi($http);
    }

    public function apiKeys(): ApiKeysApi
    {
        return $this->apiKeys;
    }

    public function create(string $name, string $slug): Organization
    {
        $response = $this->http->requestJson('POST', '/v1/organizations', [], [
            'name' => $name,
            'slug' => $slug,
        ]);

        return Organization::fromArray((array) $response['data']);
    }

    public function get(string $organizationId): Organization
    {
        $response = $this->http->requestJson('GET', '/v1/organizations/' . rawurlencode($organizationId));
        return Organization::fromArray((array) $response['data']);
    }

    public function update(string $organizationId, ?string $name = null, ?string $status = null): Organization
    {
        $response = $this->http->requestJson(
            'PATCH',
            '/v1/organizations/' . rawurlencode($organizationId),
            [],
            array_filter([
                'name' => $name,
                'status' => $status,
            ], static fn (mixed $value): bool => $value !== null),
        );

        return Organization::fromArray((array) $response['data']);
    }
}
