<?php

declare(strict_types=1);

namespace Tripwire\Server\Api;

use Tripwire\Server\Http\HttpClient;
use Tripwire\Server\Resource\GateDashboardLogin;
use Tripwire\Server\Resource\GateLoginSession;

final class GateLoginSessionsApi
{
    public function __construct(private readonly HttpClient $http) {}

    public function create(string $serviceId, string $agentToken): GateLoginSession
    {
        $response = $this->http->requestJson(
            'POST',
            '/v1/gate/login-sessions',
            body: ['service_id' => $serviceId],
            authMode: HttpClient::AUTH_BEARER,
            bearerToken: $agentToken,
        );
        return GateLoginSession::fromArray((array) $response['data']);
    }

    public function consume(string $code): GateDashboardLogin
    {
        $response = $this->http->requestJson(
            'POST',
            '/v1/gate/login-sessions/consume',
            body: ['code' => $code],
        );
        return GateDashboardLogin::fromArray((array) $response['data']);
    }
}
