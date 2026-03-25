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
     * @param array<int, string>|null $allowedOrigins
     */
    public function create(
        string $teamId,
        ?string $name = null,
        ?bool $isTest = null,
        ?array $allowedOrigins = null,
        ?int $rateLimit = null,
    ): IssuedApiKey {
        $response = $this->http->requestJson(
            'POST',
            '/v1/teams/' . rawurlencode($teamId) . '/api-keys',
            [],
            $this->compact([
                'name' => $name,
                'isTest' => $isTest,
                'allowedOrigins' => $allowedOrigins,
                'rateLimit' => $rateLimit,
            ]),
        );

        return IssuedApiKey::fromArray((array) $response['data']);
    }

    public function list(string $teamId, ?int $limit = null, ?string $cursor = null): ListResult
    {
        $response = $this->http->requestJson(
            'GET',
            '/v1/teams/' . rawurlencode($teamId) . '/api-keys',
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
            (bool) $pagination['hasMore'],
            isset($pagination['nextCursor']) ? (string) $pagination['nextCursor'] : null,
        );
    }

    public function revoke(string $teamId, string $keyId): void
    {
        $this->http->requestJson(
            'DELETE',
            '/v1/teams/' . rawurlencode($teamId) . '/api-keys/' . rawurlencode($keyId),
            [],
            null,
            false,
        );
    }

    public function rotate(string $teamId, string $keyId): IssuedApiKey
    {
        $response = $this->http->requestJson(
            'POST',
            '/v1/teams/' . rawurlencode($teamId) . '/api-keys/' . rawurlencode($keyId) . '/rotations',
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

