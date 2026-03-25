<?php

declare(strict_types=1);

namespace Tripwire\Server\Api;

use Tripwire\Server\Http\HttpClient;
use Tripwire\Server\Resource\Team;

final class TeamsApi
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

    public function create(string $name, string $slug): Team
    {
        $response = $this->http->requestJson('POST', '/v1/teams', [], [
            'name' => $name,
            'slug' => $slug,
        ]);

        return Team::fromArray((array) $response['data']);
    }

    public function get(string $teamId): Team
    {
        $response = $this->http->requestJson('GET', '/v1/teams/' . rawurlencode($teamId));
        return Team::fromArray((array) $response['data']);
    }

    public function update(string $teamId, ?string $name = null, ?string $status = null): Team
    {
        $response = $this->http->requestJson(
            'PATCH',
            '/v1/teams/' . rawurlencode($teamId),
            [],
            array_filter([
                'name' => $name,
                'status' => $status,
            ], static fn (mixed $value): bool => $value !== null),
        );

        return Team::fromArray((array) $response['data']);
    }
}

