<?php

declare(strict_types=1);

namespace Tripwire\Server\Api;

use Tripwire\Server\Http\HttpClient;
use Tripwire\Server\Resource\GateRegistryEntry;

final class GateRegistryApi
{
    public function __construct(private readonly HttpClient $http) {}

    /**
     * @return array<int, GateRegistryEntry>
     */
    public function list(): array
    {
        $response = $this->http->requestJson('GET', '/v1/gate/registry', authMode: HttpClient::AUTH_NONE);
        $items = [];
        foreach ((array) $response['data'] as $item) {
            $items[] = GateRegistryEntry::fromArray((array) $item);
        }

        return $items;
    }

    public function get(string $serviceId): GateRegistryEntry
    {
        $response = $this->http->requestJson(
            'GET',
            '/v1/gate/registry/' . rawurlencode($serviceId),
            authMode: HttpClient::AUTH_NONE,
        );
        return GateRegistryEntry::fromArray((array) $response['data']);
    }
}
